# Core

Core contains the default BASE3 runtime implementations, container, classmap, module registry and standalone bootstrap. It depends on CoreFoundation and uses `Base3\Core` as PHP namespace.

The entry-point autoloader lives at the application root in `bootstrap/Autoloader.php`, before the plugin classmap is available. The existing PluginClassMap remains the sole discovery and instantiation implementation.

The global source folder `src/` is removed; all framework implementations and contracts live in their owning modules.
