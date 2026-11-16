@extends('layouts.app')

@push('head')
    @if ($faqs->isNotEmpty())
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer]])->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
@endpush

@section('content')
    <x-page-banner title="Frequently Asked Questions" subtitle="Answers about booking, fares, vehicles and support." :crumbs="['FAQs' => null]" />

    <section class="section pt-12!">
        <div class="container-site max-w-3xl">
            @forelse ($groups as $category => $items)
                <h2 class="mt-10 mb-4 flex items-center gap-2 text-xl font-bold first:mt-0"><span class="h-1.5 w-6 rounded-full bg-gold"></span>{{ $category }}</h2>
                <x-faq-list :faqs="$items" />
            @empty
                <p class="text-lg text-ink-soft">No questions have been published yet.</p>
            @endforelse

            <div class="card mt-12 p-8 text-center">
                <h2 class="text-xl font-bold">Still have a question?</h2>
                <p class="mt-2 text-ink-soft">Our team is happy to help.</p>
                <a href="{{ route('contact') }}" class="btn btn-gold mt-5">Contact us <x-icon name="arrow-right" :size="18" /></a>
            </div>
        </div>
    </section>
@endsection
