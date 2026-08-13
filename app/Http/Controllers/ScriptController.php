<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ScriptController extends Controller
{
    public function __invoke(): Response
    {
        $paths = [
            base_path('public/script.js'),
            public_path('script.js'),
            dirname(__DIR__, 3) . '/public/script.js',
        ];

        $content = null;
        foreach ($paths as $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                break;
            }
        }

        if ($content === null) {
            return response('// script.js not found', 404, [
                'Content-Type' => 'application/javascript',
            ]);
        }

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=3600',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
