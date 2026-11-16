<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Registers the bundled demonstration photos (public/images/demo) in the media library.
 * Attribution for each photo comes from database/seeders/data/image-credits.json.
 */
class MediaSeeder extends Seeder
{
    /** filename => [alt text, focal x %, focal y %] */
    private const IMAGES = [
        'design-hero-scene.webp' => ['Black SUV taxi with a ONE WAY TAXI plate on a highway at sunset', 50, 55],
        'design-india-map.png' => ['Illustrated map of India with landmarks', 50, 50],
        'design-scenic-journeys.webp' => ['Scenic Journeys Across India — winding hill road', 50, 50],
        'design-fleet-hatchback.png' => ['White hatchback', 50, 50],
        'design-fleet-sedan.png' => ['White sedan', 50, 50],
        'design-fleet-suv.png' => ['White SUV', 50, 50],
        'design-fleet-innova.png' => ['White Innova Crysta', 50, 50],
        'design-fleet-tempo.png' => ['White Tempo Traveller', 50, 50],
        'trip-one-way-sedan.jpg' => ['Black premium sedan driving on a palm-lined highway', 55, 55],
        'trip-one-way-white-sedan-sunrise.jpg' => ['White sedan driving on an open highway at sunrise', 50, 52],
        'booking-airport-runway-sunrise.jpg' => ['Airport runway and terminal buildings at sunrise', 50, 62],
        'hero-airport-sunset-landing.jpg' => ['Passenger jet landing over an airport at sunset', 45, 38],
        'fleet-tempo-traveller.jpg' => ['White Force Traveller tempo van', 50, 55],
        'hero-airport-terminal-golden-hour.jpg' => ['Airport terminal, control tower and airliners on the apron at golden hour', 50, 58],
        'hero-airport-taxi-golden-hour.jpg' => ['ONE WAY TAXI black SUV outside an airport terminal at golden hour, with an airliner overhead', 36, 50],
        'hero-airport-takeoff-golden-hour.jpg' => ['Airliner taking off over the airport terminal and runway at golden hour', 50, 46],
        'hero-airport-terminal-sunset.jpg' => ['Airport terminal and control tower at sunset, with an airliner on the apron', 8, 45],
        'hero-white-suv-mountains.jpg' => ['White Mahindra Scorpio SUV on a Himalayan mountain road under a clear blue sky', 32, 60],
        'hero-sunset-mountains.jpg' => ['Sunset over a misty mountain valley', 50, 6],
        'hero-suv-black.webp' => ['Black SUV taxi with ONE WAY TAXI number plate', 50, 50],
        'hero-munnar-highway.jpg' => ['White taxi parked on a winding highway through the Munnar tea hills', 80, 78],
        'scenic-yercaud-ghat-road.jpg' => ['Ghat road curving through flame trees near Yercaud', 50, 60],
        'fleet-mini.jpg' => ['Silver hatchback — Mini category taxi', 50, 60],
        'service-auto-taxi.jpg' => ['Yellow and green auto rickshaws on a city street', 50, 60],
        'service-bike-taxi.jpg' => ['Black motorcycle parked on a street', 45, 60],
        'partner-driver.jpg' => ['Taxi driver’s hands on the steering wheel', 50, 50],
        'city-chennai-central.jpg' => ['Chennai Central railway station', 50, 45],
        'scenic-western-ghats.jpg' => ['Tea estates and peaks of the Western Ghats', 50, 50],
        'scenic-hairpin-aerial.jpg' => ['Aerial view of a car on a forest hairpin road near Valparai', 50, 50],
        'scenic-suv-misty.jpg' => ['White SUV parked in a misty mountain landscape', 55, 60],
        'scenic-driving.jpg' => ['SUV driving along a tree-lined road', 60, 55],
        'scenic-city-highway.jpg' => ['City highway with light trails at dusk', 50, 60],
        'fleet-economy-sedan.jpg' => ['Silver compact sedan — economy taxi', 50, 60],
        'fleet-premium-sedan.jpg' => ['White premium sedan', 50, 60],
        'fleet-suv-fortuner.jpg' => ['White full-size SUV', 50, 60],
        'fleet-suv-rav4.jpg' => ['Silver SUV parked outdoors', 50, 60],
        'fleet-innova.jpg' => ['White Toyota Innova Crysta multi-purpose vehicle', 50, 60],
        'city-chennai.jpg' => ['Chennai lighthouse on Marina Beach', 50, 55],
        'city-coimbatore.jpg' => ['Aerial view of Coimbatore city', 50, 50],
        'city-ooty.jpg' => ['Tea gardens on the hills of Ooty', 50, 60],
        'city-bengaluru.jpg' => ['Vidhana Soudha in Bengaluru', 50, 45],
        'city-madurai.jpg' => ['Gopurams of the Meenakshi Amman Temple, Madurai', 50, 40],
        'city-mysuru.jpg' => ['Mysore Palace', 50, 50],
        'city-puducherry.jpg' => ['Seafront promenade in Puducherry', 50, 55],
        'city-kodaikanal.jpg' => ['Kodaikanal lake surrounded by hills', 50, 50],
        'airport-chennai.jpg' => ['Chennai International Airport terminal', 50, 55],
    ];

    public function run(): void
    {
        $credits = json_decode((string) file_get_contents(__DIR__.'/data/image-credits.json'), true) ?: [];

        foreach (self::IMAGES as $file => [$alt, $focalX, $focalY]) {
            $absolute = public_path('images/demo/'.$file);
            if (! is_file($absolute)) {
                $this->command?->warn("Demo image missing: {$file}");

                continue;
            }

            $size = @getimagesize($absolute);
            $key = pathinfo($file, PATHINFO_FILENAME);
            $credit = $credits[$key] ?? null;

            $media = MediaAsset::firstOrNew(['disk' => 'demo', 'path' => $file]);

            // File facts are always refreshed; editorial fields (title, alt, focal point) are only
            // set when the record is first created so admin edits survive re-seeding.
            $media->fill([
                'original_name' => $file,
                'mime_type' => $size['mime'] ?? 'image/jpeg',
                'size_bytes' => filesize($absolute),
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'credit' => $credit ? sprintf('“%s” by %s — %s (%s) %s', $credit['title'], $credit['author'], $credit['license'], $credit['source'], $credit['url']) : $media->credit,
            ]);

            if (! $media->exists) {
                $media->fill([
                    'title' => Str::of($key)->replace('-', ' ')->title()->toString(),
                    'alt_text' => $alt,
                    'focal_x' => $focalX,
                    'focal_y' => $focalY,
                ]);
            }

            $media->save();
        }
    }

    public static function id(string $file): ?int
    {
        return MediaAsset::where('disk', 'demo')->where('path', $file)->value('id');
    }
}
