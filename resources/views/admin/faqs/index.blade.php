@extends('admin.layout')

@section('title', 'FAQs')

@section('content')
    <div class="page-head">
        <p>Questions appear on the FAQs page (grouped by category) and, when ticked, on the homepage.</p>
        <a class="abtn abtn--primary" href="{{ route('admin.faqs.create') }}"><x-icon name="plus" :size="16" /> Add question</a>
    </div>
    @forelse ($faqs as $category => $items)
        <section class="acard atable-wrap" style="padding:0">
            <h2 class="acard__title" style="padding:16px 16px 0">{{ $category }}</h2>
            <table class="atable">
                <thead><tr><th>Question</th><th>Homepage</th><th>Order</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($items as $faq)
                        <tr>
                            <td class="strong">{{ $faq->question }}</td>
                            <td>{{ $faq->show_on_home ? 'Yes' : '—' }}</td>
                            <td>{{ $faq->sort_order }}</td>
                            <td><x-admin.badge :on="$faq->is_active" /></td>
                            <td class="actions">
                                <a class="abtn abtn--sm abtn--primary" href="{{ route('admin.faqs.edit', $faq) }}">Edit</a>
                                <x-admin.delete :action="route('admin.faqs.destroy', $faq)" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <p class="empty">No questions yet.</p>
    @endforelse
@endsection
