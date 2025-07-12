<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AlatModel;
use App\Models\TransaksiAlatModel;

class TelegramController extends ResourceController
{
    protected $format = 'json';
    private $token = '7979273840:AAFr6W3bifNlQF5vkQSM0HUuiFy7au_cFnM';
    private $alatModel;
    private $transaksiAlatModel;
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new \App\Models\OrderModel();
        $this->alatModel = new AlatModel();
        $this->transaksiAlatModel = new TransaksiAlatModel();
    }

    public function index()
    {
        return view('telegram');
    }

    public function setWebhook()
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/setWebhook?url=" . urlencode('https://5d3574c6e897.ngrok-free.app');
        echo "Setting webhook to: " . $apiURL . "\n";

        $response = file_get_contents($apiURL);
        $data = json_decode($response, true);

        if ($data['ok']) {
            return $this->respond(['success' => true, 'message' => 'Webhook set successfully.']);
        } else {
            return $this->failServerError('Failed to set webhook: ' . $data['description']);
        }
    }

    public function getChatList()
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/getUpdates";

        $response = file_get_contents($apiURL);

        if ($response === false) {
            return $this->failServerError('Gagal mengambil data dari Telegram.');
        }

        $data = json_decode($response, true);

        if (!isset($data['ok']) || !$data['ok']) {
            return $this->failServerError('Response Telegram tidak valid.');
        }

        return $this->respond($data, 200);
    }

    public function sendChat()
    {
        $chatId  = $this->request->getPost('chat_id');
        $message = $this->request->getPost('message');
        if (!$chatId || !$message) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'chat_id dan message wajib diisi.'
            ]);
        }
        $apiURL = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $url    = $apiURL . "?chat_id=" . urlencode($chatId) . "&text=" . urlencode($message);
        $result = file_get_contents($url);

        if ($result === false) {
            file_put_contents('error.log', "Gagal mengirim pesan ke $chatId\n", FILE_APPEND);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengirim pesan.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Pesan berhasil dikirim.'
        ]);
    }

    public function webhook()
    {
        $content = file_get_contents("php://input");
        $update = json_decode($content, true);
        file_put_contents('telegram_update.log', print_r($update, true), FILE_APPEND);
        if (isset($update["message"])) {
            $chatId = $update["message"]["chat"]["id"];
            $text = $update["message"]["text"] ?? '';
            // Call the function to handle commands
            $this->handleCommands($chatId, $text);
        }
        header("HTTP/1.1 200 OK");
        exit;
    }

    private function handleCommands($chatId, $text)
    {
        $command = strtolower(trim($text));
        if (preg_match('/^\/?sewa-(\d+)-(\d+)$/', $command, $matches)) {
            $itemId = (int)$matches[1];
            $quantity = (int)$matches[2];
            $this->handleConfirmOrder($chatId, $itemId, $quantity);
        } elseif ($command === '/selesaisewa') {
            $this->handleSelesaiSewa($chatId);
        } else {
            switch ($command) {
                case '/sewa':
                    $this->handleSewa($chatId);
                    break;
                case '/lihatalat':
                    $this->handleLihatAlat($chatId);
                    break;
                case '/transaksi':
                    $this->handleTransaksi($chatId);
                    break;
                case '/pengembalian':
                    $this->handlePengembalian($chatId);
                    break;
                case '/start':
                    $this->sendMessage($chatId, "Selamat datang! Gunakan perintah:\n- sewa: Untuk menyewa alat\n- lihatalat: Melihat daftar alat\n- transaksi: Melihat riwayat transaksi\n- pengembalian: Mengembalikan alat");
                    break;
                default:
                    $this->sendMessage($chatId, "Perintah tidak dikenali. Ketik /start untuk melihat menu.");
                    break;
            }
        }
    }

    public function handleSewa($chatId)
    {
        $alatTersedia = $this->alatModel->getAlatAda(); // Get available items

        if (empty($alatTersedia)) {
            $this->sendMessage($chatId, "Tidak ada alat yang tersedia untuk disewa.");
            return;
        }

        $message = "Pilih alat yang ingin disewa:\n";
        foreach ($alatTersedia as $alat) {
            $message .= "{$alat['id_alat']}. {$alat['nama_alat']} - Rp" . number_format($alat['harga'], 0, ',', '.') . "/hari (Tersedia: {$alat['jumlah']})\n";
        }
        $message .= "\nBalas dengan nomor alat yang dipilih dan jumlah yang ingin disewa (contoh: 1 2 untuk 2 alat).";

        $this->sendMessage($chatId, $message);
    }

    public function handleConfirmOrder($chatId, $itemId, $quantity)
    {
        $alat = $this->alatModel->find($itemId);
        if (!$alat) {
            $this->sendMessage($chatId, "Barang dengan ID $itemId tidak ditemukan.");
            return;
        }

        if ($quantity > $alat['jumlah']) {
            $this->sendMessage($chatId, "Jumlah yang diminta ($quantity) melebihi stok yang tersedia ({$alat['jumlah']}).");
            return;
        }

        $total_harga = $alat['harga'] * $quantity;

        // Create order
        $orderData = [
            'id_user' => 1,
            'id_alat' => $itemId,
            'jumlah' => $quantity,
            'total_harga' => $total_harga,
            'status' => 'Pending'
        ];
        $this->orderModel->insert($orderData);

        // Update stock
        $this->alatModel->update($itemId, ['jumlah' => $alat['jumlah'] - $quantity]);

        // Buat pesan spesifikasi alat
        $pesan = "Pesanan berhasil!\n\n";
        $pesan .= "Spesifikasi Barang:\n";
        $pesan .= "Nama: {$alat['nama_alat']}\n";
        $pesan .= "Kategori: {$alat['id_kategori']}\n"; // Kalau mau nama kategori, harus join dari tabel kategori
        $pesan .= "Harga per unit: Rp" . number_format($alat['harga'], 0, ',', '.') . "\n";
        $pesan .= "Ukuran: {$alat['ukuran']}\n";
        $pesan .= "Warna: {$alat['warna']}\n";
        $pesan .= "Stok tersisa: " . ($alat['jumlah'] - $quantity) . "\n";
        $pesan .= "Deskripsi: {$alat['deskripsi']}\n\n";
        $pesan .= "Jumlah yang dipesan: $quantity\n";
        $pesan .= "Total harga: Rp" . number_format($total_harga, 0, ',', '.') . "\n\n";
        $pesan .= "Silakan datang ke toko untuk mengambil barang.";

        $this->sendMessage($chatId, $pesan);

        // Jika kamu mau kirim gambar, dan method sendMessage support foto, bisa ditambahkan seperti ini:
        // $this->sendPhoto($chatId, $alat['gambar'], $pesan);
    }


    public function handleSelesaiSewa($chatId)
    {
        $orders = $this->orderModel->getOrdersByUser(session()->get('id_user'));
        $message = "Berikut adalah pesanan Anda:\n";

        foreach ($orders as $order) {
            $alat = $this->alatModel->find($order['id_alat']);
            $message .= "Alat: {$alat['nama_alat']}, Jumlah: {$order['jumlah']}, Total: Rp" . number_format($order['total_harga'], 0, ',', '.') . "\n";
        }

        $this->sendMessage($chatId, $message);
    }


    private function handleLihatAlat($chatId)
    {
        $daftarAlat = $this->alatModel->getAlat(); // Ambil data dari model

        if (empty($daftarAlat)) {
            $this->sendMessage($chatId, "Tidak ada alat yang tersedia.");
            return;
        }

        $message = "📋 Daftar Alat Tersedia:\n";
        foreach ($daftarAlat as $alat) {
            $message .= "➤ {$alat['nama_alat']} - Rp" . number_format($alat['harga'], 0, ',', '.') . "\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleTransaksi($chatId)
    {
        $riwayatTransaksi = $this->transaksiAlatModel->getTransAlat(); // Ambil data dari model

        if (empty($riwayatTransaksi)) {
            $this->sendMessage($chatId, "Anda belum memiliki transaksi.");
            return;
        }

        $message = "📊 Riwayat Transaksi Anda:\n";
        foreach ($riwayatTransaksi as $transaksi) {
            $message .= "🆔 ID: {$transaksi['id_trans_alat']}\n";
            $message .= "🛠️ Alat: {$transaksi['nama_alat']}\n";
            $message .= "📅 Tanggal: {$transaksi['tgl_trans_alat']}\n";
            $message .= "🔄 Status: {$transaksi['status']}\n";
            $message .= "💰 Total: Rp" . number_format($transaksi['total_harga'], 0, ',', '.') . "\n\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handlePengembalian($chatId)
    {
        $this->sendMessage($chatId, "Untuk mengembalikan alat, silakan:\n1. Siapkan alat yang akan dikembalikan\n2. Kirim pesan dengan format:\n   KEMBALIKAN_[ID_ALAT]\n\nContoh: KEMBALIKAN_101");
    }

    private function sendMessage($chatId, $message)
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        file_get_contents($apiURL, false, $context);
    }
}
