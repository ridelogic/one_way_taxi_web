@extends('admin.layout')

@section('title', $district->name.' · FAQs')

@section('content')
    @include('admin.districts._header')

    <section class="acard" style="margin-top:16px">
        <h2 class="acard__title"><x-icon name="help-circle" :size="18" /> Questions &amp; answers on the {{ $district->name }} page</h2>
        <p class="acard__help">Active FAQs are shown on the page (lowest order first) and shared with Google as FAQ information.</p>

        @forelse ($faqs as $faq)
            <form method="POST" action="{{ route('admin.district-faqs.update', $faq) }}" class="row-form">
                @csrf @method('PUT')
                <div class="row-form__fields">
                    <div class="afield row-form__wide"><label>Question</label><input name="question" value="{{ $faq->question }}" required maxlength="200"></div>
                    <div class="afield row-form__wide"><label>Answer</label><textarea name="answer" rows="2" required maxlength="2000">{{ $faq->answer }}</textarea></div>
                    <div class="afield"><label>Order</label><input type="number" name="sort_order" value="{{ $faq->sort_order }}" min="0"></div>
                    <label class="acheck"><input type="checkbox" name="is_active" value="1" @checked($faq->is_active)> Active</label>
                </div>
                <div class="row-form__actions">
                    <button class="abtn abtn--sm abtn--primary" type="submit">Save</button>
                    <button class="abtn abtn--sm abtn--danger" type="submit" form="del-faq-{{ $faq->id }}">Delete</button>
                </div>
            </form>
            <form id="del-faq-{{ $faq->id }}" method="POST" action="{{ route('admin.district-faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">@csrf @method('DELETE')</form>
        @empty
            <p class="empty">No FAQs yet.</p>
        @endforelse

        <form method="POST" action="{{ route('admin.districts.faqs.store', $district) }}" class="row-form row-form--new">
            @csrf
            <h3 class="acard__title">Add a question</h3>
            <div class="row-form__fields">
                <div class="afield row-form__wide"><label for="nf_q">Question</label><input id="nf_q" name="question" value="{{ old('question') }}" required maxlength="200"></div>
                <div class="afield row-form__wide"><label for="nf_a">Answer</label><textarea id="nf_a" name="answer" rows="2" required maxlength="2000">{{ old('answer') }}</textarea></div>
                <div class="afield"><label for="nf_o">Order</label><input id="nf_o" type="number" name="sort_order" value="{{ $faqs->count() + 1 }}" min="0"></div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="row-form__actions"><button class="abtn abtn--primary" type="submit">Add FAQ</button></div>
        </form>
    </section>
@endsection
