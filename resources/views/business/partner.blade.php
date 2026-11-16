@extends('layouts.app')

@section('content')
    <x-page-banner :title="$section?->title ?: 'Become a Partner'" :subtitle="$section?->subtitle ?: 'Drivers, owner-drivers and fleet owners — grow with us.'" :media="$section?->media" fallback="images/demo/partner-driver.jpg" :crumbs="['Become a Partner' => null]" />

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_420px]">
            <div>
                @if ($section?->get('body'))
                    <p class="text-lg leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>
                @endif
                <ul class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach ($section?->items() ?? [] as $item)
                        <li class="card p-5"><span class="icon-tile"><x-icon :name="$item['icon'] ?? 'check'" :size="22" /></span><strong class="mt-3 block text-forest">{{ $item['title'] ?? '' }}</strong><small class="text-ink-soft">{{ $item['text'] ?? '' }}</small></li>
                    @endforeach
                </ul>
                <h2 class="mt-12 text-2xl font-bold">How partnering works</h2>
                <ol class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ([['Register interest', 'Fill in the form with your details and vehicle.'], ['Verification', 'We call you and check licence, permits and vehicle documents.'], ['Onboarding', 'Agree terms with our team and start receiving trips.']] as $i => [$title, $text])
                        <li class="card p-5"><span class="grid size-10 place-items-center rounded-full bg-gold font-display font-extrabold text-white">{{ $i + 1 }}</span><h3 class="mt-3 font-bold">{{ $title }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $text }}</p></li>
                    @endforeach
                </ol>
                <p class="mt-6 text-sm text-ink-soft">Submitting this form registers your interest only; it is not an offer of work. Licence, badge, permit and insurance requirements depend on your state and vehicle.</p>
            </div>

            <aside id="register" class="lg:sticky lg:top-28">
                @if (! $open)
                    <div class="card p-6"><h2 class="text-xl font-bold">Registrations paused</h2><p class="mt-2 text-ink-soft">We are not accepting new partner registrations right now. Please check back soon or contact us.</p></div>
                @else
                    <form method="POST" action="{{ route('partner.store') }}" class="card p-6 sm:p-7" novalidate>
                        @csrf
                        <h2 class="flex items-center gap-3 text-xl font-extrabold"><span class="h-1.5 w-7 rounded-full bg-gold"></span>Partner registration</h2>
                        @if (session('status'))
                            <div class="mt-5 rounded-xl border border-fresh/30 bg-mint px-4 py-3 text-sm font-semibold text-forest" role="status">{{ session('status') }}</div>
                        @elseif ($errors->any())
                            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please correct the highlighted fields.</div>
                        @endif
                        <div class="mt-5 grid gap-4">
                            <x-form.field name="name" label="Full name" icon="user" required><input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="form-control form-control-icon"></x-form.field>
                            <x-form.field name="phone" label="Mobile number" icon="phone" required><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" placeholder="10-digit mobile" class="form-control form-control-icon"></x-form.field>
                            <x-form.field name="email" label="Email (optional)" icon="mail"><input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" class="form-control form-control-icon"></x-form.field>
                            <x-form.field name="city" label="City" icon="map-pin" required><input id="city" type="text" name="city" value="{{ old('city') }}" required maxlength="100" class="form-control form-control-icon"></x-form.field>
                            <x-form.field name="partner_type" label="I am a…" icon="users" required>
                                <select id="partner_type" name="partner_type" required class="form-control form-control-icon">
                                    <option value="">Select…</option>
                                    @foreach (\App\Models\PartnerEnquiry::PARTNER_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('partner_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-form.field>
                            <x-form.field name="vehicle_type" label="Vehicle type" icon="car">
                                <select id="vehicle_type" name="vehicle_type" class="form-control form-control-icon">
                                    <option value="">Select…</option>
                                    @foreach (\App\Models\PartnerEnquiry::VEHICLE_TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('vehicle_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-form.field>
                            <div class="grid grid-cols-2 gap-4">
                                <x-form.field name="vehicle_count" label="No. of vehicles"><input id="vehicle_count" type="number" name="vehicle_count" value="{{ old('vehicle_count') }}" min="0" max="5000" inputmode="numeric" class="form-control"></x-form.field>
                                <x-form.field name="experience_years" label="Years driving"><input id="experience_years" type="number" name="experience_years" value="{{ old('experience_years') }}" min="0" max="60" inputmode="numeric" class="form-control"></x-form.field>
                            </div>
                            <label class="form-check"><input type="checkbox" name="has_commercial_licence" value="1" @checked(old('has_commercial_licence'))><span>I hold a commercial driving licence / badge</span></label>
                            <x-form.field name="message" label="Anything else? (optional)"><textarea id="message" name="message" maxlength="2000" rows="3" class="form-control">{{ old('message') }}</textarea></x-form.field>
                        </div>
                        <div class="absolute -left-[10000px]" aria-hidden="true"><label for="website">Leave empty</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <label class="form-check mt-5"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I agree to the <a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> and consent to be contacted.</span></label>
                        @error('consent')<p class="form-error">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-gold btn-lg mt-5 w-full">Register Interest <x-icon name="arrow-right" :size="18" /></button>
                    </form>
                @endif
            </aside>
        </div>
    </section>
@endsection
