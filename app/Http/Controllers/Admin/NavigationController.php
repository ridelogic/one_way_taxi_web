<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NavigationItemRequest;
use App\Models\NavigationItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NavigationController extends Controller
{
    public function index(): View
    {
        return view('admin.navigation.index', [
            'menus' => NavigationItem::orderBy('sort_order')->get()->groupBy('menu'),
        ]);
    }

    public function create(): View
    {
        return view('admin.navigation.form', ['item' => new NavigationItem(['menu' => $this->queryText(request(), 'menu') ?: 'header', 'is_active' => true])]);
    }

    public function store(NavigationItemRequest $request): RedirectResponse
    {
        NavigationItem::create($request->validated());

        return redirect()->route('admin.navigation.index')->with('status', 'Menu link added.');
    }

    public function edit(NavigationItem $item): View
    {
        return view('admin.navigation.form', ['item' => $item]);
    }

    public function update(NavigationItemRequest $request, NavigationItem $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('admin.navigation.index')->with('status', 'Menu link saved.');
    }

    public function destroy(NavigationItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('admin.navigation.index')->with('status', 'Menu link deleted.');
    }
}
