<?php

namespace App\Data\Api;

use Core\Docs\Attribute\Docs;
use Core\Route\Attribute\RouteGroup;

#[RouteGroup(app: 'api', route: '/data/{name}', name: 'data')]
#[Docs(name: '数据集', category: '数据')]
class DataApi extends Data
{
    public $api = true;
}