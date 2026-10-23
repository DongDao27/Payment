<?php

return [
    // Registration accepts up to 128 characters; Argon2id avoids bcrypt's 72-byte truncation.
    'driver' => env('HASH_DRIVER', 'argon2id'),
    'argon' => ['memory' => (int) env('ARGON_MEMORY', 65536), 'threads' => (int) env('ARGON_THREADS', 1), 'time' => (int) env('ARGON_TIME', 4), 'verify' => true],
    'bcrypt' => ['rounds' => (int) env('BCRYPT_ROUNDS', 12), 'verify' => true, 'limit' => 72],
    'rehash_on_login' => true,
];
