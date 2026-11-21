@extends('layouts.app')

@section('content')
    <x-page-banner :title="$section?->title ?: 'Corporate Travel'" :subtitle="$section?->subtitle ?: 'Dependable transport for teams, guests and events.'" :media="$section?->media" fallback="images/demo/corporate-executive-arrival.jpg" :crumbs="['Corporate' => null]" />

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_420px]">
            <div>
                @if ($section?->get('body'))
                    <p class="text-lg leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>
                @endif
                <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                    @foreach ($section?->items() ?? [] as $item)
                        <li class="card flex gap-4 p-5">
                            <span class="icon-tile"><x-icon :name="$item['icon'] ?? 'check'" :size="22" /></span>
                            <span><strong class="block text-forest">{{ $item['title'] ?? '' }}</strong><small class="text-ink-soft">{{ $item['text'] ?? '' }}</small></span>
                        </li>
                    @endforeach
                </ul>
                <h2 class="mt-12 text-2xl font-bold">What we can arrange</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\CorporateEnquiry::REQUIREMENTS as $key => $label)
                        @continue($key === 'other')
                        <li class="flex items-center gap-3 rounded-xl bg-mint px-4 py-3 font-medium"><x-icon name="check-circle" :size="20" class="text-fresh" /> {{ $label }}</li>
                    @endforeach
                </ul>
                <p class="mt-6 text-ink-soft">Corporate pricing depends on volume, vehicle mix and billing terms. Share your requirement and our team will prepare a proposal.</p>
                @if ($settings->get('corporate_email'))
                    <p class="mt-2">Prefer email? <a class="font-semibold text-forest underline" href="mailto:{{ $settings->get('corporate_email') }}">{{ $settings->get('corporate_email') }}</a></p>
                @endif
            </div>

            <aside id="enquiry" class="lg:sticky lg:top-28">
                <form method="POST" action="{{ route('corporate.store') }}" class="card p-6 sm:p-7" novalidate>
                    @csrf
                    <h2 class="flex items-center gap-3 text-xl font-extrabold"><span class="h-1.5 w-7 rounded-full bg-gold"></span>Corporate enquiry</h2>
                    @if (session('status'))
                        <div class="mt-5 rounded-xl border border-fresh/30 bg-mint px-4 py-3 text-sm font-semibold text-forest" role="status">{{ session('status') }}</div>
                    @elseif ($errors->any())
                        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please correct the highlighted fields.</div>
                    @endif
                    <div class="mt-5 grid gap-4">
                        <x-form.field name="company_name" label="Company name" icon="building" required><input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" required maxlength="150" autocomplete="organization" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="contact_name" label="Your name" icon="user" required><input id="contact_name" type="text" name="contact_name" value="{{ old('contact_name') }}" required maxlength="120" autocomplete="name" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="designation" label="Designation (optional)" icon="briefcase"><input id="designation" type="text" name="designation" value="{{ old('designation') }}" maxlength="120" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="phone" label="Mobile number" icon="phone" required><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" placeholder="10-digit mobile" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="email" label="Work email" icon="mail" required><input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="city" label="City (optional)" icon="map-pin"><input id="city" type="text" name="city" value="{{ old('city') }}" maxlength="100" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="requirement" label="Requirement" icon="list" required>
                            <select id="requirement" name="requirement" required class="form-control form-control-icon">
                                <option value="">Select…</option>
                                @foreach (\App\Models\CorporateEnquiry::REQUIREMENTS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('requirement') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-form.field>
                        <x-form.field name="monthly_trips" label="Trips per month (optional)" icon="calendar">
                            <select id="monthly_trips" name="monthly_trips" class="form-control form-control-icon">
                                <option value="">Not sure</option>
                                @foreach (\App\Models\CorporateEnquiry::MONTHLY_TRIPS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('monthly_trips') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-form.field>
                        <x-form.field name="message" label="Details (optional)"><textarea id="message" name="message" maxlength="2000" rows="3" class="form-control">{{ old('message') }}</textarea></x-form.field>
                    </div>
                    <div class="absolute -left-[10000px]" aria-hidden="true"><label for="website">Leave empty</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <label class="form-check mt-5"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I agree to the <a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> and consent to be contacted.</span></label>
                    @error('consent')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn-gold btn-lg mt-5 w-full">Send Enquiry <x-icon name="send" :size="18" /></button>
                </form>
            </aside>
        </div>
    </section>
@endsection
