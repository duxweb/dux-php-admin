<?php

declare(strict_types=1);

namespace App\Member;

use App\System\Models\SystemApi;
use Core\Api\ApiMiddleware;
use Core\App\AppExtend;
use Core\Auth\AuthMiddleware;
use Core\Bootstrap;
use Core\Handlers\ExceptionBusiness;

use Core\Route\Route;

/**
 * Application Registration
 */
class App extends AppExtend
{

    public function init(Bootstrap $app): void
    {
        \Core\App::route()->set(
            "apiMember",
            new Route(
                "/api",
                "",
                new ApiMiddleware(function ($id) {
                    $apiInfo = SystemApi::query()->where('secret_id', $id)->firstOr(function () {
                        throw new ExceptionBusiness('Signature authorization failed', 402);
                    });
                    return $apiInfo->secret_key;
                }),
                new AuthMiddleware("member")
            ),
        );

    }

    public function register(Bootstrap $app): void
    {
    }
}
