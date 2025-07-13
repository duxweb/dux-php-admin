<?php

namespace App\Member\Listener;

use App\Member\Event\PraiseEvent;
use App\Member\Event\StatsEvent;
use App\Member\Models\MemberCollect;
use App\Member\Models\MemberComment;
use App\Member\Models\MemberFoot;
use App\Member\Models\MemberNotice;
use App\Member\Models\MemberNoticeRead;
use Core\Event\Attribute\Listener;
use App\Member\Interface\PraiseInterface;

class MemberListener
{
    #[Listener(name: 'member.stats')]
    public function data(StatsEvent $event): void
    {
        $ids = MemberNotice::query()->where('user_id', $event->userId)->orWhere('type', 1)->pluck('id')->toArray();
        $noticeIds = MemberNoticeRead::query()->whereIn('notice_id', $ids)->where('user_id', $event->userId)->pluck('notice_id')->toArray();
        $result = array_diff($ids, $noticeIds);

        $event->setMap('member', [
            'notice' => count($result),
            'collect' => MemberCollect::query()->where('user_id', $event->userId)->count(),
            'foot' => MemberFoot::query()->where('user_id', $event->userId)->count(),
        ]);
    }

    #[Listener(name: 'member.praise')]
    public function praise(PraiseEvent $event): void
    {
        $event->setMap('评论', 'comment',  MemberComment::class, new class implements PraiseInterface {
            public function callback(int $id, bool $status): void
            {
                if ($status) {
                    MemberComment::query()->where('id', $id)->increment('praise');
                } else {
                    MemberComment::query()->where('id', $id)->decrement('praise');
                }
            }
        });
    }

}