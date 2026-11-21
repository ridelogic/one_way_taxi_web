@extends('layouts.app')

@section('content')
    <x-page-banner title="Drive With Us" subtitle="Own a taxi? Enroll as a driver partner. Share your licence and vehicle details — our team verifies them and activates your account." :crumbs="['Drive With Us' => null]" />

    @php
        $select = function (string $name, array $items, string $placeholder) {
            $html = '<option value="">'.e($placeholder).'</option>';
            foreach ($items as $item) {
                $html .= '<option value="'.e($item['value']).'"'.((string) old($name) === (string) $item['value'] ? ' selected' : '').'>'.e($item['label']).'</option>';
            }

            return $html;
        };
    @endphp

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_340px]">
            @if (! $available)
                <div class="card p-8">
                    <h2 class="text-xl font-bold">Enrollment is temporarily unavailable</h2>
                    <p class="mt-2 text-ink-soft">Please call us and our team will register you as a driver partner.</p>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-gold mt-5"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('drivers.enroll.store') }}" enctype="multipart/form-data" class="card p-6 sm:p-8" novalidate>
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">Please correct the highlighted fields below. Uploaded files need to be chosen again.</div>
                    @endif

                    <h2 class="text-xl font-bold">Driver details</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <x-form.field name="first_name" label="First name" icon="user" required>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="30" autocomplete="given-name" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="last_name" label="Last name" icon="user" required>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="30" autocomplete="family-name" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="gender" label="Gender" icon="users" required>
                            <select id="gender" name="gender" required class="form-control form-control-icon">
                                @foreach (['Male', 'Female', 'Others'] as $g)
                                    <option value="{{ $g }}" @selected(old('gender', 'Male') === $g)>{{ $g }}</option>
                                @endforeach
                            </select>
                        </x-form.field>
                        <x-form.field name="dob" label="Date of birth" icon="calendar" required>
                            <input id="dob" type="date" name="dob" value="{{ old('dob') }}" max="{{ now()->subYears(18)->toDateString() }}" required class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="phone" label="Mobile number" icon="smartphone" required hint="Used to sign in to the driver app.">
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" placeholder="10-digit mobile number" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="email" label="Email (optional)" icon="mail">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="75" autocomplete="email" class="form-control form-control-icon">
                        </x-form.field>
                        @if ($passwordRequired)
                            <x-form.field name="password" label="Driver app password" icon="lock" required hint="6–20 characters.">
                                <input id="password" type="password" name="password" required minlength="6" maxlength="20" autocomplete="new-password" class="form-control form-control-icon">
                            </x-form.field>
                            <x-form.field name="password_confirmation" label="Confirm password" icon="lock" required>
                                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="6" maxlength="20" autocomplete="new-password" class="form-control form-control-icon">
                            </x-form.field>
                        @endif
                        <x-form.field name="national_id" label="National ID (optional)" icon="file-text" hint="11 characters.">
                            <input id="national_id" name="national_id" value="{{ old('national_id') }}" maxlength="11" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="address" label="Address (optional)" icon="home">
                            <input id="address" name="address" value="{{ old('address') }}" maxlength="255" autocomplete="street-address" class="form-control form-control-icon">
                        </x-form.field>
                    </div>

                    <h2 class="mt-10 text-xl font-bold">Licence &amp; documents</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <x-form.field name="licence_no" label="Driving licence number" icon="file-text" required>
                            <input id="licence_no" name="licence_no" value="{{ old('licence_no') }}" required maxlength="30" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="licence_expiry" label="Licence expiry date" icon="calendar" required>
                            <input id="licence_expiry" type="date" name="licence_expiry" value="{{ old('licence_expiry') }}" min="{{ now()->addDay()->toDateString() }}" required class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="badge_expiry" label="Badge / FC expiry date" icon="calendar" required>
                            <input id="badge_expiry" type="date" name="badge_expiry" value="{{ old('badge_expiry') }}" min="{{ now()->addDay()->toDateString() }}" required class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="photo" label="Your photo" required hint="JPG or PNG, up to 4 MB.">
                            <input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png" required class="form-control">
                        </x-form.field>
                        <x-form.field name="licence_front" label="Driving licence — front" required hint="JPG, PNG or PDF, up to 4 MB.">
                            <input id="licence_front" type="file" name="licence_front" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                        </x-form.field>
                        <x-form.field name="licence_back" label="Driving licence — back" required hint="JPG, PNG or PDF, up to 4 MB.">
                            <input id="licence_back" type="file" name="licence_back" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                        </x-form.field>
                        <x-form.field name="badge_document" label="Badge / FC document" required hint="JPG, PNG or PDF, up to 4 MB.">
                            <input id="badge_document" type="file" name="badge_document" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                        </x-form.field>
                    </div>

                    <h2 class="mt-10 text-xl font-bold">Vehicle details</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <x-form.field name="fleet_model_id" label="Vehicle type" icon="car" required>
                            <select id="fleet_model_id" name="fleet_model_id" required class="form-control form-control-icon">{!! $select('fleet_model_id', $options['models'], 'Select vehicle type') !!}</select>
                        </x-form.field>
                        <x-form.field name="manufacturer_value" label="Manufacturer" icon="car" required>
                            <select id="manufacturer_value" name="manufacturer_value" required class="form-control form-control-icon">{!! $select('manufacturer_value', $options['manufacturers'], 'Select manufacturer') !!}</select>
                        </x-form.field>
                        <div class="sm:col-span-2">
                            <span class="form-label">Vehicle number <span class="text-red-600" aria-hidden="true">*</span></span>
                            <div class="grid gap-3 sm:grid-cols-[1fr_120px_1fr]">
                                <x-form.field name="state_value" label="State" required>
                                    <select id="state_value" name="state_value" required class="form-control">{!! $select('state_value', $options['states'], 'State') !!}</select>
                                </x-form.field>
                                <x-form.field name="prefix_value" label="Prefix" required>
                                    <select id="prefix_value" name="prefix_value" required class="form-control">{!! $select('prefix_value', $options['prefixes'], 'Prefix') !!}</select>
                                </x-form.field>
                                <x-form.field name="plate_number" label="Number" required>
                                    <input id="plate_number" name="plate_number" value="{{ old('plate_number') }}" required maxlength="20" placeholder="e.g. 43AB1234" class="form-control">
                                </x-form.field>
                            </div>
                        </div>
                        <x-form.field name="manufacturing_year" label="Manufacturing year" icon="calendar" required>
                            <input id="manufacturing_year" type="number" name="manufacturing_year" value="{{ old('manufacturing_year') }}" min="2005" max="{{ now()->year + 1 }}" required inputmode="numeric" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="colour" label="Colour (optional)">
                            <div class="flex flex-wrap gap-2 pt-1" role="radiogroup" aria-label="Vehicle colour">
                                @foreach ($options['colours'] as $c)
                                    <label class="cursor-pointer" title="{{ $c['label'] }}">
                                        <input type="radio" name="colour" value="{{ $c['value'] }}" class="peer sr-only" @checked(old('colour') === $c['value'])>
                                        <span class="block size-9 rounded-full border-2 border-line ring-offset-2 peer-checked:ring-2 peer-checked:ring-gold peer-focus-visible:ring-2 peer-focus-visible:ring-forest" style="background: {{ $c['value'] }}"></span>
                                    </label>
                                @endforeach
                            </div>
                        </x-form.field>
                        <x-form.field name="owner_name" label="Vehicle owner name (optional)" icon="user">
                            <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" minlength="4" maxlength="30" class="form-control form-control-icon">
                        </x-form.field>
                        <x-form.field name="taxi_image" label="Vehicle photo" required hint="JPG or PNG, up to 4 MB.">
                            <input id="taxi_image" type="file" name="taxi_image" accept=".jpg,.jpeg,.png" required class="form-control">
                        </x-form.field>
                        <x-form.field name="rc_document" label="RC book (optional)" hint="JPG or PNG, up to 4 MB.">
                            <input id="rc_document" type="file" name="rc_document" accept=".jpg,.jpeg,.png" class="form-control">
                        </x-form.field>
                    </div>

                    <div class="absolute -left-[10000px]" aria-hidden="true"><label for="website">Leave this field empty</label><input id="website" type="text" name="website" tabindex="-1" autocomplete="off"></div>

                    <label class="form-check mt-8">
                        <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                        <span>I confirm these details are correct and agree to the <a href="{{ url('/privacy-policy') }}" target="_blank">Privacy Policy</a> and <a href="{{ url('/terms-and-conditions') }}" target="_blank">Terms</a>. My documents may be checked before my account is activated.</span>
                    </label>
                    @error('consent')<p class="form-error">{{ $message }}</p>@enderror

                    <button type="submit" class="btn btn-gold btn-lg mt-6 w-full">Submit Enrollment <x-icon name="arrow-right" :size="18" /></button>
                </form>
            @endif

            <aside class="space-y-5 lg:sticky lg:top-28">
                <div class="card p-6">
                    <h2 class="text-lg font-bold">How it works</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-ink-soft">
                        <li>Submit your details, licence and vehicle documents.</li>
                        <li>Our team verifies them and may call you.</li>
                        <li>Once approved, your account is activated — sign in to the driver app with your mobile number.</li>
                    </ol>
                </div>
                @if ($settings->get('phone'))
                    <div class="card p-6">
                        <h2 class="text-lg font-bold">Questions?</h2>
                        <a href="{{ $settings->telLink() }}" class="btn btn-outline mt-3 w-full"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
