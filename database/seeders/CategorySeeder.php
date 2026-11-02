<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Technology', 'description' => 'Tech news and tutorials', 'color' => '#3b82f6'],
            ['name' => 'Business', 'description' => 'Business insights and strategies', 'color' => '#10b981'],
            ['name' => 'Lifestyle', 'description' => 'Lifestyle tips and stories', 'color' => '#f59e0b'],
            ['name' => 'Travel', 'description' => 'Travel guides and experiences', 'color' => '#8b5cf6'],
            ['name' => 'Food', 'description' => 'Recipes and food reviews', 'color' => '#ef4444'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        // Create tags
        $tags = [
            'Laravel',
            'PHP',
            'JavaScript',
            'Vue.js',
            'React',
            'Livewire',
            'Tailwind CSS',
            'Web Development',
            'Mobile Apps',
            'AI & ML',
            'Productivity',
            'Remote Work',
            'Entrepreneurship',
            'Marketing',
            'Design',
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag]);
        }
    }
}
