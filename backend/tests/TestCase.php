<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app['config']->get('database.default') !== 'mysql'
            || $app['config']->get('database.connections.mysql.database') !== 'thu_thanh_test') {
            throw new \RuntimeException('Tests require isolated MySQL database thu_thanh_test.');
        }

        return $app;
    }
}
