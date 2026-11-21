<?php

/*
|--------------------------------------------------------------------------
| Union Territory landing pages
|--------------------------------------------------------------------------
| Same shape as tamil-nadu-districts.php, but these are NOT part of Tamil Nadu: each page is stored with its own
| state and served at /{slug} (see DistrictPage::TERRITORY_SLUGS). Content is factual travel information only —
| no fares, offices or guaranteed availability. Fares come from Admin → Routes like every other page.
| 'nearby' lists neighbouring Tamil Nadu district slugs (shown as "Nearby districts").
*/

return [
    'puducherry' => [
        'name' => 'Puducherry', 'hq' => 'Puducherry', 'region' => 'Union Territory',
        'about' => 'Puducherry is a Union Territory on the Coromandel Coast, separate from Tamil Nadu. Its capital is known for the French Quarter (White Town), the seafront Promenade and its cafés, with the international township of Auroville just outside the town.',
        'trips' => 'Common journeys include one-way drops to Chennai and Chennai International Airport along the East Coast Road, day trips to Auroville and Mamallapuram, and pilgrim trips to Chidambaram and Tiruvannamalai.',
        'access' => 'Puducherry Airport has limited flights, so most air travellers use Chennai International Airport. Puducherry railway station has direct trains to Chennai and other cities, and Villupuram Junction is the nearest major rail hub.',
        'airport' => 'Puducherry Airport / Chennai International Airport',
        'pickups' => ['Puducherry Railway Station', 'Puducherry New Bus Stand', 'White Town', 'Promenade Beach', 'Puducherry Airport', 'Lawspet'],
        'nearby' => ['viluppuram', 'cuddalore'],
        'cities' => ['Puducherry'],
        'summary' => 'French-quarter streets, the seafront Promenade and Auroville — airport runs and one-way drops along the ECR.',
        'destinations' => ['Chennai', 'Auroville', 'Mamallapuram', 'Chidambaram', 'Tiruvannamalai'],
        'drops' => ['Chennai International Airport', 'Auroville', 'Paradise Beach', 'Villupuram Junction', 'Mamallapuram'],
        'photo' => 'city-puducherry.jpg',
    ],
];
