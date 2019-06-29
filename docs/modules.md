# Modules

## Writing Modules

Let's go back to our original Twig example, and write a module that uses Twig to output a top navigation menu for a
website.

```php
class NavMenuModule implements ModuleInterface
{
    public function run(ContainerInterface $c) {
        $twig     = $c->get('twig_env');
        $template = $c->get('menu_template');
        $args     = $c->get('menu_template_args');

        echo $twig->render($template, $args);
    }

    public function getFactories() {
        return [
            'menu_template' => function (ContainerInterface $c) {
                return 'menu.twig';
            },
            'menu_template_args' => function (ContainerInterface $c) {
                return [
                    'text_color' => '#fff',
                    'bg_color' => '#24292e',
                    'links' => [
                        'Home' => '/index.php',
                    ],
                ];
            },
            'twig_loader' => function (ContainerInterface $c) {
                return new FilesystemLoader('/path/to/templates');
            },
            'twig_env' => function (ContainerInterface $c) {
                return new Environment($c->get('twig_loader'), [
                    'cache' => '/path/to/compilation_cache',
                ]);
            },
        ];
    }
    
    public function getExtension() {
        return [];
    }
}
```

Such as module is agnostic of any application that may consume it, any by default will render black navbar menu with a
link to the home page. Suppose this module is provided by some 3rd party; we can add additional links to the menu via
another module.

```php
class MyAppMenuModule implements ModuleInterface
{
    public function run(ContainerInterface $c) {}

    public function getFactories() {
        return [];
    }
    
    public function getExtension() {
        return [
            'menu_template_args' => function (ContainerInterface $c, $args) {
                $args['links] = [
                    'Home' => '/',
                    'Pricing' => '/pricing',
                    'Support' => '/support',
                    'About' => '/about-us',
                ];

                return $args;
            },
        ];
    }
}
```

## Writing Modular Applications

The simplistic nature of the module system allows modular applications to be as simple or as complex as they want.

In this package you can find a [`CompositeModule`][composite-module] class which given an array of modules combines them
into a single module instance. You can also find a [`ServiceProviderContainer`][service-provider-container] which is a
special implementation of a container that sets up its services using a service provider. Combined, these two classes
make creating a container for a modular application a breeze, and also enables some very simple modular systems.

```php
class MyApplication
{
    protected $modules;
    
    public function addModule(ModuleInterface $module) {
        $this->modules[] = $module;
    }
    
    public function run() {
        // Compose a single module that groups all of our modules
        $composite = new CompositeModule($this->modules);

        // Since a module is a service provider, we can pass the composite to our container
        $container = new ServiceProviderContainer($composite);
        
        // Run all of our modules
        foreach ($this->modules as $module) {
            $module->run($container);
        }
    }
}
```

We mentioned briefly in the previous section that applications can also be their own modules. We can achieve this by
extending the [`CompositeModule`][composite-module] class, however in this case we must accept the container from
outside our class.

```php
class MyApplication extends CompositeModule
{
    public function run(ContainerInterface $c) {
        // app-specific run instructions can go here

        // Run the module
        parent::run($c);

        // app-specific run instructions can go here
    }
}

// We can instantiate our application with a list of modules
$myApp = new MyApplication($modules);

// We can then run our application
$container = new ServiceProviderContainer($myApp);
$myApp->run($container);

// Or attach it as a module to another application
$otherModules = [
    // ...
    $myApp,
    // ...
];
$otherApp = new OtherApplication($otherModules);
```

---

Next: [Conventions](conventions.md)

[composite-module]: ../src/CompositeModule.php
[service-provider-container]: ../src/Containers/ServiceProviderContainer.php
