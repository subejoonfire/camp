<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\PengembalianOrderModel;

class PengembalianAlatOrder extends BaseController
{
    protected $orderModel;
    protected $pengembalianModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->pengembalianModel = new PengembalianOrderModel();
    }

    /**
     * Menampilkan form pengembalian dan daftar order.
     */
    public function index()
    {
        // Query dasar dengan join
        $base = $this->orderModel
            ->select('orders.*, alat.nama_alat, kategori.nama_kategori, chatid.username')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->join('kategori', 'kategori.id_kategori = alat.id_kategori')
            ->join('chatid', 'chatid.chatid = orders.chatid');

        // Pending
        $builderPending = clone $base;
        $builderPending->where('orders.status', 'Pending');
        if (session()->get('role') === 'Pelanggan') {
            $builderPending->where('orders.chatid', session()->get('chatid'));
        }
        $ordersPending = $builderPending->orderBy('orders.tanggal_pesan', 'DESC')->findAll();

        // Completed
        $builderReturned = clone $base;
        $builderReturned->where('orders.status', 'Completed');
        if (session()->get('role') === 'Pelanggan') {
            $builderReturned->where('orders.chatid', session()->get('chatid'));
        }
        $ordersReturned = $this->orderModel
            ->select('orders.*, alat.nama_alat, kategori.nama_kategori, chatid.username')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->join('kategori', 'kategori.id_kategori = alat.id_kategori')
            ->join('chatid', 'chatid.chatid = orders.chatid')
            ->where('orders.status', 'Completed')
            ->findAll();

        return view('pengembalianorder/index', [
            'judul'           => 'Pengembalian Alat',
            'ordersPending'   => $ordersPending,
            'ordersReturned'  => $ordersReturned,
        ]);
    }

    /**
     * Simpan data pengembalian.
     */
    public function simpan()
    {
        // Validasi input
        $rules = [
            'id_order'            => 'required',
            'jumlah_pengembalian'  => 'required|integer',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->listErrors())
                ->withInput();
        }

        $idOrder = $this->request->getPost('id_order');
        $jumlahKembali = (int) $this->request->getPost('jumlah_pengembalian');

        // Ambil order
        $order = $this->orderModel->find($idOrder);
        if (! $order) {
            return redirect()->back()->with('errors', 'Order tidak ditemukan');
        }

        // Update status menjadi Completed
        $this->orderModel->update($idOrder, ['status' => 'Completed']);

        // Validasi jumlah
        if ($jumlahKembali > $order['jumlah']) {
            return redirect()->back()->with('errors', 'Jumlah pengembalian melebihi jumlah pesanan');
        }

        // Simpan ke tabel pengembalian
        $this->pengembalianModel->insert([
            'idorder'             => $idOrder,
            'jumlahpengembalian'  => $jumlahKembali,
        ]);

        // Redirect
        session()->setFlashdata('message', 'Pengembalian berhasil disimpan');
        return redirect()->to('/pengembalianorder');
    }
}
