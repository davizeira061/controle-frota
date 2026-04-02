<?php
/**
 * AutoLoader simples para PSR-4
 * 
 * Carrega classes automaticamente baseado no namespace
 */

spl_autoload_register(function ($class) {
    // Base path
    $base_path = dirname(__DIR__);

    // Prefixo do namespace
    $prefix = 'App\\';
    $core_prefix = 'Core\\';

    // Se for classe do Core
    if (strpos($class, $core_prefix) === 0) {
        $relative_class = substr($class, strlen($core_prefix));
        $file = $base_path . '/core/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Se for classe do App
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_path . '/app/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
