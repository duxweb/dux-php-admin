<?php

declare(strict_types=1);

namespace App\Member\Interface;

use App\Member\Models\MemberAssess;

interface AssessInterface
{

    /**
     * 评价回调
     * @param int $hasId
     * @param float $score
     * @return void
     */
    public function callback(int $hasId, float $score, MemberAssess $info);


    /**
     * 格式化数据
     * @param object $item
     * @return array
     */
    public function format(object $item): array;

    /**
     * 获取内容
     * @param int $id
     * @return array {
     *  title: string,
     *  desc: string,
     *  image: string,
     * }
     */
    public function content(int $id): array;

}
