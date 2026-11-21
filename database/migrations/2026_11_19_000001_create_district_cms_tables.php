<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * District-wise CMS: photo gallery (shared by the homepage and districts, like promo videos), the cars offered in each
 * district (each one points at a Vehicle Master record, so fares and booking keep using the existing vehicle types),
 * district routes and district FAQs as proper rows. Existing district FAQs and curated routes (JSON columns) are copied
 * into the new tables; the JSON columns are left untouched (nothing is dropped).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('show_on_home')->default(false);
            $table->boolean('show_on_districts')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'show_on_home', 'sort_order']);
        });

        Schema::create('district_page_gallery_photo', function (Blueprint $table) {
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gallery_photo_id')->constrained()->cascadeOnDelete();
            $table->primary(['district_page_id', 'gallery_photo_id']);
            $table->index('gallery_photo_id');
        });

        Schema::create('district_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->nullable(); // car name / model shown in this district, e.g. "Swift Dzire"
            $table->unsignedTinyInteger('seats')->nullable();
            $table->boolean('is_ac')->default(true);
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['district_page_id', 'is_active', 'sort_order']);
        });

        Schema::create('district_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->string('from_location', 120);
            $table->string('to_location', 120);
            $table->string('description', 500)->nullable();
            // Optional link to an existing priced route (Admin → Routes) — fares are only ever shown from that data.
            $table->foreignId('taxi_route_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['district_page_id', 'is_active', 'sort_order']);
        });

        Schema::create('district_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->string('question', 200);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['district_page_id', 'is_active', 'sort_order']);
        });

        Schema::table('promo_videos', function (Blueprint $table) {
            // Which homepage section shows the video: "featured" (Featured Taxi Videos) or "promotional".
            $table->string('home_section', 20)->default('featured')->after('show_on_home');
        });

        Schema::table('district_pages', function (Blueprint $table) {
            $table->json('hidden_services')->nullable()->after('service_texts');
        });

        $this->copyJsonContent();
    }

    /** Copy existing JSON FAQs and curated routes into the new tables (only for districts that have no rows yet). */
    private function copyJsonContent(): void
    {
        $now = now();
        foreach (DB::table('district_pages')->get(['id', 'faqs', 'popular_routes']) as $page) {
            if (! DB::table('district_faqs')->where('district_page_id', $page->id)->exists()) {
                $rows = [];
                foreach ((array) json_decode((string) $page->faqs, true) as $i => $faq) {
                    $q = trim((string) ($faq['question'] ?? ''));
                    $a = trim((string) ($faq['answer'] ?? ''));
                    if ($q !== '' && $a !== '') {
                        $rows[] = ['district_page_id' => $page->id, 'question' => mb_substr($q, 0, 200), 'answer' => $a, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
                if ($rows) {
                    DB::table('district_faqs')->insert($rows);
                }
            }

            if (! DB::table('district_routes')->where('district_page_id', $page->id)->exists()) {
                $rows = [];
                foreach ((array) json_decode((string) $page->popular_routes, true) as $i => $route) {
                    $from = trim((string) ($route['from'] ?? ''));
                    $to = trim((string) ($route['to'] ?? ''));
                    if ($from !== '' && $to !== '') {
                        $rows[] = ['district_page_id' => $page->id, 'from_location' => mb_substr($from, 0, 120), 'to_location' => mb_substr($to, 0, 120), 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
                if ($rows) {
                    DB::table('district_routes')->insert($rows);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('district_pages', fn (Blueprint $table) => $table->dropColumn('hidden_services'));
        Schema::table('promo_videos', fn (Blueprint $table) => $table->dropColumn('home_section'));
        Schema::dropIfExists('district_faqs');
        Schema::dropIfExists('district_routes');
        Schema::dropIfExists('district_vehicles');
        Schema::dropIfExists('district_page_gallery_photo');
        Schema::dropIfExists('gallery_photos');
    }
};
