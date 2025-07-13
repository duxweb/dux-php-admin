<?php

namespace App\Member\Event;

use Core\Handlers\ExceptionBusiness;
use Symfony\Contracts\EventDispatcher\Event;

class UnionEvent extends Event
{
    private array $loginData = [];

    /**
     * @param string $type 登录类型
     * @param string $code 三方code
     * @param array $params 三方参数
     * @param string $appId 应用 ID
     */
    public function __construct(public string $type, public string $code, public array $params = [], public ?string $appId = null)
    {
    }

    public function getLoginData(): array
    {
        return $this->loginData;
    }

    public function setLoginData(array $data): void
    {
        if (!$data['type'] || !$data['open_id']) {
            throw new ExceptionBusiness('登录参数有误');
        }
        $this->loginData = $data;
    }


}