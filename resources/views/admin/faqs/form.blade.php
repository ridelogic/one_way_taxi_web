@extends('admin.layout')

@section('title', $faq->exists ? 'Edit question' : 'Add question')

@section('content')
    <a href="{{ route('admin.faqs.index') }}">← All FAQs</a>
    <form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="acard" style="margin-top:12px;max-width:860px">
        @csrf
        @if ($faq->exists) @method('PUT') @endif
        <x-admin.input name="question" label="Question" :value="$faq->question" required maxlength="255" />
        <x-admin.textarea name="answer" label="Answer" :value="$faq->answer" rows="6" required />
        <div class="aform-grid">
            <x-admin.input name="category" label="Category" :value="$faq->category" list="faq-categories" required />
            <x-admin.input name="sort_order" label="Order" type="number" min="0" :value="$faq->sort_order" />
        </div>
        <datalist id="faq-categories">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
        <x-admin.checkbox name="is_active" label="Active" :checked="$faq->is_active" />
        <x-admin.checkbox name="show_on_home" label="Show on homepage" :checked="$faq->show_on_home" />
        <button type="submit" class="abtn abtn--primary">{{ $faq->exists ? 'Save question' : 'Add question' }}</button>
        <a href="{{ route('admin.faqs.index') }}" class="abtn">Cancel</a>
    </form>
@endsection
