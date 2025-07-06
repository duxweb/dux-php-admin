<?php

namespace App\System\Admin;

use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Member\Models\MemberUser;
use App\Community\Models\CommunityContent;
use App\Content\Models\Article;
use App\Community\Enum\ContentEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Core\App;

#[Resource(app: 'admin',  route: '/system/home', name: 'system.home', actions: false)]
class Home
{
    #[Action(methods: 'GET', route: '/info', name: 'info')]
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // 获取时间范围
        $now = now();
        $sevenDaysAgo = now()->subDays(7);
        $fourteenDaysAgo = now()->subDays(14);

        // 查询用户总数和增长率
        $userTotal = MemberUser::query()->count();
        $userThisWeek = MemberUser::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('created_at', '<', $now)
            ->count();
        $userLastWeek = MemberUser::query()
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();
        $userGrowth = $userLastWeek > 0 ? round(($userThisWeek - $userLastWeek) / $userLastWeek * 100, 2) : 0;

        // 查询笔记总数和增长率
        $noteTotal = CommunityContent::query()->where('type', ContentEnum::NOTE->value)->count();
        $noteThisWeek = CommunityContent::query()
            ->where('type', ContentEnum::NOTE->value)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('created_at', '<', $now)
            ->count();
        $noteLastWeek = CommunityContent::query()
            ->where('type', ContentEnum::NOTE->value)
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();
        $noteGrowth = $noteLastWeek > 0 ? round(($noteThisWeek - $noteLastWeek) / $noteLastWeek * 100, 2) : 0;

        // 查询招求租总数和增长率
        $rentTotal = CommunityContent::query()->where('type', ContentEnum::RENT->value)->count();
        $rentThisWeek = CommunityContent::query()
            ->where('type', ContentEnum::RENT->value)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('created_at', '<', $now)
            ->count();
        $rentLastWeek = CommunityContent::query()
            ->where('type', ContentEnum::RENT->value)
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();
        $rentGrowth = $rentLastWeek > 0 ? round(($rentThisWeek - $rentLastWeek) / $rentLastWeek * 100, 2) : 0;

        // 查询文章总数和增长率
        $articleTotal = Article::query()->count();
        $articleThisWeek = Article::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('created_at', '<', $now)
            ->count();
        $articleLastWeek = Article::query()
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();
        $articleGrowth = $articleLastWeek > 0 ? round(($articleThisWeek - $articleLastWeek) / $articleLastWeek * 100, 2) : 0;

        $data = [
            'user' => [
                'total' => $userTotal,
                'growth' => abs($userGrowth),
                'this_week' => $userThisWeek,
                'last_week' => $userLastWeek,
                'type' => $userGrowth >= 0 ? 'up' : 'down'
            ],
            'note' => [
                'total' => $noteTotal,
                'growth' => abs($noteGrowth),
                'this_week' => $noteThisWeek,
                'last_week' => $noteLastWeek,
                'type' => $noteGrowth >= 0 ? 'up' : 'down'
            ],
            'rent' => [
                'total' => $rentTotal,
                'growth' => abs($rentGrowth),
                'this_week' => $rentThisWeek,
                'last_week' => $rentLastWeek,
                'type' => $rentGrowth >= 0 ? 'up' : 'down'
            ],
            'article' => [
                'total' => $articleTotal,
                'growth' => abs($articleGrowth),
                'this_week' => $articleThisWeek,
                'last_week' => $articleLastWeek,
                'type' => $articleGrowth >= 0 ? 'up' : 'down'
            ]
        ];

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/user/trend', name: 'user.trend')]
    public function userTrend(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // 获取最近30天的日期范围
        $end = now()->endOfDay();
        $start = now()->subDays(29)->startOfDay();

        // 查询每天的新增用户数
        $trends = MemberUser::query()
            ->select([
                App::db()->getConnection()->raw('DATE(created_at) as date'),
                App::db()->getConnection()->raw('COUNT(*) as count')
            ])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 生成完整的30天日期数组
        $dates = [];
        $counts = [];
        $current = Carbon::parse($start);

        while ($current <= $end) {
            $currentDate = $current->format('Y-m-d');
            $dates[] = $currentDate;

            // 查找当天的数据
            $dayData = $trends->firstWhere('date', $currentDate);
            $counts[] = $dayData ? $dayData->count : 0;

            $current->addDay();
        }

        $data = [
            'labels' => $dates,
            'datasets' => [
                [
                    'title' => '新增用户数',
                    'data' => $counts
                ]
            ]
        ];

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/content/trend', name: 'content.trend')]
    public function contentTrend(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // 获取最近30天的日期范围
        $end = now()->endOfDay();
        $start = now()->subDays(29)->startOfDay();

        // 查询每天的笔记和招求租数量
        $trends = CommunityContent::query()
            ->select([
                App::db()->getConnection()->raw('DATE(created_at) as date'),
                'type',
                App::db()->getConnection()->raw('COUNT(*) as count')
            ])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        // 生成完整的30天日期数组
        $dates = [];
        $noteCounts = [];
        $rentCounts = [];
        $current = Carbon::parse($start);

        while ($current <= $end) {
            $currentDate = $current->format('Y-m-d');
            $dates[] = $currentDate;

            // 查找当天的笔记数据
            $noteDayData = $trends->first(function ($item) use ($currentDate) {
                return $item->date === $currentDate && $item->type === ContentEnum::NOTE->value;
            });
            $noteCounts[] = $noteDayData ? $noteDayData->count : 0;

            // 查找当天的招求租数据
            $rentDayData = $trends->first(function ($item) use ($currentDate) {
                return $item->date === $currentDate && $item->type === ContentEnum::RENT->value;
            });
            $rentCounts[] = $rentDayData ? $rentDayData->count : 0;

            $current->addDay();
        }

        $data = [
            'labels' => $dates,
            'datasets' => [
                [
                    'title' => '笔记数量',
                    'data' => $noteCounts
                ],
                [
                    'title' => '招求租数量',
                    'data' => $rentCounts
                ]
            ]
        ];

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/content/top', name: 'content.top')]
    public function contentTop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = 10;

        // 构建查询
        $query = CommunityContent::query()
            ->with(['user' => function ($query) {
                $query->select('id', 'nickname', 'avatar');
            }])
            ->select([
                'id',
                'user_id',
                'content',
                'image',
                'video',
                'comment',
                'praise',
                'collect',
                'created_at'
            ]);

        // 根据不同排序方式设置排序



        switch ($params['key']) {
            case 'comment':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('comment');
                } else {
                    $query->orderBy('comment');
                }
                break;
            case 'praise':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('praise');
                } else {
                    $query->orderBy('praise');
                }
                break;
            case 'collect':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('collect');
                } else {
                    $query->orderBy('collect');
                }
                break;
            default:
                $query->orderByDesc('comment');
        }

        $list = $query->limit($limit)->get();

        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->id,
                'cover' => $item->cover_image,
                'content' => mb_substr($item->content, 0, 50),
                'comment' => $item->comment,
                'praise' => $item->praise,
                'collect' => $item->collect,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $item->user->id,
                    'nickname' => $item->user->nickname,
                    'avatar' => $item->user->avatar,
                ]
            ];
        }

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/user/top', name: 'user.top')]
    public function userTop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = 9;

        // 构建用户查询
        $query = MemberUser::query()
            ->select([
                'id',
                'nickname',
                'avatar',
                'tel',
                'fans_count',
            ])
            ->withCount([
                'contents as note_count' => function ($query) {
                    $query->where('type', ContentEnum::NOTE->value);
                },
                'contents as rent_count' => function ($query) {
                    $query->where('type', ContentEnum::RENT->value);
                }
            ]);

        // 根据不同排序方式设置排序
        switch ($params['key']) {
            case 'note_count':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('note_count');
                } else {
                    $query->orderBy('note_count');
                }
                break;
            case 'rent_count':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('rent_count');
                } else {
                    $query->orderBy('rent_count');
                }
                break;
            case 'fans_count':
                if ($params['order'] === 'desc') {
                    $query->orderByDesc('fans_count');
                } else {
                    $query->orderBy('fans_count');
                }
                break;
            default:
                $query->orderByDesc('fans_count');
        }

        $list = $query->limit($limit)->get();

        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->id,
                'nickname' => $item->nickname ?: '-',
                'avatar' => $item->avatar,
                'tel' => $item->format_tel,
                'fans_count' => $item->fans_count,
                'note_count' => $item->note_count,
                'rent_count' => $item->rent_count,
            ];
        }

        return send($response, "ok", $data);
    }
}
