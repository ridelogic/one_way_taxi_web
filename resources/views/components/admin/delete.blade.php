@props(['action', 'label' => 'Delete', 'confirm' => 'Delete this item? This cannot be undone.'])
<form method="POST" action="{{ $action }}" class="inline-form" data-confirm="{{ $confirm }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="abtn abtn--sm abtn--danger"><x-icon name="trash" :size="14" /> {{ $label }}</button>
</form>
