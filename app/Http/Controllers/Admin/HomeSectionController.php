<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeSectionRequest;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Contracts\View\View;
use App\Support\Ordering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index(): View
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        return view('admin.homepage.index', [
            'page' => $home,
            'sections' => $home->sections()->with('media')->get(),
        ]);
    }

    public function edit(PageSection $section): View
    {
        return view('admin.homepage.edit', [
            'section' => $section,
            'schema' => $section->schema(),
        ]);
    }

    public function update(HomeSectionRequest $request, PageSection $section): RedirectResponse
    {
        $data = $request->validated();
        $schema = $section->schema();
        $input = $data['content'] ?? [];

        // Keep keys that aren't part of the schema; overwrite schema-managed ones with typed values.
        $content = $section->content ?? [];
        foreach ($schema['fields'] ?? [] as $key => $field) {
            $content[$key] = $this->cast($input[$key] ?? null, $field['type']);
        }

        if (isset($schema['items'])) {
            $items = [];
            foreach ($input['items'] ?? [] as $item) {
                $row = [];
                foreach ($schema['items']['fields'] as $key => $field) {
                    $row[$key] = $this->cast($item[$key] ?? null, $field['type']);
                }
                // Skip rows the editor left completely empty.
                if (collect($row)->except('icon')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()) {
                    $items[] = $row;
                }
            }
            $content['items'] = $items;
        }

        $section->update([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'media_id' => isset($schema['media']) ? ($data['media_id'] ?? null) : $section->media_id,
            'content' => $content,
        ]);

        return redirect()->route('admin.homepage.edit', $section)->with('status', ($schema['label'] ?? 'Section').' saved. Changes are live on the homepage.');
    }

    public function toggle(PageSection $section): RedirectResponse
    {
        $section->update(['is_active' => ! $section->is_active]);

        return back()->with('status', ($section->schema()['label'] ?? 'Section').($section->is_active ? ' is now visible.' : ' is now hidden.'));
    }

    public function move(Request $request, PageSection $section): RedirectResponse
    {
        Ordering::move($section, PageSection::where('page_id', $section->page_id)->orderBy('sort_order')->orderBy('id'), $request->input('direction') === 'up' ? -1 : 1);

        return back()->with('status', 'Section order updated.');
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'number', 'media' => $value === null || $value === '' ? null : (int) $value,
            'boolean' => (bool) $value,
            default => $value === null ? '' : trim((string) $value),
        };
    }
}
