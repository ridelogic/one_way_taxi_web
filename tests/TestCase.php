<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /** YouTube oEmbed answer used by the fake below: an HTTP status, or 0 for "YouTube unreachable". */
    protected int $youtubeOembedStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        // Views render without needing a compiled Vite manifest.
        $this->withoutVite();

        // Never call YouTube from tests: the embed check gets a fake answer (public + embeddable by default).
        Http::fake(['www.youtube.com/oembed*' => function () {
            if ($this->youtubeOembedStatus === 0) {
                throw new ConnectionException('YouTube unreachable (test)');
            }

            return $this->youtubeOembedStatus === 200
                ? Http::response(['title' => 'Test video', 'author_name' => 'Test channel'], 200)
                : Http::response('', $this->youtubeOembedStatus);
        }]);
    }
}
