@if ($faqs->isNotEmpty())
<section class="section bg-ivory" id="faqs" aria-labelledby="faq-title">
    <div class="container-site max-w-3xl">
        <x-section-heading eyebrow="Help centre" :title="$section->title" :subtitle="$section->subtitle" id="faq-title" />
        <x-faq-list :faqs="$faqs" />
        <div class="mt-8 text-center"><a href="{{ route('faqs') }}" class="btn btn-outline">View all FAQs <x-icon name="arrow-right" :size="18" /></a></div>
    </div>
</section>
@endif
