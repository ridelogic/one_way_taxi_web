{{-- Contact (CMS: Admin → Homepage sections → Contact): hero-red details panel beside the white message form --}}
<section class="home-section bg-ivory" id="contact" aria-labelledby="contact-title">
    <div class="container-site">
        <div @class(['grid gap-6', 'lg:grid-cols-[0.85fr_1.15fr]' => $section->get('show_form')])>
            <div class="contact-panel">
                <span class="kicker kicker-light">Contact</span>
                <h2 id="contact-title" class="display-2 text-white">{{ $section->title }}</h2>
                @if ($section->subtitle)<p class="mt-4 leading-[1.7] text-white/75">{{ $section->subtitle }}</p>@endif
                <ul class="mt-6">
                    @if ($settings->get('phone'))
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="phone" :size="20" /></span><span><small class="block text-xs font-semibold tracking-wide text-white/55 uppercase">Call us</small><a href="{{ $settings->telLink() }}" class="font-display text-lg font-bold text-white hover:underline">{{ $settings->get('phone') }}</a></span></li>
                    @endif
                    @if ($settings->get('email'))
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="mail" :size="20" /></span><span class="min-w-0"><small class="block text-xs font-semibold tracking-wide text-white/55 uppercase">Email</small><a href="mailto:{{ $settings->get('email') }}" class="font-semibold break-all text-white hover:underline">{{ $settings->get('email') }}</a></span></li>
                    @endif
                    @if ($settings->get('address'))
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="map-pin" :size="20" /></span><span><small class="block text-xs font-semibold tracking-wide text-white/55 uppercase">Office</small><span class="text-sm text-white/85">{!! nl2br(e($settings->get('address'))) !!}</span></span></li>
                    @endif
                    @if ($settings->get('business_hours'))
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="clock" :size="20" /></span><span><small class="block text-xs font-semibold tracking-wide text-white/55 uppercase">Hours</small><span class="text-sm text-white/85">{{ $settings->get('business_hours') }}</span></span></li>
                    @endif
                </ul>
            </div>

            @if ($section->get('show_form'))
                <form method="POST" action="{{ route('contact.store') }}" class="relative rounded-2xl border border-line bg-white p-7 sm:p-10" novalidate>
                    @csrf
                    <h3 class="text-2xl font-extrabold tracking-[-0.015em]">Send a message</h3>
                    @if ($section->get('body'))<p class="mt-2 text-ink-soft">{{ $section->get('body') }}</p>@endif
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-form.field name="name" for="hc-name" label="Your name" icon="user"><input id="hc-name" type="text" name="name" required maxlength="120" autocomplete="name" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="phone" for="hc-phone" label="Mobile number" icon="phone"><input id="hc-phone" type="tel" name="phone" required maxlength="20" autocomplete="tel" class="form-control form-control-icon"></x-form.field>
                        <x-form.field name="message" for="hc-message" label="Message" class="sm:col-span-2"><textarea id="hc-message" name="message" required minlength="10" maxlength="2000" rows="5" class="form-control"></textarea></x-form.field>
                    </div>
                    <div class="absolute -left-[10000px]" aria-hidden="true"><label for="hc-website">Leave empty</label><input id="hc-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <button type="submit" class="btn btn-gold mt-6">Send Message <x-icon name="send" :size="18" /></button>
                </form>
            @endif
        </div>
    </div>
</section>
