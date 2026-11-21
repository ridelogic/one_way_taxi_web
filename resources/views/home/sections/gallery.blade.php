{{-- Photo Gallery (Admin → Homepage Content → Photos) --}}
<x-photo-gallery
    :photos="$galleryPhotos ?? collect()"
    :title="$section->title ?: 'Photo Gallery'"
    :subtitle="$section->subtitle"
    :eyebrow="$section->get('eyebrow') ?: 'Gallery'"
    heading-id="gallery-title"
    class="bg-ivory"
    id="gallery" />
