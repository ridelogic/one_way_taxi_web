@extends('admin.layout')

@section('title', 'Testimonials')

@section('content')
    <div class="page-head">
        <p>Only publish genuine customer feedback. Entries marked “sample” are labelled as such on the website.</p>
        <a class="abtn abtn--primary" href="{{ route('admin.testimonials.create') }}"><x-icon name="plus" :size="16" /> Add testimonial</a>
    </div>
    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th>Customer</th><th>Quote</th><th>Rating</th><th>Type</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($testimonials as $t)
                    <tr>
                        <td class="strong">{{ $t->customer_name }}<br><small class="muted">{{ $t->trip }}</small></td>
                        <td>{{ \Illuminate\Support\Str::limit($t->quote, 90) }}</td>
                        <td>{{ $t->rating ? $t->rating.'/5' : '—' }}</td>
                        <td>@if ($t->is_sample)<span class="abadge abadge--amber">Sample</span>@else<span class="abadge abadge--green">Genuine</span>@endif</td>
                        <td><x-admin.badge :on="$t->is_active" /></td>
                        <td class="actions">
                            <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.testimonials.edit', $t) }}">Edit</a>
                            <x-admin.delete :action="route('admin.testimonials.destroy', $t)" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No testimonials yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
