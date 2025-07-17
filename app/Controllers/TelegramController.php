<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AlatModel;
use App\Models\TransaksiAlatModel;
use App\Models\Chatidadmin;
use App\Models\PengembalianOrderModel;

class TelegramController extends ResourceController
{
    protected $format = 'json';
    private $url = 'https://4d936da54601.ngrok-free.app/telegram/webhook';
    private $token = '7979273840:AAFr6W3bifNlQF5vkQSM0HUuiFy7au_cFnM';
    private $alatModel;
    private $orderModel;
    private $transaksiAlatModel;
    private $chatidAdminModel;
    private $pengembalianOrderModel;

    // State untuk menyimpan informasi sementara selama interaksi
    private $userStates = [];

    public function __construct()
    {
        $this->orderModel = new \App\Models\OrderModel();
        $this->alatModel = new AlatModel();
        $this->transaksiAlatModel = new TransaksiAlatModel();
        $this->chatidAdminModel = new Chatidadmin();
        $this->pengembalianOrderModel = new PengembalianOrderModel();

        // Load user states from a persistent storage (e.g., file, database, or cache)
        // For simplicity, we'll use a file here. In production, consider Redis or database.
        $stateFile = WRITEPATH . 'telegram_user_states.json';
        if (file_exists($stateFile)) {
            $this->userStates = json_decode(file_get_contents($stateFile), true);
        }
    }

    private function saveUserStates()
    {
        $stateFile = WRITEPATH . 'telegram_user_states.json';
        file_put_contents($stateFile, json_encode($this->userStates));
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
        $currentState = $this->userStates[$chatId]['state'] ?? null;
        // if ($command === '/batal') {
        //     $this->handleBatalPesanan($chatId);
        //     $currentState == null; // Reset state jika ada
        //     return; // Langsung return agar tidak lanjut ke state lainnya
        // }

        if ($currentState === 'awaiting_item_id') {
            $this->processItemId($chatId, $command);
        } elseif ($currentState === 'awaiting_quantity') {
            $this->processQuantity($chatId, $command);
        } elseif ($currentState === 'awaiting_duration') {
            $this->processDuration($chatId, $command);
        } elseif ($currentState === 'awaiting_return_order_id') {
            $this->processReturnOrderId($chatId, $command);
        } elseif ($currentState === 'awaiting_return_quantity') {
            $this->processReturnQuantity($chatId, $command);
        } else {
            // Normal command handling
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
                case '/selesaisewa':
                    $this->handleSelesaiSewa($chatId);
                    break;
                case '/start':
                    $this->sendMessage($chatId, "Selamat datang! Gunakan perintah:\n- /sewa: Untuk menyewa alat\n- /lihatalat: Melihat daftar alat\n- /transaksi: Melihat riwayat transaksi\n- /selesaisewa: Mengembalikan alat");
                    break;
                default:
                    $this->sendMessage($chatId, "Perintah tidak dikenali. Ketik /start untuk melihat menu.");
                    break;
            }
        }
        $this->saveUserStates();
    }
    private function handleBatalPesanan($chatId)
    {
        if (isset($this->userStates[$chatId])) {
            unset($this->userStates[$chatId]);
            file_put_contents('debug.log', "Pesanan dibatalkan oleh chat $chatId\n", FILE_APPEND);
            $this->sendMessage($chatId, "✅ Proses pemesanan berhasil dibatalkan");
        } else {
            $this->sendMessage($chatId, "⚠️ Tidak ada pesanan yang sedang diproses");
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
        $message .= "\nSilakan masukkan *ID alat* yang ingin Anda sewa:";

        $this->userStates[$chatId] = ['state' => 'awaiting_item_id'];
        $this->sendMessage($chatId, $message);
    }

    private function processItemId($chatId, $itemId)
    {
        if (!is_numeric($itemId)) {
            $this->sendMessage($chatId, "ID alat harus berupa angka. Silakan masukkan kembali ID alat:");
            return;
        }

        $alat = $this->alatModel->find((int)$itemId);
        if (!$alat || $alat['jumlah'] <= 0) {
            $this->sendMessage($chatId, "ID alat tidak valid atau stok habis. Silakan masukkan kembali ID alat yang tersedia:");
            return;
        }

        $this->userStates[$chatId]['item_id'] = (int)$itemId;
        $this->userStates[$chatId]['state'] = 'awaiting_quantity';
        $this->sendMessage($chatId, "Anda memilih *{$alat['nama_alat']}*. Berapa *jumlah* yang ingin Anda sewa? (Tersedia: {$alat['jumlah']})");
    }

    private function processQuantity($chatId, $quantity)
    {
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            $this->sendMessage($chatId, "Jumlah harus berupa angka positif. Silakan masukkan kembali jumlah:");
            return;
        }

        $itemId = $this->userStates[$chatId]['item_id'];
        $alat = $this->alatModel->find($itemId);

        if ((int)$quantity > $alat['jumlah']) {
            $this->sendMessage($chatId, "Jumlah yang diminta ($quantity) melebihi stok yang tersedia ({$alat['jumlah']}). Silakan masukkan kembali jumlah:");
            return;
        }

        $this->userStates[$chatId]['quantity'] = (int)$quantity;
        $this->userStates[$chatId]['state'] = 'awaiting_duration';
        $this->sendMessage($chatId, "Berapa lama (dalam *hari*) Anda ingin menyewa alat ini?");
    }

    private function processDuration($chatId, $duration)
    {
        file_put_contents('debug.log', "Processing duration: $duration\n", FILE_APPEND);

        if (!is_numeric($duration) || (int)$duration <= 0) {
            $logMessage = "Invalid duration input from chat $chatId: '$duration'\n";
            file_put_contents('error.log', $logMessage, FILE_APPEND);

            $this->sendMessage($chatId, "⛔ Lama sewa harus berupa angka positif (dalam hari). Contoh: 3 untuk 3 hari.\nSilakan masukkan kembali lama sewa:");
            return;
        }

        $duration = (int)$duration;
        $this->userStates[$chatId]['duration'] = $duration;

        file_put_contents('debug.log', "Valid duration received: $duration days from chat $chatId\n", FILE_APPEND);

        // Process the order
        $itemId = $this->userStates[$chatId]['item_id'];
        $quantity = $this->userStates[$chatId]['quantity'];
        $lamaPinjam = $duration;

        $this->handleConfirmOrder($chatId, $itemId, $quantity, $lamaPinjam);

        // Clear state after successful order
        unset($this->userStates[$chatId]);
        file_put_contents('debug.log', "Order processed successfully for chat $chatId\n", FILE_APPEND);
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

        $username = $this->getTelegramUsername($chatId);

        $chatModel = new \App\Models\Chatid();
        $existing = $chatModel->where('chatid', $chatId)->first();
        if (!$existing) {
            $insertData = [
                'chatid' => $chatId,
                'username' => $username ?: '(tanpa username)',
            ];
            $chatModel->insert($insertData);
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
        // Get orders that are 'Pending' or 'Completed' but not yet fully returned
        $ordersToReturn = $this->orderModel
            ->select('orders.*, alat.nama_alat, kategori.nama_kategori')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->join('kategori', 'alat.id_kategori = kategori.id_kategori')
            ->where('orders.chatid', $chatId)
            ->whereIn('orders.status', ['Pending', 'Completed']) // Include completed if not fully returned
            ->findAll();

        if (empty($ordersToReturn)) {
            $this->sendMessage($chatId, "Anda tidak memiliki pesanan yang perlu dikembalikan.");
            return;
        }

        $message = "Berikut adalah pesanan Anda yang bisa dikembalikan:\n\n";
        foreach ($ordersToReturn as $order) {
            // Check if there's any remaining quantity to return
            $returnedQuantity = $this->pengembalianOrderModel->where('idorder', $order['id_order'])->selectSum('jumlahpengembalian')->first()['jumlahpengembalian'] ?? 0;
            $remainingQuantity = $order['jumlah'] - $returnedQuantity;

            if ($remainingQuantity > 0) {
                $message .= "ID Order: *{$order['id_order']}*\n";
                $message .= "Alat: {$order['nama_alat']}\n";
                $message .= "Jumlah Disewa: {$order['jumlah']}\n";
                $message .= "Jumlah Sudah Dikembalikan: {$returnedQuantity}\n";
                $message .= "Jumlah Belum Dikembalikan: *{$remainingQuantity}*\n";
                $message .= "Status: {$order['status']}\n\n";
            }
        }

        if (strpos($message, "ID Order:") === false) {
            $this->sendMessage($chatId, "Anda tidak memiliki pesanan yang perlu dikembalikan.");
            return;
        }

        $this->userStates[$chatId] = ['state' => 'awaiting_return_order_id'];
        $this->sendMessage($chatId, $message . "Silakan masukkan *ID Order* yang ingin Anda kembalikan:");
    }

    private function processReturnOrderId($chatId, $orderId)
    {
        if (!is_numeric($orderId)) {
            $this->sendMessage($chatId, "ID Order harus berupa angka. Silakan masukkan kembali ID Order:");
            return;
        }

        $order = $this->orderModel->where('id_order', (int)$orderId)
            ->where('chatid', $chatId)
            ->whereIn('status', ['Pending', 'Completed'])
            ->first();

        if (!$order) {
            $this->sendMessage($chatId, "ID Order tidak valid atau bukan milik Anda. Silakan masukkan kembali ID Order:");
            return;
        }

        $returnedQuantity = $this->pengembalianOrderModel->where('idorder', $order['id_order'])->selectSum('jumlahpengembalian')->first()['jumlahpengembalian'] ?? 0;
        $remainingQuantity = $order['jumlah'] - $returnedQuantity;

        if ($remainingQuantity <= 0) {
            $this->sendMessage($chatId, "Pesanan ini sudah sepenuhnya dikembalikan. Silakan pilih ID Order lain:");
            return;
        }

        $this->userStates[$chatId]['return_order_id'] = (int)$orderId;
        $this->userStates[$chatId]['state'] = 'awaiting_return_quantity';
        $this->sendMessage($chatId, "Anda memilih Order ID *{$order['id_order']}* (Alat: {$order['nama_alat']}). Berapa *jumlah* yang ingin Anda kembalikan? (Sisa: {$remainingQuantity})");
    }

    private function processReturnQuantity($chatId, $returnQuantity)
    {
        if (!is_numeric($returnQuantity) || (int)$returnQuantity <= 0) {
            $this->sendMessage($chatId, "Jumlah pengembalian harus berupa angka positif. Silakan masukkan kembali jumlah:");
            return;
        }

        $orderId = $this->userStates[$chatId]['return_order_id'];
        $order = $this->orderModel->find($orderId);

        $returnedQuantity = $this->pengembalianOrderModel->where('idorder', $order['id_order'])->selectSum('jumlahpengembalian')->first()['jumlahpengembalian'] ?? 0;
        $remainingQuantity = $order['jumlah'] - $returnedQuantity;

        if ((int)$returnQuantity > $remainingQuantity) {
            $this->sendMessage($chatId, "Jumlah pengembalian ($returnQuantity) melebihi sisa yang belum dikembalikan ({$remainingQuantity}). Silakan masukkan kembali jumlah:");
            return;
        }

        // Process the return
        $this->pengembalianOrderModel->insert([
            'idorder' => $orderId,
            'jumlahpengembalian' => (int)$returnQuantity,
        ]);

        // Update alat stock
        $alat = $this->alatModel->find($order['id_alat']);
        $this->alatModel->update($order['id_alat'], [
            'jumlah' => $alat['jumlah'] + (int)$returnQuantity
        ]);

        // Check if all items are returned for this order
        $totalReturned = $this->pengembalianOrderModel->where('idorder', $orderId)->selectSum('jumlahpengembalian')->first()['jumlahpengembalian'] ?? 0;
        if ($totalReturned >= $order['jumlah']) {
            $this->orderModel->update($orderId, ['status' => 'Returned']);
            $this->sendMessage($chatId, "✅ Semua alat untuk Order ID *{$orderId}* telah berhasil dikembalikan.");
        } else {
            $this->sendMessage($chatId, "✅ *" . $returnQuantity . "* alat untuk Order ID *" . $orderId . "* berhasil dikembalikan. Sisa *" . ((int)$remainingQuantity - (int)$returnQuantity) . "* alat belum dikembalikan.");
        }

        // Clear state after successful return
        unset($this->userStates[$chatId]);
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
            $message .= "🆔 ID Order: *{$order['id_order']}*\n";
            $message .= "🛠️ Alat: {$order['nama_alat']}\n";
            $message .= "🛠️ Kategori: {$order['nama_kategori']}\n";
            $message .= "🔢 Jumlah: {$order['jumlah']}\n";
            $message .= "💰 Total: Rp" . number_format($order['total_harga'], 0, ',', '.') . "\n";
            $message .= "📅 Tgl Pesan: {$order['tanggal_pesan']}\n";
            $message .= "📅 Tgl Kembali: {$order['tanggal_pengembalian']}\n";
            $message .= "📌 Status: *{$order['status']}*\n\n";
        }

        $this->sendMessage($chatId, $message);
    }


    private function handlePengembalian($chatId)
    {
        // Ambil semua pengembalian user berdasarkan chatid
        $returns = $this->pengembalianOrderModel
            ->select('pengembalianorder.jumlahpengembalian, orders.id_order, orders.jumlah as total_pesan, orders.tanggal_pesan, orders.tanggal_pengembalian, alat.nama_alat')
            ->join('orders', 'orders.id_order = pengembalianorder.idorder')
            ->join('alat', 'alat.id_alat = orders.id_alat')
            ->where('orders.chatid', $chatId)
            ->findAll();

        if (empty($returns)) {
            $this->sendMessage($chatId, "🎉 Anda belum melakukan pengembalian apapun.");
            return;
        }

        $message = "📥 Daftar Pengembalian Anda:\n\n";
        foreach ($returns as $ret) {
            $message .= "🆔 Order: *#{$ret['id_order']}*\n";
            $message .= "📦 Alat: {$ret['nama_alat']}\n";
            $message .= "🔢 Jumlah Dikembalikan: {$ret['jumlahpengembalian']} / {$ret['total_pesan']}\n";
            $message .= "📅 Dipesan: " . date('d F Y', strtotime($ret['tanggal_pesan'])) . "\n";
            $message .= "📅 Est. Kembali: " . date('d F Y', strtotime($ret['tanggal_pengembalian'])) . "\n\n";
        }

        $this->sendMessage($chatId, $message);
    }


    private function sendMessage($chatId, $message)
    {
        $apiURL = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML' // Use HTML for bold text
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
