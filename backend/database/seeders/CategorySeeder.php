<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'sach-giao-khoa' => 'Sách giáo khoa',
            'sach-van-hoc' => 'Sách văn học',
            'van-phong-pham' => 'Văn phòng phẩm/dụng cụ học sinh',
            'do-choi' => 'Đồ chơi',
            'truyen-tranh' => 'Truyện tranh',
            'qua-luu-niem' => 'Quà lưu niệm',
        ];
        foreach (array_keys($categories) as $index => $slug) {
            // Do not reset admin-edited category settings on repeated seed.
            DB::table('categories')->insertOrIgnore([
                'slug' => $slug, 'name' => $categories[$slug], 'sort_order' => $index,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
