{{-- Taxi Promotional Videos (Admin → Homepage Content → Videos, homepage section "Taxi Promotional Videos") --}}
<x-video-gallery
    :videos="$promoVideos ?? collect()"
    :title="$section->title ?: 'Taxi Promotional Videos'"
    :subtitle="$section->subtitle"
    :eyebrow="$section->get('eyebrow') ?: 'Promotions'"
    heading-id="promo-videos-title"
    class="bg-white"
    id="promo-videos" />
