@extends('layouts.app')

@section('content')
    <x-page-banner title="Contact Us" subtitle="Questions, quotes or special requests — we’re happy to help." :crumbs="['Contact' => null]" />

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1.2fr_0.8fr]">
            <form method="POST" action="{{ route('contact.store') }}" class="card p-6 sm:p-8" novalidate>
                @csrf
                <h2 class="text-2xl font-bold">Send us a message</h2>
                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-fresh/30 bg-mint px-4 py-3 font-semibold text-forest" role="status">{{ session('status') }}</div>
                @elseif ($errors->any())
                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please correct the highlighted fields.</div>
                @endif
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <x-form.field name="name" label="Your name" icon="user" required>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="phone" label="Mobile number" icon="phone" required>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" placeholder="10-digit mobile" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="email" label="Email (optional)" icon="mail">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="subject" label="Subject (optional)" icon="message">
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" maxlength="150" class="form-control form-control-icon">
                    </x-form.field>
                    <x-form.field name="message" label="Message" required class="sm:col-span-2">
                        <textarea id="message" name="message" required minlength="10" maxlength="2000" rows="5" class="form-control">{{ old('message') }}</textarea>
                    </x-form.field>
                </div>
                <div class="absolute -left-[10000px]" aria-hidden="true"><label for="website">Leave this field empty</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <button type="submit" class="btn btn-gold btn-lg mt-6">Send Message <x-icon name="send" :size="18" /></button>
            </form>

            <aside class="space-y-4">
                @if ($settings->get('phone'))
                    <a href="{{ $settings->telLink() }}" class="card card-hover flex gap-4 p-5"><span class="icon-tile"><x-icon name="phone" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Call us</small><strong class="text-lg text-forest">{{ $settings->get('phone') }}</strong></span></a>
                @endif
                @if ($settings->get('phone_secondary'))
                    <a href="{{ $settings->telLink($settings->get('phone_secondary')) }}" class="card card-hover flex gap-4 p-5"><span class="icon-tile"><x-icon name="phone" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Alternate number</small><strong class="text-forest">{{ $settings->get('phone_secondary') }}</strong></span></a>
                @endif
                @if ($settings->get('whatsapp'))
                    <a href="https://wa.me/{{ $settings->get('whatsapp') }}" target="_blank" rel="noopener" class="card card-hover flex gap-4 p-5"><span class="icon-tile"><x-icon name="whatsapp" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">WhatsApp</small><strong class="text-forest">Chat with us</strong></span></a>
                @endif
                @if ($settings->get('email'))
                    <a href="mailto:{{ $settings->get('email') }}" class="card card-hover flex gap-4 p-5"><span class="icon-tile"><x-icon name="mail" :size="22" /></span><span class="min-w-0"><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Email</small><strong class="break-all text-forest">{{ $settings->get('email') }}</strong></span></a>
                @endif
                @if ($settings->get('address'))
                    <div class="card flex gap-4 p-5"><span class="icon-tile"><x-icon name="map-pin" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Office</small><span class="text-sm">{!! nl2br(e($settings->get('address'))) !!}</span></span></div>
                @endif
                @if ($settings->get('business_hours'))
                    <div class="card flex gap-4 p-5"><span class="icon-tile"><x-icon name="clock" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Hours</small><span class="text-sm">{{ $settings->get('business_hours') }}</span></span></div>
                @endif
                @if ($settings->get('map_embed_url'))
                    <div class="overflow-hidden rounded-2xl border border-line"><iframe src="{{ $settings->get('map_embed_url') }}" title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="aspect-[4/3] w-full border-0" allowfullscreen></iframe></div>
                @endif
                <a href="{{ route('booking.create') }}" class="btn btn-gold w-full">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            </aside>
        </div>
    </section>
@endsection
