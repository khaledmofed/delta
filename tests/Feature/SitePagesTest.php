<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    public static function pages(): array
    {
        $pages = [
            'home' => ['/', 'Insurance Brokers in Sharjah'],
            'about' => ['/about', 'Your trusted insurance broker since 1990'],
            'services' => ['/services', 'Insurance Services'],
            'contact' => ['/contact', 'Contact Us'],
        ];
        foreach (['motor', 'medical', 'home', 'travel', 'boat', 'pet'] as $p) {
            $pages["product $p"] = ["/$p-insurance", ucfirst($p).' Insurance'];
        }

        return $pages;
    }

    #[DataProvider('pages')]
    public function test_public_page_renders_with_shared_layout(string $url, string $text): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($text)
            ->assertSee('class="hdr"', false)
            ->assertSee('class="ftr"', false)
            ->assertSee(asset('assets/css/site.css'), false)
            ->assertDontSee('.html"', false);
    }

    public function test_product_pages_mark_insurance_menu_active(): void
    {
        $this->get('/pet-insurance')->assertSee('<li class="active"><a href="'.route('services').'">', false);
    }
}
