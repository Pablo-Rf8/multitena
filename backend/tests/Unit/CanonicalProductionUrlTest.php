<?php

namespace Tests\Unit;

use App\Http\Middleware\CanonicalProductionUrl;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CanonicalProductionUrlTest extends TestCase
{
    public function test_it_keeps_previous_urls_on_the_public_https_origin(): void
    {
        $environment = $this->app['env'];
        $appUrl = config('app.url');
        $this->app->instance('env', 'production');
        config()->set('app.url', 'https://multitena.example');

        try {
            $request = Request::create('http://internal.railway/branding', 'GET', server: [
                'HTTP_REFERER' => 'http://internal.railway/branding',
            ]);
            $response = (new CanonicalProductionUrl)->handle($request, fn (Request $request) => new Response($request->fullUrl()));

            $this->assertSame('https://multitena.example/branding', $response->getContent());
            $this->assertNull($request->headers->get('referer'));
        } finally {
            $this->app->instance('env', $environment);
            config()->set('app.url', $appUrl);
        }
    }
}
