<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // kategori
        $this->db->table('kategori')->insertBatch([
            ['id_kategori' => 2, 'nama_kategori' => 'Alat Masak'],
            ['id_kategori' => 3, 'nama_kategori' => 'Peralatan Camp'],
            ['id_kategori' => 5, 'nama_kategori' => 'Peralatan Tidur'],
            ['id_kategori' => 6, 'nama_kategori' => 'Sepatu Outdoor'],
        ]);

        // alat
        $this->db->table('alat')->insertBatch([
            [
                'id_alat' => 12,
                'nama_alat' => 'Nesting',
                'harga' => 20000,
                'id_kategori' => 2,
                'gambar' => 'default_image.jpg',
                'ukuran' => 'Besar',
                'warna' => 'Silver',
                'jumlah' => 99,
                'deskripsi' => 'Full set untuk penyewaan, berbahan dasar titanium'
            ],
            [
                'id_alat' => 15,
                'nama_alat' => 'Tenda',
                'harga' => 50000,
                'id_kategori' => 3,
                'gambar' => 'default_image.jpg',
                'ukuran' => '2P',
                'warna' => 'orange',
                'jumlah' => 90,
                'deskripsi' => ''
            ],
            [
                'id_alat' => 18,
                'nama_alat' => 'Fly Sheet',
                'harga' => 15000,
                'id_kategori' => 3,
                'gambar' => 'default_image.jpg',
                'ukuran' => 'besar',
                'warna' => 'hitam',
                'jumlah' => 10,
                'deskripsi' => 'berbahan lembut dan hangat'
            ]
        ]);

        // chatid
        $this->db->table('chatid')->insert([
            'id' => 2,
            'chatid' => '7683456685',
            'username' => 'mhmmdNashr'
        ]);

        // chatidadmin
        $this->db->table('chatidadmin')->insert([
            'id' => 1,
            'chatid' => '1516620357'
        ]);

        // users
        $this->db->table('users')->insertBatch([
            ['id_user' => 1, 'username' => 'roy', 'password' => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK', 'nama_lengkap' => 'El Roy Tamba', 'role' => 'Pelanggan'],
            ['id_user' => 6, 'username' => 'susilo', 'password' => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK', 'nama_lengkap' => 'Susilo Aditya Pratama', 'role' => 'Admin'],
            ['id_user' => 8, 'username' => 'nashir', 'password' => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK', 'nama_lengkap' => 'nashirdesu', 'role' => 'Admin'],
            ['id_user' => 10, 'username' => 'harlan', 'password' => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK', 'nama_lengkap' => 'harlan', 'role' => 'Admin'],
        ]);
    }
}
