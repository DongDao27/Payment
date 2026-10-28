<?php

// Local, one-time provisioning. Never prints credentials or overwrites configured databases.
$root = dirname(__DIR__);
$runtime = $root.'/.runtime';
if (is_dir($runtime.'/mysql-data') || file_exists($runtime.'/mysql-admin.cnf')) {
    fwrite(STDERR, "Existing local database configuration detected; refusing to replace it.\n");
    exit(1);
}
$envPath = $root.'/backend/.env';
$current = file_get_contents($envPath);
if (preg_match('/^DB_CONNECTION=mysql$/m', $current)) {
    fwrite(STDERR, "Existing MySQL env detected; configure manually to preserve credentials.\n");
    exit(1);
}
$passwords = array_map(fn () => bin2hex(random_bytes(24)), range(1, 3));
[$admin, $development, $testing] = $passwords;
$base = str_replace('\\', '/', $runtime);
$mysqlRoot = $base.'/mysql-8.4.13-winx64';
file_put_contents($runtime.'/mysql.ini', "[mysqld]\nbasedir=\"$mysqlRoot\"\ndatadir=\"$base/mysql-data\"\nbind-address=127.0.0.1\nport=3307\nmysqlx=0\ncharacter-set-server=utf8mb4\ncollation-server=utf8mb4_unicode_ci\ndefault-time-zone=+00:00\nlog-error=\"$base/mysql-error.log\"\npid-file=\"$base/mysql.pid\"\n");
file_put_contents($runtime.'/mysql-admin.cnf', "[client]\nhost=127.0.0.1\nport=3307\nuser=root\npassword=$admin\n");
$sql = "ALTER USER 'root'@'localhost' IDENTIFIED BY '$admin';\n";
foreach (['thu_thanh' => $development, 'thu_thanh_test' => $testing] as $database => $password) {
    $sql .= "CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $sql .= "CREATE USER '$database'@'127.0.0.1' IDENTIFIED BY '$password';\n";
    $sql .= "GRANT ALL PRIVILEGES ON `$database`.* TO '$database'@'127.0.0.1';\n";
}
file_put_contents($runtime.'/mysql-init.sql', $sql);
$template = file_get_contents($root.'/backend/.env.example');
preg_match('/^APP_KEY=(.*)$/m', $current, $matches);
$env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.trim($matches[1] ?? ''), $template);
$env = str_replace(['DB_PORT=3306', 'DB_USERNAME=', 'DB_PASSWORD='], ['DB_PORT=3307', 'DB_USERNAME=thu_thanh', 'DB_PASSWORD='.$development], $env);
file_put_contents($envPath, $env);
$testEnv = "APP_NAME=\"Thu Thanh Tests\"\nAPP_ENV=testing\nAPP_KEY=base64:".base64_encode(random_bytes(32))."\nAPP_DEBUG=false\nAPP_URL=http://localhost:8000\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3307\nDB_DATABASE=thu_thanh_test\nDB_USERNAME=thu_thanh_test\nDB_PASSWORD=$testing\nCACHE_STORE=array\nSESSION_DRIVER=array\nQUEUE_CONNECTION=sync\nMAIL_MAILER=array\n";
file_put_contents($root.'/backend/.env.testing', $testEnv);
echo "Private local env and database configuration prepared. No credentials printed.\n";
