<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CompressResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(\App\Http\Middleware\CompressResponse::class)
            ->get('/__test/compress', function () {
                return response('<html>'.str_repeat('a', 2000).'</html>');
            });
    }

    public function test_compresses_response_when_client_supports_gzip(): void
    {
        $response = $this->withHeaders(['Accept-Encoding' => 'gzip'])->get('/__test/compress');
        $response->assertHeader('Content-Encoding', 'gzip');
        $this->assertLessThan(2000, strlen($response->getContent()));
    }

    public function test_does_not_compress_small_responses(): void
    {
        Route::middleware(\App\Http\Middleware\CompressResponse::class)
            ->get('/__test/small', fn () => response('ok'));

        $response = $this->withHeaders(['Accept-Encoding' => 'gzip'])->get('/__test/small');
        $response->assertHeaderMissing('Content-Encoding');
    }

    public function test_does_not_compress_when_client_does_not_accept_gzip(): void
    {
        $response = $this->get('/__test/compress');
        $response->assertHeaderMissing('Content-Encoding');
    }
}
