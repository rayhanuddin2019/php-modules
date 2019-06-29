# Module Conventions

In other to enable the toggling of modules, a few conventions ought to be established.

### 1. Module factories should be self-contained

This basically means that a module's factories should only reference services from that module. Similarly, a module's
`run()` method should only fetch services from the container that are declared in the module's factories. 

Having a module require, or assume the presence of, another module's service creates a hard dependency on that module.
Strictly speaking, module dependency is not a bad thing, but soft dependencies should be preferred. The best way to have
soft dependencies between modules is by allowing modules to optionally integrate with each other. A factory can check
if an external service exists and return it if it does; otherwise it can return some default value.

```php
class ModuleA implements ModuleInterface
{
    public function run(ContainerInterface $c) {}
    public function getExtension() { return []; }

    public function getFactories() {
        return [
            'module_a_service' => function (ContainerInterface $c) {
                return new SomeClassA();
            }
        ];
    }
}

class ModuleB implements ModuleInterface
{
    public function run(ContainerInterface $c) {}
    public function getExtension() { return []; }

    public function getFactories() {
        return [
            // This is a hard dependency! ModuleB cannot work without ModuleA
            'module_b_service_1' => function (ContainerInterface $c) {
                return new SomeClassB($c->get('module_a_service'));
            },
            
        ];
    }
}

class ModuleC implements ModuleInterface
{
    public function run(ContainerInterface $c) {}
    public function getExtension() { return []; }

    public function getFactories() {
        return [
            // This is self-contained
            // It references another service from this module
            'module_c_service' => function (ContainerInterface $c) {
                return new SomeClassC($c->get('module_c_dependency'));
            },
            
            // Intermediate service for integration with ModuleA
            'module_c_dependency' => function (ContainerInterface $c) {
                if ($c->has('module_a_service')) {
                    return $c->get('module_a_service');
                }

                return new SomeDefault(); 
            },
            
        ];
    }
}
```

### 2. Module factories should not overwrite each other

Unlike the first convention, which helps to prevent hard module dependencies, this convention is more about semantics.

Technically, a module _can_ declare a factory with a key that is also used by another module. This won't create a hard
dependency; either module will still work fine without the other. But the resulting behavior is **undefined**. That is,
the behavior depends on how the application uses the modules. For this reason, modules should not declare factories with
identical keys. To prevent accidental collisions, modules should therefore prefix their service keys with a
module-specific string. 

If accidental collision occurs still, perhaps due to modules using the same prefix, then it falls unto the application
to resolve the conflicts. 

If a module desires to take over another module's service, it should create an extension that simply returns the new
instance. Extensions are intended to be used in this way and have clear defined behavior. This semantically separates 
factories and extensions: factories are self-contained services provided by the module and used by the module, whereas
extensions are used for modifying and integrating with other modules.

If multiple modules provide extensions for the same service and the order in which extensions are applied matters,
it falls unto the application to properly sort the modules or the extensions.

### 3. Service keys should use snake_case and forward-slashes for delimiters

Service keys should use `snake_case` for consistency. The use of `camelCase`, `kebab-case` or any other `weird+case`
should be avoided. This reduces confusion and disconnect between modules, allowing the application to use the same
convention everywhere.

Furthermore, service keys are allowed to use a forward-slash as a grouping delimiter, similar to PHP's namespace syntax.
Module prefixes should make up the first segment of this path-like string key. The criteria by which services are
grouped is left entirely up to the module. But ideally the grouping is done logically and is consistent.

```php
class NavMenuModule implements ModuleInterface
{
    public function run(ContainerInterface $c) {
        // ...
    }

    public function getFactories() {
        return [
            'nav_menu/template/name' => function (ContainerInterface $c) {
                return 'menu.twig';
            },

            'nav_menu/template/args' => function (ContainerInterface $c) {
                return [ /* ... */ ];
            },

            'nav_menu/twig/loader' => function (ContainerInterface $c) {
                return new FilesystemLoader('/path/to/templates');
            },

            'nav_menu/twig/env' => function (ContainerInterface $c) {
                return new Environment($c->get('nav_menu/twig/loader'), [ /* ... */ ]);
            },
        ];
    }
    
    public function getExtension() {
        return [];
    }
}
```

---

Next: [Multi-boxing](multi-boxing.md)
