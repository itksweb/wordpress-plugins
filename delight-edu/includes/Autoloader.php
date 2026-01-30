<?php
spl_autoload_register( function ( $class ) {
    // 1. Define our Namespace Prefix
    $prefix = 'DelightEDU\\';

    // 2. Define the Base Directory for that prefix (the 'core' folder)
    $base_dir = __DIR__ . '/../core/';

    // 3. Does the class use our prefix?
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return; // No? Move to the next registered autoloader
    }

    // 4. Get the relative class name (e.g., Database\Schema)
    $relative_class = substr( $class, $len );

    // 5. Replace namespace separators (\) with directory separators (/) 
    // and append .php
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    // 6. If the file exists, require it
    if ( file_exists( $file ) ) {
        require $file;
    }
});