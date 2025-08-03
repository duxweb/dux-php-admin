<?php

namespace App\System\Admin;

use Core\Resources\Attribute\Action;
use Core\Resources\Attribute\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\System\Models\SystemUser;
use App\System\Models\SystemMemo;
use App\System\Models\SystemNotice;
use App\System\Models\SystemBulletin;
use App\System\Models\LogLogin;
use App\System\Models\LogOperate;
use App\System\Service\Config;
use Carbon\Carbon;
use Core\App;
use Core\Handlers\ExceptionBusiness;

#[Resource(app: 'admin', route: '/system/home', name: 'system.home', actions: false)]
class Home
{
    #[Action(methods: 'GET', route: '/stats', name: 'stats')]
    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        $today = now()->format('Y-m-d');

        // 备忘录统计
        $memoTotal = SystemMemo::query()
            ->where('user_has', SystemUser::class)
            ->where('user_id', $userId)
            ->count();
        
        $memoPending = SystemMemo::query()
            ->where('user_has', SystemUser::class)
            ->where('user_id', $userId)
            ->where('is_completed', false) // 未完成的备忘录
            ->count();

        // 今日操作统计
        $todayOperate = LogOperate::query()
            ->whereDate('created_at', $today)
            ->where('user_type', SystemUser::class)
            ->where('user_id', $userId)
            ->count();

        // 登录记录统计
        $loginTotal = LogLogin::query()
            ->where('user_type', SystemUser::class)
            ->where('user_id', $userId)
            ->count();

        // 最近操作统计（最近7天）
        $recentOperate = LogOperate::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('user_type', SystemUser::class)
            ->where('user_id', $userId)
            ->count();

        // 未读通知数量
        $unreadNotices = SystemNotice::query()
            ->where('user_has', SystemUser::class)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        // 系统公告总数（当前用户可见）
        $bulletinTotal = SystemBulletin::query()
            ->where('status', 1) // 已发布状态
            ->count();

        $data = [
            'memo' => [
                'total' => $memoTotal,
                'pending' => $memoPending
            ],
            'operate' => [
                'today' => $todayOperate,
                'recent' => $recentOperate
            ],
            'login' => [
                'total' => $loginTotal
            ],
            'notice' => [
                'unread' => $unreadNotices
            ],
            'bulletin' => [
                'total' => $bulletinTotal
            ]
        ];

        $system = Config::getJsonValue('system');

        $data['info'] = [
            'title' => $system['title'],
            'copyright' => $system['copyright'],
            'website' => $system['website'],
        ];

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/profile', name: 'profile')]
    public function profile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];

        // 获取用户信息
        $user = SystemUser::query()->find($userId);
        if (!$user) {
            throw new ExceptionBusiness('用户不存在');
        }

        // 获取最后登录时间
        $lastLogin = LogLogin::query()
            ->where('user_type', SystemUser::class)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        // 格式化时间 - 使用Laravel的人性化时间格式
        $loginAtFormatted = $lastLogin?->created_at?->locale('zh')->diffForHumans() ?? '从未登录';
        $createdAtFormatted = $user->created_at?->locale('zh')->diffForHumans() ?? '-';

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_name' => $user->role?->name ?? '管理员',
            'dept_name' => $user->dept?->name ?? '',
            'status' => $user->status,
            'status_text' => $user->status ? '正常' : '禁用',
            'login_at' => $lastLogin?->created_at?->format('Y-m-d H:i:s'),
            'login_at_formatted' => $loginAtFormatted,
            'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $createdAtFormatted,
        ];

        return send($response, "ok", $data);
    }

    #[Action(methods: 'GET', route: '/operate', name: 'operate')]
    public function operate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $userId = (int)$auth['id'];
        
        // 获取最近7天的日期范围
        $end = now()->endOfDay();
        $start = now()->subDays(6)->startOfDay();

        // 查询每天的操作数据
        $operateData = LogOperate::query()
            ->select([
                App::db()->getConnection()->raw('DATE(created_at) as date'),
                App::db()->getConnection()->raw('COUNT(*) as count')
            ])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->where('user_type', SystemUser::class)
            ->where('user_id', $userId)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 生成完整的7天日期数组
        $dates = [];
        $counts = [];
        $current = Carbon::parse($start);

        while ($current <= $end) {
            $currentDate = $current->format('Y-m-d');
            $dates[] = $current->format('m-d');

            // 查找当天的数据
            $dayData = $operateData->firstWhere('date', $currentDate);
            $counts[] = $dayData ? $dayData->count : 0;

            $current->addDay();
        }

        $data = [
            'labels' => $dates,
            'data' => [
                [
                    'name' => '操作次数',
                    'data' => $counts
                ]
            ],
            'smooth' => true
        ];

        return send($response, "ok", $data);
    }
}
