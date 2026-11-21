<?php

namespace App\Http\Requests\Admin;

use App\Models\PromoAnnouncement;
use Illuminate\Validation\Rule;

class PromoAnnouncementRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:255'],
            // A page on this website ("/book") or a full http(s) address — never javascript:, data: and the like.
            'link_url' => ['nullable', 'string', 'max:500', 'regex:#^(/(?!/)[^\s]*|https?://[^\s]+)$#i'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'target' => ['required', Rule::in(array_keys(PromoAnnouncement::TARGETS))],
            // Location pages are required (at least one) only for the "selected location pages" choices.
            'district_ids' => [Rule::requiredIf(fn () => str_contains((string) $this->input('target'), 'selected')), 'array'],
            'district_ids.*' => ['integer', 'distinct', Rule::exists('district_pages', 'id')],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_enabled' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'link_url.regex' => 'Enter a page on this website starting with / (e.g. /book) or a full address starting with https://.',
            'ends_at.after' => 'The end must be after the start.',
            'district_ids.required' => 'Choose at least one location page, or pick a different display option.',
        ];
    }

    /** @return array<string, string> */
    protected function fieldNames(): array
    {
        return [
            'link_url' => 'link', 'starts_at' => 'start date and time', 'ends_at' => 'end date and time', 'sort_order' => 'display order',
            'is_enabled' => 'status', 'target' => 'display locations', 'district_ids' => 'location pages',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_enabled']);
        $this->merge([
            'title' => trim($this->text('title')),
            'message' => trim($this->text('message')),
            'link_url' => trim($this->text('link_url')) ?: null,
            'starts_at' => $this->text('starts_at') ?: null,
            'ends_at' => $this->text('ends_at') ?: null,
            'target' => $this->text('target') ?: 'home',
            'district_ids' => is_array($this->input('district_ids')) ? array_values($this->input('district_ids')) : [],
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    /** Model attributes: the display choice becomes show_on_home + location_scope. @return array<string, mixed> */
    public function announcementAttributes(): array
    {
        [, $home, $scope] = PromoAnnouncement::TARGETS[$this->validated('target')];

        return array_merge(
            collect($this->validated())->except(['target', 'district_ids'])->all(),
            ['show_on_home' => $home, 'location_scope' => $scope],
        );
    }

    /** Location page ids to keep linked: only for "selected" scopes (cleared otherwise). @return list<int> */
    public function districtIds(): array
    {
        return str_contains($this->validated('target'), 'selected')
            ? array_map('intval', $this->validated('district_ids') ?? [])
            : [];
    }
}
