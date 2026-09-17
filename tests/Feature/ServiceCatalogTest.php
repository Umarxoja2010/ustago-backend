<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_publicly_listable(): void
    {
        Service::factory()->count(3)->create();

        $this->getJson('/api/services')->assertOk()->assertJsonCount(3, 'data');
    }
}
