<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAllTables extends Migration
{
    public function up()
    {
        // kategori
        $this->forge->addField([
            'id_kategori' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'nama_kategori' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('id_kategori', true);
        $this->forge->createTable('kategori');

        // alat
        $this->forge->addField([
            'id_alat' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_alat' => ['type' => 'VARCHAR', 'constraint' => 100],
            'harga' => ['type' => 'INT', 'default' => 0],
            'id_kategori' => ['type' => 'INT', 'unsigned' => true],
            'gambar' => ['type' => 'VARCHAR', 'constraint' => 255],
            'ukuran' => ['type' => 'VARCHAR', 'constraint' => 50],
            'warna' => ['type' => 'VARCHAR', 'constraint' => 100],
            'jumlah' => ['type' => 'INT'],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_alat', true);
        $this->forge->createTable('alat');

        // chatid
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'chatid' => ['type' => 'VARCHAR', 'constraint' => 255],
            'username' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('chatid');

        // chatidadmin
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'chatid' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('chatidadmin');

        // users
        $this->forge->addField([
            'id_user' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'CHAR', 'constraint' => 50],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'nama_lengkap' => ['type' => 'VARCHAR', 'constraint' => 100],
            'role' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('id_user', true);
        $this->forge->createTable('users');

        // orders
        $this->forge->addField([
            'id_order' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'chatid' => ['type' => 'VARCHAR', 'constraint' => 255],
            'id_alat' => ['type' => 'INT', 'unsigned' => true],
            'jumlah' => ['type' => 'INT'],
            'total_harga' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Completed'], 'default' => 'Pending'],
            'tanggal_pesan' => ['type' => 'DATE'],
            'tanggal_pengembalian' => ['type' => 'DATE'],
        ]);
        $this->forge->addKey('id_order', true);
        $this->forge->createTable('orders');

        // transaksi
        $this->forge->addField([
            'id_trans_alat' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_alat' => ['type' => 'INT', 'unsigned' => true],
            'jumlah_trans_alat' => ['type' => 'INT'],
            'total_harga' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'tgl_trans_alat' => ['type' => 'DATE'],
            'tgl_est' => ['type' => 'DATE', 'null' => true],
            'disimpan_oleh' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status' => ['type' => 'ENUM', 'constraint' => ['Disetujui', 'Tunggu', 'Tolak']],
        ]);
        $this->forge->addKey('id_trans_alat', true);
        $this->forge->createTable('transaksi');

        // pengembalian
        $this->forge->addField([
            'id_pengemb_alat' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'id_alat' => ['type' => 'INT', 'unsigned' => true],
            'jumlah_pengemb_alat' => ['type' => 'INT'],
            'harga_satuan' => ['type' => 'FLOAT', 'constraint' => '10,2'],
            'total_harga' => ['type' => 'FLOAT', 'constraint' => '10,2'],
            'tgl_pengemb_alat' => ['type' => 'DATE'],
            'disimpan_oleh' => ['type' => 'VARCHAR', 'constraint' => 100],
            'id_trans_alat' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id_pengemb_alat', true);
        $this->forge->addKey('id_trans_alat');
        $this->forge->createTable('pengembalian');
    }

    public function down()
    {
        $this->forge->dropTable('pengembalian');
        $this->forge->dropTable('transaksi');
        $this->forge->dropTable('orders');
        $this->forge->dropTable('users');
        $this->forge->dropTable('chatidadmin');
        $this->forge->dropTable('chatid');
        $this->forge->dropTable('alat');
        $this->forge->dropTable('kategori');
    }
}
