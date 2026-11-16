@props(['estimate' => null])
<div {{ $attributes->class(['card p-6']) }}>
    <h2 class="text-lg font-bold">Fare estimate</h2>
    @if ($estimate && $estimate['available'])
        <p class="mt-2 text-sm font-semibold text-ink">{{ $estimate['route_name'] }} · {{ (float) $estimate['distance_km'] }} km · {{ $estimate['duration'] }}</p>
        @if (! empty($estimate['starting_from']))
            <p class="mt-3 text-ink-soft">Starting from <span class="font-display text-2xl font-extrabold text-gold-600">{{ $estimate['total_formatted'] }}</span></p>
        @else
            <table class="mt-4 w-full text-sm">
                <tbody>
                    @foreach ($estimate['lines'] as $line)
                        <tr class="border-b border-dashed border-line"><td class="py-2 text-ink-soft">{{ $line['label'] }}</td><td class="py-2 text-right font-semibold whitespace-nowrap">{{ $line['formatted'] }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td class="pt-3 font-display font-bold text-forest">Estimated total</td><td class="pt-3 text-right font-display text-xl font-extrabold text-gold-600">{{ $estimate['total_formatted'] }}</td></tr>
                </tfoot>
            </table>
        @endif
        @if ($estimate['message'] ?? null)
            <p class="mt-3 text-sm text-ink-soft">{{ $estimate['message'] }}</p>
        @endif
        @if ($estimate['excluded'])
            <p class="mt-3 text-sm text-ink-soft"><strong class="text-ink">Not included:</strong> {{ implode('; ', $estimate['excluded']) }}.</p>
        @endif
        @if ($estimate['is_sample'] && settings()->bool('demo_mode'))
            <p class="mt-3 flex items-center gap-2 text-sm text-ink-soft"><x-sample-badge label="Sample fare" /> Demonstration rates.</p>
        @endif
    @elseif ($estimate)
        <p class="mt-3 text-sm text-ink-soft">{{ $estimate['message'] }}</p>
    @else
        <p class="mt-3 text-sm text-ink-soft">Enter your route and vehicle to see an estimate.</p>
    @endif
    <p class="mt-4 border-t border-line pt-4 text-xs leading-relaxed text-ink-soft/80">{{ settings('fare_disclaimer') }}</p>
</div>
