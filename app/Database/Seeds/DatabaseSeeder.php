<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed kategori
        $kategori = [
            ['id_kategori' => 2, 'nama_kategori' => 'Alat Masak'],
            ['id_kategori' => 3, 'nama_kategori' => 'Peralatan Camp'],
            ['id_kategori' => 5, 'nama_kategori' => 'Peralatan Tidur'],
            ['id_kategori' => 6, 'nama_kategori' => 'Sepatu Outdoor'],
        ];
        $this->db->table('kategori')->insertBatch($kategori);

        // Seed alat
        $alat = [
            [
                'id_alat'      => 12,
                'nama_alat'    => 'Nesting',
                'harga'        => 20000,
                'id_kategori'  => 2,
                'gambar'       => 'default_image.jpg',
                'ukuran'       => 'Besar',
                'warna'        => 'Silver',
                'jumlah'       => 94,
                'deskripsi'    => 'Full set untuk penyewaan, berbahan dasar titanium'
            ],
            [
                'id_alat'      => 15,
                'nama_alat'    => 'Tenda',
                'harga'        => 50000,
                'id_kategori'  => 3,
                'gambar'       => 'default_image.jpg',
                'ukuran'       => '2P',
                'warna'        => 'orange',
                'jumlah'       => 84,
                'deskripsi'    => ''
            ],
            [
                'id_alat'      => 18,
                'nama_alat'    => 'Fly Sheet',
                'harga'        => 15000,
                'id_kategori'  => 3,
                'gambar'       => 'default_image.jpg',
                'ukuran'       => 'besar',
                'warna'        => 'hitam',
                'jumlah'       => 10,
                'deskripsi'    => 'berbahan lembut dan hangat'
            ]
        ];
        $this->db->table('alat')->insertBatch($alat);

        // Seed users
        $users = [
            [
                'id_user'       => 1,
                'username'      => 'roy',
                'password'      => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK',
                'nama_lengkap'   => 'El Roy Tamba',
                'role'          => 'Pelanggan'
            ],
            [
                'id_user'       => 6,
                'username'      => 'susilo',
                'password'      => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK',
                'nama_lengkap'   => 'Susilo Aditya Pratama',
                'role'          => 'Admin'
            ],
            [
                'id_user'       => 8,
                'username'      => 'nashir',
                'password'      => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK',
                'nama_lengkap'   => 'nashirdesu',
                'role'          => 'Admin'
            ],
            [
                'id_user'       => 10,
                'username'      => 'harlan',
                'password'      => '$2y$10$UYFGzOMm0BAE0UVqzRW5CejUE7becCrz3hs6fnfZsou.VbChqVNIK',
                'nama_lengkap'   => 'harlan',
                'role'          => 'Admin'
            ]
        ];
        $this->db->table('users')->insertBatch($users);

        // Seed chatid
        $chatid = [
            ['id' => 2, 'chatid' => '7683456685', 'username' => 'mhmmdNashr']
        ];
        $this->db->table('chatid')->insertBatch($chatid);

        // Seed chatidadmin
        $chatidadmin = [
            ['id' => 1, 'chatid' => '1516620357']
        ];
        $this->db->table('chatidadmin')->insertBatch($chatidadmin);

        // Seed orders
        $orders = [
            [
                'id_order'              => 1,
                'chatid'                => '7683456685',
                'id_alat'               => 15,
                'jumlah'                => 1,
                'total_harga'           => 150000.00,
                'status'                => 'Completed',
                'tanggal_pesan'         => '2025-07-14',
                'tanggal_pengembalian'  => '2025-07-17'
            ],
            [
                'id_order'              => 2,
                'chatid'                => '7683456685',
                'id_alat'               => 12,
                'jumlah'                => 5,
                'total_harga'           => 400000.00,
                'status'                => 'Completed',
                'tanggal_pesan'         => '2025-07-14',
                'tanggal_pengembalian'  => '2025-07-18'
            ],
            [
                'id_order'              => 3,
                'chatid'                => '7683456685',
                'id_alat'               => 15,
                'jumlah'                => 5,
                'total_harga'           => 500000.00,
                'status'                => 'Completed',
                'tanggal_pesan'         => '2025-07-14',
                'tanggal_pengembalian'  => '2025-07-16'
            ]
        ];
        $this->db->table('orders')->insertBatch($orders);

        // Seed pengembalianorder
        $pengembalian = [
            ['id' => 1, 'idorder' => 1, 'jumlahpengembalian' => 1],
            ['id' => 2, 'idorder' => 3, 'jumlahpengembalian' => 5]
        ];
        $this->db->table('pengembalianorder')->insertBatch($pengembalian);
    }
}
