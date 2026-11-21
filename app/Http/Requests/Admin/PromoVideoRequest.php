<?php

namespace App\Http\Requests\Admin;

use App\Models\DistrictPage;
use App\Models\PromoVideo;
use App\Services\YouTubeEmbedChecker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PromoVideoRequest extends AdminRequest
{
    /** YouTube's answer for the submitted video (set during validation). @var array{status: string, message: string, title: ?string, author: ?string}|null */
    public ?array $embedCheck = null;

    /** Columns to store with the video: the embed check result. @return array<string, mixed> */
    public function embedAttributes(): array
    {
        return $this->embedCheck ? ['embed_status' => $this->embedCheck['status'], 'embed_checked_at' => now()] : [];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'youtube_url' => ['required', 'string', 'max:255'],
            'youtube_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{11}$/'],
            'thumbnail_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'category' => ['required', Rule::in(array_keys(PromoVideo::CATEGORIES))],
            'placement' => ['nullable', Rule::in(['home', 'districts', 'both'])],
            'show_on_home' => ['boolean'],
            'home_section' => ['required', Rule::in(array_keys(PromoVideo::HOME_SECTIONS))],
            'show_on_districts' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'district_ids' => ['nullable', 'array', 'max:'.max(1, DistrictPage::count())], // every district / UT page at most
            'district_ids.*' => ['integer', 'distinct', Rule::exists('district_pages', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Simple placement choice (Homepage / Selected districts / Homepage + selected districts) sets the two flags.
        $placement = $this->input('placement');
        if (in_array($placement, ['home', 'districts', 'both'], true)) {
            $this->merge(['show_on_home' => $placement !== 'districts', 'show_on_districts' => $placement !== 'home']);
        }
        $this->booleans(['show_on_home', 'show_on_districts', 'is_active']);
        $this->merge([
            'youtube_url' => trim($this->text('youtube_url')),
            // Extracted server-side from the URL — never taken from the form.
            'youtube_id' => PromoVideo::parseYoutubeId($this->text('youtube_url')) ?? '',
            'home_section' => array_key_exists($this->text('home_section'), PromoVideo::HOME_SECTIONS) ? $this->text('home_section') : 'featured',
            'sort_order' => (int) $this->input('sort_order', 0),
            'district_ids' => array_values(array_unique(array_map('intval', array_filter((array) $this->input('district_ids', []), 'is_numeric')))),
        ]);
    }

    /** Placement rules: somewhere to show it, and at most 10 active videos per homepage section and per district. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Ask YouTube whether visitors can actually play this video on our site (public + embedding allowed).
            $this->embedCheck = app(YouTubeEmbedChecker::class)->check((string) $this->input('youtube_id'));
            if (in_array($this->embedCheck['status'], [YouTubeEmbedChecker::BLOCKED, YouTubeEmbedChecker::UNAVAILABLE], true)) {
                $validator->errors()->add('youtube_id', $this->embedCheck['message'].' Please choose a public video that allows embedding.');

                return;
            }

            if (! $this->boolean('is_active')) {
                return;
            }

            $current = $this->route('video')?->id;
            $max = PromoVideo::MAX_ACTIVE_PER_PLACEMENT;
            $districtIds = $this->input('district_ids', []);

            if (! $this->boolean('show_on_home') && (! $this->boolean('show_on_districts') || $districtIds === [])) {
                $validator->errors()->add('show_on_home', 'Choose where the video appears: the homepage, or at least one district.');

                return;
            }

            if ($this->boolean('show_on_home')) {
                $homeCount = PromoVideo::active()->where('show_on_home', true)->where('home_section', $this->input('home_section'))
                    ->when($current, fn ($q) => $q->whereKeyNot($current))->count();
                if ($homeCount >= $max) {
                    $validator->errors()->add('show_on_home', "This homepage section already has {$max} active videos. Deactivate one or choose the other section.");
                }
            }

            if ($this->boolean('show_on_districts') && $districtIds !== []) {
                $full = DistrictPage::whereIn('id', $districtIds)
                    ->withCount(['videos as active_videos' => fn ($q) => $q->where('is_active', true)->where('show_on_districts', true)
                        ->when($current, fn ($q) => $q->whereKeyNot($current))])
                    ->get()->filter(fn ($d) => $d->active_videos >= $max)->pluck('name');
                if ($full->isNotEmpty()) {
                    $validator->errors()->add('district_ids', "These districts already have {$max} active videos: ".$full->join(', ').'. Deactivate one there first.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'youtube_id.required' => 'Enter a valid YouTube video link — for example https://www.youtube.com/watch?v=…, https://youtu.be/… or a YouTube Shorts link.',
            'youtube_id.regex' => 'Enter a valid YouTube video link.',
        ];
    }

    protected function fieldNames(): array
    {
        return [
            'youtube_url' => 'YouTube link',
            'thumbnail_media_id' => 'custom thumbnail',
            'district_ids' => 'districts',
            'district_ids.*' => 'district',
        ];
    }
}
