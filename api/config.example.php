<?php
// Settings for the IntelliFeed server.
//
// To secure an installation: copy this file to config.php (same folder) and
// change the values there. config.php is ignored by git, so secrets stay off
// GitHub. If config.php does not exist, these defaults are used.

return [
    // Database. The defaults match a fresh XAMPP install (root, no password).
    // For real use, create a dedicated MySQL user with a password that only has
    // access to the intellifeed database, and put it here.
    'db_host' => 'localhost',
    'db_name' => 'intellifeed',
    'db_user' => 'root',
    'db_pass' => '',

    // Shared secret between this server and the feeder. Must be identical to
    // `deviceKey` at the top of the ESP32 sketch. Change it to a long random string.
    'device_key' => 'change-me',
];
