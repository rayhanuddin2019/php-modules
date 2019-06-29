# Introduction

I'll try to keep this a brief as possible, but it's important to go through all the mundane stuff, because it helps to 
provide context and better demonstrates the rationale for this project. It's easier for me to walk you through what
problems we tried to solve and how they were solved (by us or others), than it is for me to give a list of reasons why
I think it's a good idea.

So even if you already know this stuff, I encourage you to at least _skim_ through the boring parts :)

## How this came to be

It all starts with a desire to have an application be extendable by first or third parties. For this, a proper
dependency injection system is a must. And so our story begins with [containers][psr11] and [service providers][psr11+].

In short, containers are an interoperable mechanism by which services can be retrieved. In this context, services are
any piece of data that is used by another. Service providers are, as you can imagine, objects that provide services.
Services are provided in the form of factory functions, that receive a container and return the service.

To understand why such concepts and standards are useful, let's first consider what it takes to set up the required
instances for [Twig][twig], a templating library. The following is taken from their [documentation][twig-docs]

```php
$loader = new \Twig\Loader\FilesystemLoader('/path/to/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => '/path/to/compilation_cache',
]);
```

Observe how the `\Twig\Environment` class requires a `\Twig\Loader\*Loader` instance. In this case, we're using the
`FilesystemLoader`. This makes the loader a dependency for the environment, and the environment dependent on the loader.

## The need for extensions (DI)

Recall that we started this story with a desire to have our application be extendable. In this case, we'd want to allow
some other code to either (a) add additional template paths to the loader, or (b) change the loader instance completely.

There are a number of ways to achieve this. Perhaps the least convoluted way is to give each instance a string name by
which it can be referenced. Storing this key and instance pair in an array becomes the next logic step. Lastly, you'd
need to provide an API for modifying that array.

```php
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class MyApp
{
    protected $array;
    
    public function __construct()
    {
        $this->array = [];
        $this->array['twig_loader'] = new FilesystemLoader('/path/to/templates');
        $this->array['twig_env'] = new Environment($this->array['twig_loder'], [
            'cache' => '/path/to/compilation_cache',
        ]);
    }
    
    public function set($key, $object)
    {
        $this->array[$key] = $object;
    }
}
```

We still have the environment being given a loader, so everything should still work fine. But now, 1st or 3rd party code
can call `MyApp::set()` to change out any of the components. There's just one problem.

When the constructor has finished creating the array, the twig environment object has already been given the original
loader, via `$this->array['twig_loader']`. So if some code decides to change the loader using the `set()` method, the
environment instance will remain unchanged. We'd need to replace the environment instance as well. And we may have
more than 1 environment instance, perhaps some of which our application is unaware of (because they're used by 3rd
party extensions, for example).

This is where the concept of factory functions comes in. By storing the instances in a function, we avoid actually
creating them until we call them. If we pass the entire array to the functions, they will have access to the other keys,
allowing them to read and use whatever they require. Remember that we'd need to call the factories after retrieving
them.

```php
public function __construct()
{
    $this->array = [
        'twig_loader' => function ($array) {
            return new FilesystemLoader('/path/to/templates');
        },
        'twig_env' => function ($array) {
            // Notice how we need to run the factory here
            //                                         ||
            //                                         \/
            return new Environment($array['twig_loader']($array), [
                'cache' => '/path/to/compilation_cache',
            ]);
        },
    ];
}
```

## And then ... Containers

Now ideally, we also keep a second array for cache. Otherwise, we'd be creating new loader instances every time we do
`$array['twig_loader']()`. This is where containers come in. They provide an abstraction for all of this behavior.
Containers provide two methods: `get($key)` and `has($key)`. Typically, a container implementation will look something
like this:

```php
class Container {
    protected $cache;
    protected $factories;
    
    public function __construct($factories = []) {
        $this->factories = $factories;
        $this->cache = [];
    }
    
    public function get($key) {
        if (!array_key_exists($key, $this->factories)) {
            // throw some error
        }
        
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->factories[$key]($this);
        }

        return $this->cache[$key];
    }
    
    public function get($key) {
        return array_key_exists($key, $this->factories);
    }
}
``` 

Notice how the container caches created instances immediately after invoking the factory function. It also passes itself
to the factory function. This is done for the same reason why we were previously passing our array to each function.
We can now rewrite our services using a container, instead of an array.

```php
public function __construct()
{
    $factories = [
        'twig_loader' => function (Container $c) {
            return new FilesystemLoader('/path/to/templates');
        },
        'twig_env' => function (Container $c) {
            return new Environment($c->get('twig_loader'), [
                'cache' => '/path/to/compilation_cache',
            ]);
        },
    ];
    
    $container = new Container($factories);
}
```

Now, the factory function for our twig environment instance can simply call `$c->get('twig_loader')`, which will return
the loader instance. If any other service exists that does the same, they will receive the exact same instance.

The next step is to become standards compliant. [PSR-11][psr11] is a PHP-FIG standard for containers that provides a 
[`ContainerInterface`][container-interface] interface, as well as a couple of exceptions related to container operations.

## `set()` a better way

Let's replace our application's `set()` mutation method with a more robust system. For this, we can use 
[service providers][psr11+], which are simply instances that can provide services. You might notice that they split up
the services, distinguishing between factories and extensions.

```
class MyServiceProvider implements ServiceProviderInterface
{
    public function getFactories() {
        return [
            'menu_template' => function (ContainerInterface $c) {
                return new Template('templates/mine/my-template.php');
            },
            'menu_renderer' => function (ContainerInterface $c) {
                return new Renderer($c->get('menu_template'), ['show_settings' => false]);
            }
        ];
    }
    
    public function getExtension() {
        return [
            'rest_api' => function (ContainerInterface $c, $api) {
                $api->addRoute(/* ... */);

                return $api;
            },
        ];
    }
}
```

The difference is both functional and semantic. Extension keys, like the `"rest_api"` key above, are expected to exist
as factories. The above expects a `"rest_api"` factory to exist, and when called it receives its value as the second
argument. The extension then has a chance to extend that service, or even outright replace it by returning something
else.

With this, our application no longer needs a `set()` method. Instead, we can simply collect a list of service provider
instances, get their factories and extensions and create our container. This means that we've replaced our `set()`
method with a more flexible, robust and interoperable way of adding or replacing services in our application. External
code now only needs to give our application service provider instances.

This still means that our application needs an
API for allowing service provider registration, but receiving a new service provider instance has no side effects unless
the application wants such side effects, whereas having a `set()` method directly modify an array that the application
uses takes control away from the application.

## Finally, modularity

Ever since the dawn of software development, developers have dreamed of breaking down their software into chunks, often
called modules. It's hard dream to fully realize, which tends to make us developer envious of those who work in hardware.

A PC is the best example of modularity. Remove a memory stick and your computer won't complain; you just have less
memory. Remove the GPU and you'll end up with whatever graphics processing your CPU provides (if any). Similarly, a
sound card is often not required, but one may be added to enhance your computer's sound processing and reproduction
quality.

This optional or on/off nature of modules makes them tricky to implement, and when implemented they are often times
bound to some API that the application defines; dynamically linked libraries are an example of this. In PHP, we have the
advantage of being able to come very close to realizing that dream, and the power lies within the concept of dependency 
injection containers and service providers.

By extending service providers to be able to `run()`, we give applications an API to allow modules to do their thing.

```php
interface ModuleInterface extends ServiceProviderInterface {
    public function run(ContainerInterface $c);
}
```

With this, modules are able to provide the application with functionality that the application would otherwise need to
implement. For instance, a module can when `run()` output a top-bar menu on our website. It might need to use some of
its services, declared in its `getFactories()` method, so the `run()` method will need to be given the container just
like any other service. But more than that, another module might extend or overwrite one of its services. This gives
modules the ability to influence, extend or compliment each other beautifully.

Furthermore, applications that use modules can be modules themselves. Entire applications may then be plugged into
another modular application, allowing all of its functionality to be reused in any context.

---

Next: [Modules](modules.md)

[psr11]: https://github.com/php-fig/container
[psr11+]: https://github.com/container-interop/service-provider
[twig]: https://twig.symfony.com/
[twig-docs]: https://twig.symfony.com/doc/2.x/api.html
[container-interface]: https://github.com/php-fig/container/blob/master/src/ContainerInterface.php
