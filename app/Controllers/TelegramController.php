<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AlatModel;
use App\Models\TransaksiAlatModel;
use App\Models\Chatidadmin;


class TelegramController extends ResourceController
{
    protected $format = 'json';
    private $url = 'https://283de3c2b381.ngrok-free.app/telegram/webhook';
    private $token = '7979273840:AAFr6W3bifNlQF5vkQSM0HUuiFy7au_cFnM';
    private $alatModel;
    private $transaksiAlatModel;
    private $orderModel;
    private $chatidAdminModel;

    public function __construct()
    {
        $this->orderModel = new \App\Models\OrderModel();
        $this->alatModel = new AlatModel();
        $this->transaksiAlatModel = new TransaksiAlatModel();
        $this->chatidAdminModel = new Chatidadmin();
    }

    public function index()
    {
        return view('telegram');
    }

    public function setWebhook()
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/setWebhook?url=" . urlencode($this->url);
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
        if (preg_match('/^\/?sewa-(\d+)-(\d+)-(\d+)$/', $command, $matches)) {
            $itemId = (int)$matches[1];
            $quantity = (int)$matches[2];
            $lamaPinjam = (int)$matches[3];
            $this->handleConfirmOrder($chatId, $itemId, $quantity, $lamaPinjam);
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
        $message .= "\n📌 *Cara menyewa:*\n";
        $message .= "Gunakan format `/sewa-{id_alat}-{jumlah}`\n";
        $message .= "Contoh: `/sewa-1-2` untuk menyewa 2 unit alat dengan ID 1.\n";
        $message .= "\n🛑 Untuk mengakhiri sewa, gunakan perintah /selesaisewa.";

        $this->sendMessage($chatId, $message);
    }
    private function getTelegramUsername($chatId)
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/getChat?chat_id={$chatId}";
        $response = file_get_contents($apiURL);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['ok']) && isset($data['result']['username'])) {
                return $data['result']['username'];
            }
        }
        return null;
    }

    public function handleConfirmOrder($chatId, $itemId, $quantity, $lamaPinjam)
    {
        helper('date');

        // Cek apakah chat ID sudah tersimpan
        $chatModel = new \App\Models\Chatid();
        $existing = $chatModel->where('chatid', $chatId)->first();

        if (!$existing) {
            $username = $this->getTelegramUsername($chatId);
            $chatModel->insert([
                'chatid' => $chatId,
                'username' => $username ?? null
            ]);
        }

        // Ambil data alat + kategori
        $alat = $this->alatModel
            ->select('alat.*, kategori.nama_kategori')
            ->join('kategori', 'kategori.id_kategori = alat.id_kategori')
            ->where('alat.id_alat', $itemId)
            ->first();

        if (!$alat) {
            $this->sendMessage($chatId, "Barang dengan ID $itemId tidak ditemukan.");
            return;
        }

        if ($quantity > $alat['jumlah']) {
            $this->sendMessage($chatId, "Jumlah yang diminta ($quantity) melebihi stok yang tersedia ({$alat['jumlah']}).");
            return;
        }

        // Hitung total harga: harga * jumlah * hari
        $total_harga = $alat['harga'] * $quantity * $lamaPinjam;

        // Hitung tanggal pengembalian
        $tanggal_pesan = date('Y-m-d');
        $tanggal_pengembalian = date('Y-m-d', strtotime("+$lamaPinjam days"));

        // Insert order
        $orderData = [
            'chatid' => $chatId,
            'id_alat' => $itemId,
            'jumlah' => $quantity,
            'total_harga' => $total_harga,
            'status' => 'Pending',
            'tanggal_pesan' => $tanggal_pesan,
            'tanggal_pengembalian' => $tanggal_pengembalian
        ];
        $this->orderModel->insert($orderData);

        // Kurangi stok
        $this->alatModel->update($itemId, [
            'jumlah' => $alat['jumlah'] - $quantity
        ]);

        // Kirim pesan
        $pesan = "✅ Pesanan berhasil!\n\n";
        $pesan .= "📦 Nama: {$alat['nama_alat']}\n";
        $pesan .= "📁 Kategori: {$alat['nama_kategori']}\n";
        $pesan .= "💸 Harga / hari / unit: Rp" . number_format($alat['harga'], 0, ',', '.') . "\n";
        $pesan .= "🔢 Jumlah: $quantity unit\n";
        $pesan .= "🗓️ Lama sewa: $lamaPinjam hari\n";
        $pesan .= "💰 Total: Rp" . number_format($total_harga, 0, ',', '.') . "\n";
        $pesan .= "📅 Tanggal pengembalian: $tanggal_pengembalian\n";
        $pesan .= "\nSilakan datang ke toko untuk pengambilan alat.";

        $this->sendMessage($chatId, $pesan);
        $this->notifyAdminsOrder($orderData, $alat);
    }
    public function notifyAdminsOrder($order = null, $alat = null)
    {
        $admins = $this->chatidAdminModel->findAll();
        if (empty($admins)) {
            return;
        }

        // Ambil username dari chatid
        $chatModel = new \App\Models\Chatid();
        $user = $chatModel->where('chatid', $order['chatid'])->first();
        $username = $user['username'] ?? '(tanpa username)';

        $message = "📢 <b>Pesanan Baru!</b>\n\n";
        $message .= "👤 Pelanggan: @$username\n"; // Tambahkan nama pelanggan
        $message .= "📦 Nama Alat: {$alat['nama_alat']}\n";
        $message .= "📁 Kategori: {$alat['nama_kategori']}\n";
        $message .= "🔢 Jumlah: {$order['jumlah']} unit\n";
        $message .= "🗓️ Lama sewa: " . ((strtotime($order['tanggal_pengembalian']) - strtotime($order['tanggal_pesan'])) / 86400) . " hari\n";
        $message .= "💰 Total: Rp" . number_format($order['total_harga'], 0, ',', '.') . "\n";
        $message .= "📅 Tgl Pesan: {$order['tanggal_pesan']}\n";
        $message .= "📅 Tgl Kembali: {$order['tanggal_pengembalian']}\n";

        foreach ($admins as $admin) {
            $this->sendMessage($admin['chatid'], $message);
        }
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

    public function handleTransaksi($chatId)
    {
        $orders = $this->orderModel
            ->select('orders.*, alat.nama_alat, kategori.nama_kategori')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->join('kategori', 'alat.id_kategori = kategori.id_kategori')
            ->where('orders.chatid', $chatId)
            ->orderBy('orders.tanggal_pesan', 'DESC')
            ->findAll();
        if (empty($orders)) {
            $this->sendMessage($chatId, "Anda belum memiliki transaksi pemesanan.");
            return;
        }

        $message = "📦 Riwayat Pemesanan Anda:\n";
        foreach ($orders as $order) {
            $message .= "🆔 ID Order: {$order['id_order']}\n";
            $message .= "🛠️ Alat: {$order['nama_alat']}\n";
            $message .= "🛠️ Kategori: {$order['nama_kategori']}\n";
            $message .= "🔢 Jumlah: {$order['jumlah']}\n";
            $message .= "💰 Total: Rp" . number_format($order['total_harga'], 0, ',', '.') . "\n";
            $message .= "📅 Tgl Pesan: {$order['tanggal_pesan']}\n";
            $message .= "📅 Tgl Kembali: {$order['tanggal_pengembalian']}\n";
            $message .= "📌 Status: {$order['status']}\n\n";
        }

        $this->sendMessage($chatId, $message);
    }


    private function handlePengembalian($chatId)
    {
        $this->sendMessage(
            $chatId,
            "Untuk mengembalikan alat, silakan:\n
        1. Siapkan alat yang akan dikembalikan\n
        2. Kirim pesan dengan format:\n   
        KEMBALIKAN_[ID_ALAT]\n\n
        Contoh: KEMBALIKAN_101"
        );
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
    public function handleChatIdList()
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/getUpdates";
        $response = file_get_contents($apiURL);

        if (!$response) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengambil data dari Telegram API.'
            ]);
        }

        $data = json_decode($response, true);

        if (!isset($data['ok']) || !$data['ok']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid dari Telegram.'
            ]);
        }

        $chats = [];

        foreach ($data['result'] as $update) {
            if (!isset($update['message'])) continue;

            $chat = $update['message']['chat'];
            $chatId = $chat['id'];

            // Hindari duplikat
            if (!isset($chats[$chatId])) {
                $chats[$chatId] = [
                    'chatid'   => $chatId,
                    'username' => $chat['username'] ?? '(tanpa username)',
                    'name'     => $chat['first_name'] ?? '' . ' ' . ($chat['last_name'] ?? ''),
                ];
            }
        }

        // Reset index array agar rapi
        $chatList = array_values($chats);

        return $this->response->setJSON([
            'success' => true,
            'count'   => count($chatList),
            'data'    => $chatList
        ]);
    }
}
