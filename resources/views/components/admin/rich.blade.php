@props(['name', 'label', 'value' => null, 'help' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = 'f_'.str_replace('.', '_', $key);
@endphp
<div @class(['afield', 'rich', 'has-error' => $errors->has($key)]) data-rich>
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="rich__toolbar" role="toolbar" aria-label="Formatting">
        <button type="button" data-cmd="formatBlock" data-value="h2" title="Heading">H2</button>
        <button type="button" data-cmd="formatBlock" data-value="h3" title="Sub-heading">H3</button>
        <button type="button" data-cmd="formatBlock" data-value="p" title="Paragraph">¶</button>
        <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
        <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
        <button type="button" data-cmd="createLink" title="Insert link">Link</button>
        <button type="button" data-cmd="insertImage" title="Insert image from the media library">Image</button>
        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
        <button type="button" data-rich-toggle title="Edit HTML source">&lt;/&gt; HTML</button>
    </div>
    <div class="rich__editor" contenteditable="true" data-rich-editor role="textbox" aria-multiline="true" aria-label="{{ $label }}"></div>
    <textarea id="{{ $id }}" name="{{ $name }}" class="rich__source" data-rich-source hidden>{{ old($key, $value) }}</textarea>
    <p class="afield__help">{{ $help ?? 'Scripts, inline styles and unsafe links are removed when saved.' }}</p>
    @error($key)<p class="afield__error">{{ $message }}</p>@enderror
</div>
