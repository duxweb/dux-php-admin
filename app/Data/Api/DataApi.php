<?php

namespace App\Data\Api;

use Core\Route\Attribute\RouteGroup;

#[RouteGroup(app: 'api', route: '/data/{name}', name: 'data')]
class DataApi extends Data
{
    public $api = true;
}