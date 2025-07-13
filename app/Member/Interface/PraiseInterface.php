<?php

declare(strict_types=1);

namespace App\Member\Interface;


interface PraiseInterface
{

    /**
     * 点赞回调
     * @param int $hasId
     * @param bool $status
     * @return void
     */
    public function callback(int $hasId, bool $status);

}
