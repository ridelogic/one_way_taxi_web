<?php

use App\Models\HeroSlide;

$slides = HeroSlide::with("media")->orderBy("sort_order")->get();

foreach ($slides as $s) {
    dump([
        "slide_id" => $s->id,
        "title" => $s->title,
        "media_id" => $s->media_id,
        "image" => $s->media?->path,
        "object_fit" => $s->media?->object_fit,
        "focal_x" => $s->media?->focal_x,
        "focal_y" => $s->media?->focal_y,
    ]);
}
