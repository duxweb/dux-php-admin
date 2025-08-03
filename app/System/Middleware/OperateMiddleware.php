<?php

namespace App\System\Middleware;

use App\System\Models\LogOperate;
use Core\App;
use donatj\UserAgent\UserAgentParser;
use Core\Handlers\ExceptionBusiness;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class OperateMiddleware
{

    private string $hasType;

    public function __construct(string $hasType)
    {
        $this->hasType = $hasType;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $response = $handler->handle($request);


        if ($method == "GET") {
            return $response;
        }

        if (str_contains($path, "/static")) {
            return $response;
        }

        $auth = $request->getAttribute("auth");
        $id = $auth["id"];
        if (!$id) {
            return $response;
        }

         $method = $request->getMethod();
        if ($method != 'GET' && $method != 'OPTIONS') {
            $example = App::config('use')->get('app.example', false);
            if ($example) {
                throw new ExceptionBusiness('演示模式，操作无效');
            }
        }

        $useragent = $request->getHeaderLine("user-agent");
        $time = round(microtime(true) - $startTime, 3);
        $route = RouteContext::fromRequest($request)->getRoute();
        $parser = new UserAgentParser();
        $ua = $parser->parse($useragent);
        LogOperate::query()->create([
            "user_type" => $this->hasType,
            "user_id" => $id,
            "request_method" => $request->getMethod(),
            "request_url" => $request->getUri(),
            "request_time" => $time,
            "request_params" => $request->getParsedBody(),
            "route_name" => $route->getName(),
            "route_title" => $route->getArgument("route:title"),
            "client_ua" => Str::limit($useragent, 200),
            "client_ip" => get_ip(),
            "client_browser" => $ua->browser() . " " . $ua->browserVersion(),
            "client_device" => $ua->platform(),
        ]);
        return $response;
    }
}
