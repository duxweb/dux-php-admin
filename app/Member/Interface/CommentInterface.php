<?php

declare(strict_types=1);

namespace App\Member\Interface;

use App\Member\Models\MemberComment;

interface CommentInterface
{

    /**
     * 收藏和取消收藏回调
     * @param int $hasId
     * @param bool $status
     * @return void
     */
    public function callback(int $hasId, MemberComment $info);


}
