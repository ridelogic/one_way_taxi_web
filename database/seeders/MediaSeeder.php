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
        // Premium redesign: taxi-service photography (Pexels License)
        'about-driver-with-passenger.jpg' => ['Professional driver in a white shirt driving a passenger, who works on a tablet in the back seat', 62, 45],
        'why-chauffeur-opening-door.jpg' => ['Chauffeur in a suit holding the rear door of a black sedan open for a business traveller', 50, 55],
        'service-couple-loading-luggage.jpg' => ['Couple with a suitcase at the open boot of their car, ready for the trip', 50, 45],
        'service-white-suv-indian-highway.jpg' => ['White Mahindra SUV on a tree-lined Indian highway', 55, 60],
        'service-airport-traveller.jpg' => ['Traveller with a suitcase outside an airport terminal, booking her ride on a phone', 50, 50],
        'service-corporate-rear-seat.jpg' => ['Business traveller working on a laptop in the rear seat of a premium car', 40, 50],
        'cta-mumbai-taxi-driver.jpg' => ['Taxi driver at the wheel of a black-and-yellow cab in Mumbai', 62, 55],
        'corporate-executive-arrival.jpg' => ['Business traveller stepping out of a chauffeur-driven car while on a phone call', 55, 40],
        'partner-driver-at-wheel.jpg' => ['Professional driver at the wheel of a silver car', 60, 45],
        'rental-chauffeur-luxury-suv.jpg' => ['Uniformed chauffeur standing beside a black luxury SUV', 70, 55],
        'routes-sedan-highway-sunset.jpg' => ['Black premium sedan driving on a highway at sunset', 50, 42],
        'services-chauffeur-door.jpg' => ['Chauffeur holding the door of a black sedan as a passenger steps in', 45, 55],
        'onewaytrip-hero-banner.jpg' => ['Find Your Perfect Taxi for Every Journey — white OneWayTrip Taxi sedan in front of a temple at sunset', 50, 50],
        'hero-black-sedan-city-skyline.jpg' => ['Black luxury sedan in front of a city skyline', 62, 60],
        'about-indian-taxi-driver.jpg' => ['Smiling taxi driver at the wheel of his cab', 38, 58],
        'about-woman-passenger.jpg' => ['Smiling woman passenger seated comfortably in a car', 55, 56],
        'about-woman-in-car-mockup.jpg' => ['Smiling young Indian woman seated in a car with her seat belt on', 50, 45],
        'about-college-woman-passenger.jpg' => ['Young Indian woman travelling comfortably as a passenger in a car', 46, 38],
        'why-woman-passenger-smiling.jpg' => ['Smiling young Indian woman passenger seen in the car mirror', 52, 40],
        // Tamil Nadu district landmarks (Wikimedia Commons, credited in image-credits.json)
        'district-ariyalur.jpg' => ['Gangaikonda Cholapuram temple, Ariyalur district', 50, 50],
        'district-chengalpattu.jpg' => ['Shore Temple at Mamallapuram, Chengalpattu district', 50, 50],
        'district-cuddalore.jpg' => ['Gopuram of the Thillai Nataraja temple, Chidambaram', 50, 40],
        'district-dharmapuri.jpg' => ['Hogenakkal Falls on the Kaveri, Dharmapuri district', 50, 50],
        'district-erode.jpg' => ['Kodiveri dam near Gobichettipalayam, Erode district', 50, 55],
        'district-kallakurichi.jpg' => ['Rice fields below the Kalvarayan Hills, Kallakurichi district', 50, 50],
        'district-kancheepuram.jpg' => ['Kailasanathar temple, Kanchipuram', 50, 50],
        'district-kanniyakumari.jpg' => ['Vivekananda Rock Memorial, Kanniyakumari', 45, 50],
        'district-karur.jpg' => ['Karur town at night', 50, 65],
        'district-krishnagiri.jpg' => ['Krishnagiri dam', 55, 55],
        'district-mayiladuthurai.jpg' => ['Dansborg fort, Tharangambadi', 50, 50],
        'district-nagapattinam.jpg' => ['Basilica of Our Lady of Good Health, Velankanni', 50, 45],
        'district-namakkal.jpg' => ['Ghat road through the Kolli Hills, Namakkal district', 45, 55],
        'district-perambalur.jpg' => ['Ranjankudi fort, Perambalur district', 50, 45],
        'district-pudukkottai.jpg' => ['Thirumayam fort, Pudukkottai district', 50, 60],
        'district-ramanathapuram.jpg' => ['Pamban road bridge to Rameswaram', 60, 55],
        'district-ranipet.jpg' => ['Historic gateway at Arcot, Ranipet district', 55, 50],
        'district-salem.jpg' => ['Mettur dam and reservoir, Salem district', 50, 55],
        'district-sivaganga.jpg' => ['Chettinad mansion, Sivaganga district', 50, 50],
        'district-tenkasi.jpg' => ['Courtallam waterfalls, Tenkasi district', 40, 55],
        'district-thanjavur.jpg' => ['Brihadeeswarar temple, Thanjavur', 30, 50],
        'district-theni.jpg' => ['Vaigai dam, Theni district', 50, 45],
        'district-thoothukudi.jpg' => ['Tiruchendur Murugan temple on the seashore, Thoothukudi district', 40, 45],
        'district-tiruchirappalli.jpg' => ['View over Tiruchirappalli from the Rockfort', 40, 50],
        'district-tirunelveli.jpg' => ['Nellaiappar temple, Tirunelveli', 45, 40],
        'district-tirupathur.jpg' => ['Road to the Yelagiri hills, Tirupathur district', 50, 55],
        'district-tiruvallur.jpg' => ['Boats on Pulicat lake, Tiruvallur district', 50, 55],
        'district-tiruvannamalai.jpg' => ['Arunachaleswarar temple, Tiruvannamalai', 50, 50],
        'district-tiruvarur.jpg' => ['Thyagarajaswamy temple, Tiruvarur', 40, 55],
        'district-vellore.jpg' => ['Vellore fort and moat', 50, 50],
        'district-viluppuram.jpg' => ['Gingee fort, Viluppuram district', 50, 50],
        'district-virudhunagar.jpg' => ['Andal temple tower, Srivilliputhur', 70, 40],
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
        // About Us page (Unsplash / Pexels licence, credited in image-credits.json)
        'about-banner-kodaikanal-innova.jpg' => ['Silver Toyota Innova taxi on a hill road below terraced slopes in Kodaikanal, Tamil Nadu', 52, 78],
        'about-indian-family-car.jpg' => ['Smiling Indian family with their young son at the door of a white car', 55, 45],
        'about-banner-woman-passenger.jpg' => ['Young Indian woman travelling comfortably in the back seat of a car, trees outside the window', 40, 45],
        'about-man-car-4x3.jpg' => ['Smiling young Indian man in a grey suit seated in a white car, arm on the open window', 58, 45],
        'about-woman-passenger-4x3.jpg' => ['Smiling young Indian woman standing beside a silver car', 45, 35],
        'about-family-taxi-4x3.jpg' => ['Smiling Indian family with their young son at the door of a white car', 50, 45],
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
