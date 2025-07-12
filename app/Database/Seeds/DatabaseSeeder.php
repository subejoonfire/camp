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
                'id_alat' => 12, 'nama_alat' => 'Nesting', 'harga' => 20000, 'id_kategori' => 2,
                'gambar' => 'default_image.jpg', 'ukuran' => 'Besar', 'warna' => 'Silver',
                'jumlah' => 30, 'deskripsi' => 'Full set untuk penyewaan, berbahan dasar titanium'
            ],
            [
                'id_alat' => 15, 'nama_alat' => 'Tenda', 'harga' => 50000, 'id_kategori' => 3,
                'gambar' => 'default_image.jpg', 'ukuran' => '2P', 'warna' => 'orange',
                'jumlah' => 8, 'deskripsi' => ''
            ],
            [
                'id_alat' => 18, 'nama_alat' => 'Fly Sheet', 'harga' => 15000, 'id_kategori' => 3,
                'gambar' => 'default_image.jpg', 'ukuran' => 'besar', 'warna' => 'hitam',
                'jumlah' => 10, 'deskripsi' => "berbahan lembut dan hangat"
            ]
        ]);

        // users
        $this->db->table('users')->insertBatch([
            [
                'id_user' => 1, 'username' => 'roy',
                'password' => '$2y$10$rz5.lfE/RWE0arKXdRXPB.1vcAkmQVSzCk6ESMWYCelXb8ko2CO2G',
                'nama_lengkap' => 'El Roy Tamba', 'role' => 'Pelanggan'
            ],
            [
                'id_user' => 6, 'username' => 'susilo',
                'password' => '$2y$10$dottMRUzyl3LGFKoTLc5veJ0UZnuoKlXxDAPwSflBjQblbfuOO106',
                'nama_lengkap' => 'Susilo Aditya Pratama', 'role' => 'Admin'
            ],
            [
                'id_user' => 8, 'username' => 'nashir',
                'password' => '$2y$10$LDpAC/Xl4Astooj1G6lJxuiDlLmOWf/HSzJMbR45bpc1VuEZz0BzG',
                'nama_lengkap' => 'nashirdesu', 'role' => 'Admin'
            ],
            [
                'id_user' => 10, 'username' => 'harlan',
                'password' => '$2y$10$m/uTFx.VeFV24WbxFTq6zeIR.6khLfBorMGrbpw10p/xd0sIhq6oC',
                'nama_lengkap' => 'harlan', 'role' => 'Admin'
            ]
        ]);

        // transaksi
        $this->db->table('transaksi')->insertBatch([
            ['id_trans_alat' => 1, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 1200000.00, 'tgl_trans_alat' => '2025-06-02', 'tgl_est' => '2025-06-03', 'disimpan_oleh' => 'roy', 'status' => 'Disetujui'],
            ['id_trans_alat' => 2, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 7140000.00, 'tgl_trans_alat' => '2025-06-02', 'tgl_est' => '2025-06-18', 'disimpan_oleh' => 'susilo', 'status' => 'Disetujui'],
            ['id_trans_alat' => 3, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 11600000.00, 'tgl_trans_alat' => '2025-06-02', 'tgl_est' => '2025-06-30', 'disimpan_oleh' => 'susilo', 'status' => 'Disetujui'],
            ['id_trans_alat' => 4, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 3780000.00, 'tgl_trans_alat' => '2025-06-02', 'tgl_est' => '2025-06-10', 'disimpan_oleh' => 'roy', 'status' => 'Disetujui'],
            ['id_trans_alat' => 5, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 320000.00, 'tgl_trans_alat' => '2025-06-04', 'tgl_est' => '2025-06-05', 'disimpan_oleh' => 'nashir', 'status' => 'Disetujui'],
            ['id_trans_alat' => 6, 'id_alat' => 18, 'jumlah_trans_alat' => 0, 'total_harga' => 300000.00, 'tgl_trans_alat' => '2025-06-04', 'tgl_est' => '2025-06-05', 'disimpan_oleh' => 'nashir', 'status' => 'Tolak'],
            ['id_trans_alat' => 7, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 600000.00, 'tgl_trans_alat' => '2025-06-04', 'tgl_est' => '2025-06-04', 'disimpan_oleh' => 'nashir', 'status' => 'Tunggu'],
            ['id_trans_alat' => 8, 'id_alat' => 12, 'jumlah_trans_alat' => 0, 'total_harga' => 1200000.00, 'tgl_trans_alat' => '2025-06-04', 'tgl_est' => '2025-06-05', 'disimpan_oleh' => 'nashir', 'status' => 'Disetujui'],
        ]);

        // pengembalian
        $this->db->table('pengembalian')->insertBatch([
            ['id_pengemb_alat' => 1, 'id_alat' => 12, 'jumlah_pengemb_alat' => 30, 'harga_satuan' => 0, 'total_harga' => 1200000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'roy', 'id_trans_alat' => 1],
            ['id_pengemb_alat' => 2, 'id_alat' => 12, 'jumlah_pengemb_alat' => 212112121, 'harga_satuan' => 0, 'total_harga' => 7140000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'susilo', 'id_trans_alat' => 2],
            ['id_pengemb_alat' => 3, 'id_alat' => 12, 'jumlah_pengemb_alat' => -212112101, 'harga_satuan' => 0, 'total_harga' => 7140000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'susilo', 'id_trans_alat' => 2],
            ['id_pengemb_alat' => 4, 'id_alat' => 12, 'jumlah_pengemb_alat' => 1, 'harga_satuan' => 0, 'total_harga' => 7140000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'susilo', 'id_trans_alat' => 2],
            ['id_pengemb_alat' => 5, 'id_alat' => 12, 'jumlah_pengemb_alat' => 20, 'harga_satuan' => 0, 'total_harga' => 11600000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'susilo', 'id_trans_alat' => 3],
            ['id_pengemb_alat' => 6, 'id_alat' => 12, 'jumlah_pengemb_alat' => 8, 'harga_satuan' => 0, 'total_harga' => 320000, 'tgl_pengemb_alat' => '2025-06-04', 'disimpan_oleh' => 'nashir', 'id_trans_alat' => 5],
            ['id_pengemb_alat' => 7, 'id_alat' => 18, 'jumlah_pengemb_alat' => 10, 'harga_satuan' => 0, 'total_harga' => 300000, 'tgl_pengemb_alat' => '2025-06-04', 'disimpan_oleh' => 'nashir', 'id_trans_alat' => 6],
            ['id_pengemb_alat' => 8, 'id_alat' => 12, 'jumlah_pengemb_alat' => 21, 'harga_satuan' => 0, 'total_harga' => 3780000, 'tgl_pengemb_alat' => '2025-06-02', 'disimpan_oleh' => 'roy', 'id_trans_alat' => 4],
            ['id_pengemb_alat' => 9, 'id_alat' => 12, 'jumlah_pengemb_alat' => 30, 'harga_satuan' => 0, 'total_harga' => 600000, 'tgl_pengemb_alat' => '2025-06-04', 'disimpan_oleh' => 'nashir', 'id_trans_alat' => 7],
            ['id_pengemb_alat' => 10, 'id_alat' => 12, 'jumlah_pengemb_alat' => 30, 'harga_satuan' => 0, 'total_harga' => 1200000, 'tgl_pengemb_alat' => '2025-06-04', 'disimpan_oleh' => 'nashir', 'id_trans_alat' => 8],
        ]);
    }
}
