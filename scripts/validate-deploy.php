<?php

require dirname(__DIR__).'/backend/vendor/autoload.php';
foreach (['compose.yml', 'compose.scale.yml'] as $name) {
    $config = Symfony\Component\Yaml\Yaml::parseFile(dirname(__DIR__).'/deploy/'.$name);
    if (! is_array($config['services'] ?? null)) { throw new RuntimeException('Missing services: '.$name); }
}
echo "Compose YAML parsed. Docker images, Nginx syntax and production runtime were not executed.\n";
