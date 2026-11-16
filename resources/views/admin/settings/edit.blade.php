@extends('admin.layout')

@section('title', 'Settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <nav class="tabs" aria-label="Settings sections">
            @foreach ($groups as $groupKey => $group)
                <a href="#group-{{ $groupKey }}">{{ $group['label'] }}</a>
            @endforeach
        </nav>

        @foreach ($groups as $groupKey => $group)
            <section class="acard" id="group-{{ $groupKey }}">
                <h2 class="acard__title">{{ $group['label'] }}</h2>
                <div class="aform-grid">
                    @foreach ($group['fields'] as $key => $field)
                        @php($value = $values[$key] ?? ($field['default'] ?? null))
                        <div @class(['span-all' => in_array($field['type'], ['textarea', 'media', 'boolean'])])>
                            @switch($field['type'])
                                @case('textarea')
                                    <x-admin.textarea :name="$key" :label="$field['label']" :value="$value" rows="3" />
                                    @break
                                @case('boolean')
                                    <x-admin.checkbox :name="$key" :label="$field['label']" :checked="filter_var($value, FILTER_VALIDATE_BOOLEAN)" />
                                    @break
                                @case('media')
                                    <x-admin.media :name="$key" :label="$field['label']" :value="$value" />
                                    @break
                                @case('color')
                                    <x-admin.input :name="$key" :label="$field['label']" type="color" :value="$value" style="height:44px;padding:4px;max-width:120px" />
                                    @break
                                @case('select')
                                    <x-admin.select :name="$key" :label="$field['label']" :options="$field['options']" :value="$value" />
                                    @break
                                @default
                                    <x-admin.input :name="$key" :label="$field['label']" :type="['email' => 'email', 'url' => 'url', 'tel' => 'tel', 'number' => 'number'][$field['type']] ?? 'text'" :value="$value" />
                            @endswitch
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save settings</button>
        </div>
    </form>
@endsection
