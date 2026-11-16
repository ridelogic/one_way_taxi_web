{{-- Renders one schema-defined field. Expects: $type, $name, $label, $value, optional $field (schema array). --}}
@switch($type)
    @case('textarea')
        <x-admin.textarea :name="$name" :label="$label" :value="$value" rows="4" />
        @break
    @case('number')
        <x-admin.input :name="$name" :label="$label" type="number" :value="$value" :min="$field['min'] ?? null" :max="$field['max'] ?? null" />
        @break
    @case('boolean')
        <x-admin.checkbox :name="$name" :label="$label" :checked="(bool) $value" />
        @break
    @case('icon')
        <x-admin.icon-select :name="$name" :label="$label" :value="$value" />
        @break
    @case('media')
        <x-admin.media :name="$name" :label="$label" :value="$value" />
        @break
    @case('url')
        <x-admin.input :name="$name" :label="$label" :value="$value" placeholder="/book or https://…" />
        @break
    @default
        <x-admin.input :name="$name" :label="$label" :value="$value" />
@endswitch
