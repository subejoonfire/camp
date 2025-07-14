<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class Order extends BaseController
{
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * Tampilkan daftar order berstatus Pending
     */
    public function index()
    {
        $orders = $this->orderModel
            ->select('orders.*, alat.nama_alat, kategori.nama_kategori, chatid.username')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->join('kategori', 'kategori.id_kategori = alat.id_kategori')
            ->join('chatid', 'chatid.chatid = orders.chatid')
            // ->where('orders.status', 'Pending')
            ->orderBy('orders.tanggal_pesan', 'DESC')
            ->findAll();

        return view('order/index', [
            'judul' => 'Daftar Order',
            'orders' => $orders,
        ]);
    }

    /**
     * Proses approve: ubah status jadi Completed
     */
    public function approve($id)
    {
        $this->orderModel->update($id, ['status' => 'Completed']);
        return redirect()->to('/order')
            ->with('message', 'Order #' . $id . ' berhasil disetujui.');
    }
}
