<div class="media-modal" data-media-modal hidden role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
    <div class="media-modal__backdrop" data-media-close></div>
    <div class="media-modal__panel">
        <div class="media-modal__head">
            <h2 id="media-modal-title">Choose an image</h2>
            <button type="button" class="abtn abtn--ghost" data-media-close aria-label="Close"><x-icon name="x" :size="20" /></button>
        </div>
        <div class="media-modal__tools">
            <input type="search" placeholder="Search images…" data-media-search aria-label="Search images">
            <label class="abtn abtn--primary">
                <x-icon name="upload" :size="16" /> Upload new
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-media-upload data-upload-url="{{ route('admin.media.store') }}" hidden>
            </label>
        </div>
        <p class="media-modal__status" data-media-status aria-live="polite"></p>
        <div class="media-modal__grid" data-media-grid data-picker-url="{{ route('admin.media.picker') }}"></div>
        <div class="media-modal__foot"><button type="button" class="abtn" data-media-more hidden>Load more</button></div>
    </div>
</div>
