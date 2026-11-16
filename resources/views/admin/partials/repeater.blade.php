{{--
    Repeatable rows. Expects:
      $prefix  form name prefix, e.g. "content[items]"
      $label   heading
      $fields  [key => ['label' => .., 'type' => ..]]
      $items   existing rows (arrays)
      $max     maximum rows
--}}
@php
    $dotPrefix = trim(str_replace(['[', ']'], ['.', ''], $prefix), '.');
    $rows = old($dotPrefix, $items) ?: [];
    $wide = count($fields) > 2;
@endphp
<div class="repeater" data-repeater data-repeater-name="{{ $prefix }}" data-max="{{ $max ?? 20 }}">
    <h3 class="acard__title">{{ $label }}</h3>
    <div data-repeater-list>
        @foreach (array_values($rows) as $i => $row)
            <div class="repeater__item" data-repeater-item>
                <div class="repeater__head">
                    <span>#<span data-repeater-number>{{ $i + 1 }}</span></span>
                    <span class="repeater__tools">
                        <button type="button" class="abtn abtn--sm abtn--ghost" data-repeater-up aria-label="Move up">↑</button>
                        <button type="button" class="abtn abtn--sm abtn--ghost" data-repeater-down aria-label="Move down">↓</button>
                        <button type="button" class="abtn abtn--sm abtn--danger" data-repeater-remove>Remove</button>
                    </span>
                </div>
                <div @class(['aform-grid', 'aform-grid--3' => $wide])>
                    @foreach ($fields as $key => $field)
                        @include('admin.partials.field', ['type' => $field['type'], 'name' => $prefix.'['.$i.']['.$key.']', 'label' => $field['label'], 'value' => $row[$key] ?? null, 'field' => $field])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    <template data-repeater-template>
        <div class="repeater__item" data-repeater-item>
            <div class="repeater__head">
                <span>#<span data-repeater-number>?</span></span>
                <span class="repeater__tools">
                    <button type="button" class="abtn abtn--sm abtn--ghost" data-repeater-up aria-label="Move up">↑</button>
                    <button type="button" class="abtn abtn--sm abtn--ghost" data-repeater-down aria-label="Move down">↓</button>
                    <button type="button" class="abtn abtn--sm abtn--danger" data-repeater-remove>Remove</button>
                </span>
            </div>
            <div @class(['aform-grid', 'aform-grid--3' => $wide])>
                @foreach ($fields as $key => $field)
                    @include('admin.partials.field', ['type' => $field['type'], 'name' => $prefix.'[__INDEX__]['.$key.']', 'label' => $field['label'], 'value' => null, 'field' => $field])
                @endforeach
            </div>
        </div>
    </template>
    <button type="button" class="abtn abtn--sm" data-repeater-add><x-icon name="plus" :size="14" /> Add row</button>
</div>
