<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_returned_nested(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->getJson('/api/categories')->assertOk();

        $electronics = collect($response->json('data'))->firstWhere('slug', 'electronics');
        $this->assertSame(['Mobile', 'Computer'], collect($electronics['children'])->pluck('name')->all());
    }

    public function test_location_hierarchy_is_seeded(): void
    {
        $this->seed(LocationSeeder::class);

        $root = $this->getJson('/api/locations')->assertOk()->json('data.0');
        $this->assertSame('Bangladesh', $root['name']);

        $kasba = $this->getJson('/api/locations?type=upazila')->assertOk()->json('data.0');
        $this->assertSame('Kasba', $kasba['name']);
    }

    public function test_admin_endpoints_are_admin_only(): void
    {
        $this->postJson('/api/admin/categories', ['name' => 'X'])->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/admin/categories', ['name' => 'X'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = User::factory()->admin()->create();

        $id = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/categories', ['name' => 'Gadgets & Toys'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'gadgets-toys')
            ->json('data.id');

        $this->putJson("/api/admin/categories/{$id}", ['name' => 'Gadgets', 'is_active' => false])
            ->assertOk()->assertJsonPath('data.name', 'Gadgets');

        $this->getJson("/api/categories/{$id}")->assertNotFound();

        $this->deleteJson("/api/admin/categories/{$id}")->assertOk();
        $this->assertNull(Category::find($id));
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'A', 'slug' => 'a']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/categories/{$category->id}", ['parent_id' => $category->id])
            ->assertStatus(422);
    }
}
