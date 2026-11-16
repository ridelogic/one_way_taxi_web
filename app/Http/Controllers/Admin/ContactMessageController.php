<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');

        return view('admin.messages.index', [
            'messages' => ContactMessage::query()
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('message', 'like', "%{$term}%")))
                ->when($request->query('filter') === 'unread', fn ($q) => $q->whereNull('read_at'))
                ->latest()->paginate(25)->withQueryString(),
            'search' => $term,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Message deleted.');
    }
}
