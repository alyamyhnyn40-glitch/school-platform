<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response};
use App\Services\AssistantService as A;

final class AssistantController
{
    public function ask(Request $r): Response
    {
        $h = $r->input('history'); $hist = is_array($h) ? array_values(array_filter($h, 'is_array')) : [];
        return Response::ok(A::ask((string)$r->input('message', ''), $hist, (string)$r->input('path', '')));
    }
    public function suggestions(Request $r): Response { return Response::ok(['items' => A::suggestions()]); }
}
