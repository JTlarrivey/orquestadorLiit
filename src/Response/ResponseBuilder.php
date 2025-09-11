<?php

namespace App\Response;

use App\Template\Template;
use App\Model\UserContext;

class ResponseBuilder
{
    public function buildResponse(Template $template, array $data, UserContext $user): array
    {
        return [
            'template' => [
                'name' => $template->name,
                'path' => $template->path,
                'config' => $template->config
            ],
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'jerarquia' => $user->jerarquia
            ],
            'data' => $data
        ];
    }
}
