<?php

namespace App\Member\Api;

use Core\Docs\Attribute\Api;
use Core\Docs\Attribute\Docs;
use Core\Docs\Attribute\Payload;
use Core\Docs\Attribute\ResultData;
use Core\Docs\Enum\FieldEnum;
use Core\Route\Attribute\Route;
use Core\Route\Attribute\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[RouteGroup(app: 'apiMember', route: '/member/upload')]
#[Docs(name: '文件上传')]
class Upload extends \App\System\Extends\Upload
{

    #[Route(methods: 'POST', route: '/sign')]
    #[Api(name: '上传签名', payloadExample: ['filename' => 'image.jpg'])]
    #[Payload(field: 'filename', type: FieldEnum::STRING, name: '文件名', desc: '要上传的文件名')]
    #[Payload(field: 'mime', type: FieldEnum::STRING, name: '文件类型', desc: '要上传的文件类型')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '上传签名', desc: '文件上传签名信息', root: true)]
    public function sign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        $auth = $request->getAttribute('auth');

        return send($response, "ok", parent::uploadSign(filename: $data['filename'], mime: $data['mime'], prefix: 'member/' . $auth["id"]));
    }

    #[Route(methods: 'POST', route: '')]
    #[Api(name: '上传文件')]
    #[Payload(field: 'mime', type: FieldEnum::STRING, name: '文件类型', required: false, desc: '文件MIME类型')]
    #[ResultData(field: 'data', type: FieldEnum::OBJECT, name: '上传结果', desc: '文件上传结果信息', children: [
        new ResultData(field: 'url', type: FieldEnum::STRING, name: '文件URL', desc: '上传文件的访问地址'),
        new ResultData(field: 'size', type: FieldEnum::INT, name: '文件大小', desc: '文件大小（字节）'),
        new ResultData(field: 'name', type: FieldEnum::STRING, name: '文件名', desc: '文件名称')
    ], root: true)]
    public function upload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $data = $request->getParsedBody();
        $info = parent::uploadStorage(
            hasType: 'member',
            request: $request,
            manager: true,
            mime: $data['mime'],
        );
        return send($response, "ok", $info);

    }

}