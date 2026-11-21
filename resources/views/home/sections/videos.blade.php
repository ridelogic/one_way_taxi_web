{{-- Promotional videos (CMS: Admin → Homepage sections → Videos; the videos themselves: Admin → Video Promotions → "Show on homepage") --}}
<x-video-gallery
    :videos="$homeVideos ?? collect()"
    :title="$section->title ?: 'Explore Our Taxi Experiences'"
    :subtitle="$section->subtitle ?: 'Real journeys, real customers and reliable taxi services.'"
    :eyebrow="$section->get('eyebrow') ?: 'Video stories'"
    heading-id="videos-title"
    class="bg-ivory"
    id="videos" />
