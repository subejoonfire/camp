<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id_order';
    protected $allowedFields = ['id_user', 'id_alat', 'chatid', 'jumlah', 'total_harga', 'status', 'tanggal_pesan', 'tanggal_pengembalian'];

    public function createOrder($data)
    {
        return $this->insert($data);
    }

    public function getOrdersByUser($userId)
    {
        return $this->where('id_user', $userId)->findAll();
    }
}
