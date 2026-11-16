<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', ['services' => Service::with('media')->ordered()->get()]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['is_active' => true, 'show_on_home' => true, 'icon' => 'car'])]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::create($this->payload($request));

        return redirect()->route('admin.services.edit', $service)->with('status', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($this->payload($request));

        return redirect()->route('admin.services.edit', $service)->with('status', 'Service saved.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Service deleted. Remove any menu links that pointed to it in Navigation.');
    }

    /** @return array<string, mixed> */
    private function payload(ServiceRequest $request): array
    {
        $data = $request->validated();
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? '');

        return $data;
    }
}
