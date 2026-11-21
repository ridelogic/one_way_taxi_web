<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Asks YouTube itself whether a video can be played in an embedded player, using its public oEmbed endpoint
 * (no API key, no sign-in). YouTube answers 200 for public, embeddable videos, 401 when the owner has disabled
 * embedding, and 400/403/404 for invalid, private or deleted videos. This never tries to get around any restriction —
 * it only reports what YouTube allows, so the admin can fix the link before visitors see an error.
 */
class YouTubeEmbedChecker
{
    public const OK = 'ok';

    public const BLOCKED = 'blocked';

    public const UNAVAILABLE = 'unavailable';

    public const UNKNOWN = 'unknown';

    /** @return array{status: string, message: string, title: ?string, author: ?string} */
    public function check(string $youtubeId): array
    {
        try {
            $response = Http::timeout(6)->acceptJson()->get('https://www.youtube.com/oembed', [
                'url' => 'https://www.youtube.com/watch?v='.$youtubeId,
                'format' => 'json',
            ]);
        } catch (Throwable) {
            return $this->result(self::UNKNOWN, 'YouTube could not be reached to verify this video. It was not checked.');
        }

        return match (true) {
            $response->successful() => $this->result(self::OK, 'Public video — it can be played on the website.', $response->json('title'), $response->json('author_name')),
            $response->status() === 401 => $this->result(self::BLOCKED, 'The video owner has turned off embedding, so it cannot be played on other websites.'),
            in_array($response->status(), [400, 403, 404], true) => $this->result(self::UNAVAILABLE, 'This video is private, deleted or does not exist.'),
            default => $this->result(self::UNKNOWN, 'YouTube did not confirm this video right now. It was not checked.'),
        };
    }

    /** @return array{status: string, message: string, title: ?string, author: ?string} */
    private function result(string $status, string $message, mixed $title = null, mixed $author = null): array
    {
        return ['status' => $status, 'message' => $message, 'title' => is_string($title) ? $title : null, 'author' => is_string($author) ? $author : null];
    }
}
