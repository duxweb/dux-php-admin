<?php

namespace App\Member\Api;

use App\Member\Event\PraiseEvent;
use App\Member\Models\MemberFeedback;
use App\Member\Models\MemberPraise;
use Core\App;
use Core\Auth\AuthService;
use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Enum\FieldEnum;
use Core\Handlers\ExceptionBusiness;
use Core\Handlers\ExceptionNotFound;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Core\Validator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;

#[RouteGroup(app: 'apiMember', route: '/member/feedback')]
#[Docs(name: '反馈管理')]
class Feedback
{

    #[Route(methods: 'POST', route: '')]
    #[Api(name: '提交反馈', payloadExample: ['content' => '反馈内容', 'images' => ['image1.jpg', 'image2.jpg']])]
    #[Payload(field: 'content', type: FieldEnum::STRING, name: '反馈内容', desc: '反馈的文字内容')]
    #[Payload(field: 'images', type: FieldEnum::ARRAY, name: '反馈图片', required: false, desc: '反馈的图片列表')]
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $params = $request->getParsedBody() ?: [];

        $data = Validator::parser($params, [
            "content" => ["required", "请输入反馈内容"],
        ]);

        $lastInfo = MemberFeedback::query()->where('user_id', $auth['id'])->orderByDesc('id')->first();


        $time = now();
        if ($lastInfo && $lastInfo->created_at->addHour()->gte($time)) {
            throw new ExceptionBusiness('反馈太频繁，请隔段时间再试');
        }
        MemberFeedback::query()->create([
            'user_id' => $auth['id'],
            'content' => $data->content,
            'images' => is_array($data->images) ? $data->images : [],
        ]);

        return send($response, "反馈成功");
    }
}