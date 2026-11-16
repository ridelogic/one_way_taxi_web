@extends('admin.layout')

@section('title', 'Homepage · '.($schema['label'] ?? $section->key))

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('admin.homepage.index') }}">← All sections</a>
            <h2 style="margin:6px 0 0">{{ $schema['label'] ?? $section->key }}</h2>
            @if (! empty($schema['note']))<p>{{ $schema['note'] }}</p>@endif
        </div>
        <a class="abtn" href="{{ route('home') }}#{{ $section->key }}" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> View on site</a>
    </div>

    <form method="POST" action="{{ route('admin.homepage.update', $section) }}">
        @csrf
        @method('PUT')

        <div class="layout-2">
            <div>
                <section class="acard">
                    <h3 class="acard__title">Text</h3>
                    @if ($section->type === 'hero')
                        <p class="acard__help">The large heading is built from the three heading lines below; the last one is shown in orange.</p>
                    @endif
                    <div class="aform-grid">
                        <div class="span-all"><x-admin.input name="title" :label="$section->type === 'hero' ? 'Internal title' : 'Section title'" :value="$section->title" /></div>
                        <div class="span-all"><x-admin.textarea name="subtitle" :label="$section->type === 'hero' ? 'Supporting text' : 'Subtitle'" :value="$section->subtitle" rows="2" /></div>
                        @foreach ($schema['fields'] ?? [] as $key => $field)
                            <div @class(['span-all' => in_array($field['type'], ['textarea', 'media'])])>
                                @include('admin.partials.field', ['type' => $field['type'], 'name' => 'content['.$key.']', 'label' => $field['label'], 'value' => $section->get($key), 'field' => $field])
                            </div>
                        @endforeach
                    </div>
                </section>

                @if (isset($schema['items']))
                    <section class="acard">
                        @include('admin.partials.repeater', [
                            'prefix' => 'content[items]',
                            'label' => $schema['items']['label'],
                            'fields' => $schema['items']['fields'],
                            'items' => $section->items(),
                            'max' => $schema['items']['max'] ?? 12,
                        ])
                    </section>
                @endif
            </div>

            <aside>
                <section class="acard">
                    <h3 class="acard__title">Display</h3>
                    <x-admin.checkbox name="is_active" label="Show this section on the homepage" :checked="$section->is_active" />
                    <x-admin.input name="sort_order" label="Order" type="number" :value="$section->sort_order" min="0" max="999" help="Lower numbers appear first (hero, booking card and routes always stay at the top)." />
                </section>
                @if (! empty($schema['media']))
                    <section class="acard">
                        <h3 class="acard__title">{{ $schema['media'] }}</h3>
                        <x-admin.media name="media_id" label="Image" :value="$section->media_id" help="Use “Crop & position” to choose which part of the image stays visible." />
                    </section>
                @endif
            </aside>
        </div>

        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save section</button>
            <a href="{{ route('admin.homepage.index') }}" class="abtn">Cancel</a>
        </div>
    </form>
@endsection
