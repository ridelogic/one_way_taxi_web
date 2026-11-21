@extends('admin.layout')

@section('title', $district->name.' · SEO Settings')

@section('content')
    @include('admin.districts._header')

    <form method="POST" action="{{ route('admin.districts.tab.update', [$district, 'seo']) }}" style="margin-top:16px">
        @csrf @method('PUT')
        <div class="layout-2">
            <div>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="search" :size="18" /> Google search</h2>
                    <x-admin.input name="seo_title" label="SEO title" :value="$district->seo_title" help="The title shown in Google. “| One Way Taxi” is added automatically. Blank = “Taxi Service in {{ $district->name }}”." />
                    <x-admin.textarea name="seo_description" label="Meta description" :value="$district->seo_description" rows="3" help="The short text under the title in Google, about 150 characters." />
                    <x-admin.input name="seo_keywords" label="Meta keywords (optional)" :value="$district->seo_keywords" help="Comma-separated. Google gives these little weight — the page text matters most." />
                    <x-admin.input name="canonical_url" label="Canonical URL (optional)" :value="$district->canonical_url" help="Leave blank to use this page’s own address (recommended). Must be different for every district." />
                    <x-admin.input name="image_alt" label="Image description (alt text)" :value="$district->image_alt" help="Describes the banner photo for search engines and screen readers." />
                </section>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="send" :size="18" /> Sharing on WhatsApp, Facebook, etc.</h2>
                    <x-admin.input name="og_title" label="Sharing title" :value="$district->og_title" help="Blank = the SEO title." />
                    <x-admin.textarea name="og_description" label="Sharing description" :value="$district->og_description" rows="2" help="Blank = the meta description." />
                </section>
            </div>
            <aside>
                <section class="acard">
                    <x-admin.media name="og_media_id" label="Sharing image" :value="$district->og_media_id" help="Blank = the banner image. 1200×630 works best." />
                </section>
                <section class="acard">
                    <h2 class="acard__title"><x-icon name="check-circle" :size="18" /> Added automatically</h2>
                    <p class="acard__help">This page also tells search engines its breadcrumb trail, its FAQs (FAQs tab) and that your taxi service covers {{ $district->name }}. Nothing to fill in.</p>
                </section>
            </aside>
        </div>
        <div class="form-actions"><button type="submit" class="abtn abtn--primary">Save SEO settings</button></div>
    </form>
@endsection
