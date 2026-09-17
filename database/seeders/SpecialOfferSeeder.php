<?php

namespace Database\Seeders;

use App\Models\SpecialOffer;
use Illuminate\Database\Seeder;

class SpecialOfferSeeder extends Seeder
{
    public function run(): void
    {
        if (SpecialOffer::count() > 0) {
            return;
        }

        $offers = [
            [
                'title' => 'Birinchi buyurtmaga 20% chegirma',
                'subtitle' => 'UstaGo\'da yangimisiz? Birinchi xizmat biz tomondan.',
                'cta' => 'Chegirmani olish',
                'image' => 'https://images.unsplash.com/photo-1486006920555-c77dcf18193c?auto=format&fit=crop&w=1200&q=80',
                'link' => '/app/search',
                'badge' => '-20% CHEGIRMA',
                'accent' => 'from-emerald-600 to-teal-800',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Bepul diagnostika haftaligi',
                'subtitle' => 'Hamkor ustaxonalarda to\'liq 45 nuqtali tekshiruv.',
                'cta' => 'Ustaxona topish',
                'image' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=1200&q=80',
                'link' => '/app/search?service=Diagnostika',
                'badge' => 'BEPUL',
                'accent' => 'from-blue-600 to-indigo-800',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Qishki shina to\'plami',
                'subtitle' => 'Almashtirish + balanslash + saqlash 240 000 so\'mdan.',
                'cta' => 'Takliflarni ko\'rish',
                'image' => 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?auto=format&fit=crop&w=1200&q=80',
                'link' => '/app/search?service=Shina xizmati',
                'badge' => 'MAVSUMIY',
                'accent' => 'from-amber-600 to-rose-800',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($offers as $offer) {
            SpecialOffer::create($offer);
        }
    }
}
