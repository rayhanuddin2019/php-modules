# Module Multi-Boxing

_Yes, I borrowed the term from MMORPGs for lack of a better name._

The term multi-boxing means to have more than 1 instance. In this context, module multi-boxing simply means having the
ability to have more than 1 instance of the same module.

At first, given some thought you might think this is impossible. If the [module conventions][conventions] made sense to
you and you're convinced by them, then you'd also know that convention 2 states that module factories should not
overwrite each other. So how can we instantiate and use a module class twice? Won't the module's services be processed
twice by the application? 

But most importantly, why would you want to have 2 instances of the same module?

## Why?

To explain why we would want module multi-boxing, we're going to be using the nav menu module example that we used in
the [modules][modules] section., with some modifications.

```php
class NavMenuModule implements ModuleInterface
{
    public function run(ContainerInterface $c) {
        echo $c->get('nav_menu/twig/env')->render(
            $c->get('nav_menu/template/name'),
            $c->get('nav_menu/template/args')
        );
    }

    public function getFactories() {
        return [
            'nav_menu/links' => function (ContainerInterface $c) {
                return [
                    'Home' => '/index.php',
                ];
            },
            'nav_menu/template/name' => function (ContainerInterface $c) {
                return 'menu.twig';
            },
            'nav_menu/template/args' => function (ContainerInterface $c) {
                return [
                    'text_color' => '#fff',
                    'bg_color' => '#24292e',
                    'position' => 'top',
                    'links' => $c->get('nav_menu/links'),
                ];
            },
            'nav_menu/twig/loader' => function (ContainerInterface $c) {
                return new FilesystemLoader('/path/to/templates');
            },
            'nav_menu/twig/env' => function (ContainerInterface $c) {
                return new Environment($c->get('nav_menu/twig/loader'), []);
            },
        ];
    }
    
    public function getExtension() {
        return [];
    }
}
```

Let's assume that we want to use this module for our website to add a top navigation menu, but we also want to have
a second navigation menu on the bottom. This module already provides this functionality; we just need to change the
`nav_menu/templates/args` service to have `'position' => 'bottom'`.

Without multi-boxing, we'd need to copy and paste the module class code, rename it and change the service keys so as to
not conflict. Why copy? Because we can't simply extend the class and override `getFactories()`; the factory functions
have internal references. The `nav_menu/template/args` service internally references `nav_menu/link`, so even if we
rename the service to `second_nav_menu/template/args`, the factory would still be using the first module's links array.

Another possibility is to have the module provide a way to have multiple nav menus. This would require the module to
be written differently, explicitly providing multiple nav menu support and thus removing the need for multiple module
instances. But this adds additional effort on the part of the module author. The below demonstrates this by exposing
a service that holds a list of nav menu instances (previously known as template args).

```php
class NavMenuModule implements ModuleInterface
{
    public function run(ContainerInterface $c) {
        foreach ($c->get('nav_menu/instances') as $instance) {
            echo $c->get('nav_menu/twig/env')->render(
                $c->get('nav_menu/template/name'),
                $instance
            );
        }
    }

    public function getFactories() {
        return [
            'nav_menu/instances' => function (ContainerInterface $c) {
                return [
                    'main' => [
                        'links' => [
                          'Home' => '/index.php',
                        ],
                        'text_color' => '#fff',
                        'bg_color' => '#24292e',
                        'position' => 'top',
                    ],
                ];
            },
            'nav_menu/template/name' => function (ContainerInterface $c) {
                return 'menu.twig';
            },
            'nav_menu/twig/loader' => function (ContainerInterface $c) {
                return new FilesystemLoader('/path/to/templates');
            },
            'nav_menu/twig/env' => function (ContainerInterface $c) {
                return new Environment($c->get('nav_menu/twig/loader'), []);
            },
        ];
    }
    
    public function getExtension() {
        return [];
    }
}
```

This is inconvenient as it puts unnecessary burden on module developers who would now have to think about how consumers
might want to use the modules. Ideally, multi-boxing is an automatic process that requires no extra effort from the
module authors, like how OOP classes are automatically instantiable in different ways with little to no effort from the
class author. 

## How?

Since we hope to have multi-boxing not require effort on the part of module developers, it logically follows that
multi-boxing should be achieved by the application. If given the right tools, applications should be able to use as many
copies of a module as they require. The difficulty with achieving this from _outside_ the module is, as previously
mentioned, renaming the keys used **inside** factory functions.

In other words, to find a way to change this:
```php
[
    'foo' => function (ContainerInterface $c) {
        return new SomeClass($c->get('bar'));
    }
]
```

to this:

```php
[
    'new/foo' => function (ContainerInterface $c) {
        return new SomeClass($c->get('new/bar'));
    }
]
```

Since this is an array, we can re-map the key easily. But there is no way to modify the code in a callable; i.e. we
can't do `$factories['key']->bar = "new/bar"` if `$factories['key']` is a closure function.

[Or can we?](https://www.youtube.com/watch?v=1dwu4iVA1yo)

If we could access a service's dependencies in such a way, we could remap them, making the service depend on something
else. Not only that, but we could even programmatically analyse our services and their dependencies, potentially
finding problems without running the module, such as circular dependency, depending on non-existent services and so on.

The only issue is that the [service provider][psr11+] spec only allows services to be `callable` values. That is, we
can't create a `ServiceInterface` interface or use an array.

```php
[
    'foo' => [
        'requires': ['bar'],
        'factory: function (ContainerInterface $c) {
            return new SomeClass($c->get('new/bar'));
        }
    ]
]
```

Sure, we can diverge from that PSR (it's experimental after all), but even without that spec many container
implementations _do_ expect services to be factory functions.

## Solution

The solution is actually quite simple: [`__invoke()`][php__invoke]!

Consider the below invokable class, which is equivalent to the `foo` service from the previous section's examples.

```php
class FooService 
{
    public function __invoke(ContainerInterface $c) {
        return new SomeClass($c->get('new/bar'));
    }
}
```

We can use instances of this class as factory functions in our modules:

```php
[
    'foo' => new FooService(),
]
```

And therein lies the secret to module multi-boxing. By creating a [`Factory`][factory] class that exposes its
dependencies, we can now crawl the list of services programmatically and re-wire the services however we want. As an
added bonus, such a class can also use the list of dependencies to auto-get those services from the container and pass
**those** to the factory function rather than passing the whole container. And we haven't even mentioned the utility
of having other implementations, such as [`Config`][config] for static values or [`Callback`][callback] for callback
functions, which would have otherwise been nested anonymous functions. Yuck!

```
[
    'foo' => new Factory(['bar'], function ($bar) {
        return new SomeClass($bar);
    }),
    'bar' => new Config('path/to/template/file.twig'),
]
```

Now you might ask, wasn't the solution supposed to require little to no effort on the part of modules? Well, that's why
the class analogy was made. Here, the module author does not need to add additional services to support multiple
instances of certain services or modules. Instead, they can simply use an alternative service declaration strategy that
also makes their module support multi-boxing.

Most of the work still needs to be done by the application. If the application needs to use two instances of the
`NavMenuModule`, then this package provides it with the tools to do so. Namely, a
[`KeyConvertingModule`][key-converting-module] class;  a decorator class which can rename all of the services in the
module along with any dependencies used by factories and extensions. The [`PrefixChangeModule`] class in particular is
especially useful since it can change service prefixes, for instance from `nav_menu/` to `bottom_menu/`.

---

Thank you for reading! :D

[modules]: ./modules.md
[conventions]: ./conventions.md
[psr11+]: https://github.com/container-interop/service-provider
[php__invoke]: https://www.php.net/manual/en/language.oop5.magic.php#object.invoke
[factory]: ../src/Services/Factory.php
[config]: ../src/Services/Config.php
[callback]: ../src/Services/Callback.php
[key-converting-module]: ../src/KeyConvertingModule.php
[prefix-change-module]: ../src/PrefixChangeModule.php
