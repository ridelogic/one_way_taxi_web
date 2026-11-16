<section class="section bg-mint" id="contact" aria-labelledby="contact-title">
    <div class="container-site">
        <x-section-heading eyebrow="Contact" :title="$section->title" :subtitle="$section->subtitle" id="contact-title" />
        <div @class(['grid gap-6', 'lg:grid-cols-[0.9fr_1.1fr]' => $section->get('show_form'), 'sm:grid-cols-2 lg:grid-cols-4' => ! $section->get('show_form')])>
            <div @class(['grid gap-4', 'sm:grid-cols-2 lg:grid-cols-1' => $section->get('show_form'), 'contents' => ! $section->get('show_form')])>
                @if ($settings->get('phone'))
                    <a class="card card-hover flex gap-4 p-5" href="{{ $settings->telLink() }}"><span class="icon-tile"><x-icon name="phone" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Call us</small><strong class="text-forest">{{ $settings->get('phone') }}</strong></span></a>
                @endif
                @if ($settings->get('email'))
                    <a class="card card-hover flex gap-4 p-5" href="mailto:{{ $settings->get('email') }}"><span class="icon-tile"><x-icon name="mail" :size="22" /></span><span class="min-w-0"><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Email</small><strong class="break-all text-forest">{{ $settings->get('email') }}</strong></span></a>
                @endif
                @if ($settings->get('address'))
                    <div class="card flex gap-4 p-5"><span class="icon-tile"><x-icon name="map-pin" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Office</small><span class="text-sm">{!! nl2br(e($settings->get('address'))) !!}</span></span></div>
                @endif
                @if ($settings->get('business_hours'))
                    <div class="card flex gap-4 p-5"><span class="icon-tile"><x-icon name="clock" :size="22" /></span><span><small class="block text-xs font-semibold tracking-wide text-ink-soft uppercase">Hours</small><span class="text-sm">{{ $settings->get('business_hours') }}</span></span></div>
                @endif
            </div>

            @if ($section->get('show_form'))
                <form method="POST" action="{{ route('contact.store') }}" class="card p-6 sm:p-8" novalidate>
                    @csrf
                    <h3 class="text-xl font-bold">Send a message</h3>
                    @if ($section->get('body'))<p class="mt-1 text-sm text-ink-soft">{{ $section->get('body') }}</p>@endif
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-form.field name="name" for="hc-name" label="Your name" icon="user"><input id="hc-name" type="text" name="name" required maxlength="120" autocomplete="name" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="phone" for="hc-phone" label="Mobile number" icon="phone"><input id="hc-phone" type="tel" name="phone" required maxlength="20" autocomplete="tel" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="message" for="hc-message" label="Message" class="sm:col-span-2"><textarea id="hc-message" name="message" required minlength="10" maxlength="2000" rows="4" class="form-control"></textarea></x-form.field>
                    </div>
                    <div class="absolute -left-[10000px]" aria-hidden="true"><label for="hc-website">Leave empty</label><input id="hc-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <button type="submit" class="btn btn-gold mt-5">Send Message <x-icon name="send" :size="18" /></button>
                </form>
            @endif
        </div>
    </div>
</section>
