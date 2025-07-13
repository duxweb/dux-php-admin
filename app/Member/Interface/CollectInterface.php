<?php

declare(strict_types=1);

namespace App\Member\Interface;


interface CollectInterface
{

    /**
     * 收藏和取消收藏回调
     * @param int $hasId
     * @param bool $status
     * @return void
     */
    public function callback(int $hasId, bool $status);


    /**
     * 格式化数据
     * @param object $item
     * @return array {
     *  title: string,
     *  image: string,
     * }
     */
    public function format(object $item): array;


}
