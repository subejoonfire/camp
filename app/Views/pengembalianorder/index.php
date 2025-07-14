<?= $this->extend('layout/template'); ?>
<?= $this->section('content'); ?>

<section class="content">
    <div class="container-fluid">
        <!-- Pesan sukses -->
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('message'); ?></div>
        <?php endif; ?>

        <!-- Validasi error -->
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('errors'); ?></div>
        <?php endif; ?>

        <!-- Form Pengembalian -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Form Pengembalian</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('pengembalianorder/simpan'); ?>" method="POST">
                    <?= csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="id_order">Pilih Order (Pending)</label>
                            <select id="id_order" name="id_order" class="form-control">
                                <option value="">-- Pilih Order --</option>
                                <?php foreach ($ordersPending as $o): ?>
                                    <option value="<?= $o['id_order']; ?>" data-jumlah="<?= $o['jumlah']; ?>"
                                        data-harga="<?= $o['total_harga']; ?>">
                                        <?= sprintf(
                                            "#%s | %s (%s) | User: %s | Jumlah: %s | Pesan: %s | Est.: %s",
                                            $o['id_order'],
                                            $o['nama_alat'],
                                            $o['nama_kategori'],
                                            $o['username'],
                                            $o['jumlah'],
                                            date('d F Y', strtotime($o['tanggal_pesan'])),
                                            date('d F Y', strtotime($o['tanggal_pengembalian']))
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Jumlah Pesanan</label>
                            <input type="text" id="jumlah_pesanan" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="jumlah_pengembalian">Jumlah Pengembalian</label>
                            <input type="number" min="1" name="jumlah_pengembalian" id="jumlah_pengembalian"
                                class="form-control" />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Total Harga (Order)</label>
                            <input type="text" id="total_harga" class="form-control" readonly>
                        </div>
                        <div class="form-group col-md-4 align-self-end">
                            <button type="submit" class="btn btn-success btn-block">Simpan Pengembalian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Pending -->
        <h5>Order Belum Dikembalikan</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#Order</th>
                        <th>Alat</th>
                        <th>Kategori</th>
                        <th>User</th>
                        <th>Jumlah</th>
                        <th>Pesan</th>
                        <th>Est. Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordersPending as $o): ?>
                        <tr>
                            <td><?= $o['id_order'] ?></td>
                            <td><?= $o['nama_alat'] ?></td>
                            <td><?= $o['nama_kategori'] ?></td>
                            <td><?= $o['username'] ?></td>
                            <td><?= $o['jumlah'] ?></td>
                            <td><?= date('d F Y', strtotime($o['tanggal_pesan'])) ?></td>
                            <td><?= date('d F Y', strtotime($o['tanggal_pengembalian'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel Returned -->
        <h5>Order Sudah Dikembalikan</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#Order</th>
                        <th>Alat</th>
                        <th>Kategori</th>
                        <th>User</th>
                        <th>Jumlah</th>
                        <th>Pesan</th>
                        <th>Est. Kembali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordersReturned as $o): ?>
                        <tr>
                            <td><?= $o['id_order'] ?></td>
                            <td><?= $o['nama_alat'] ?></td>
                            <td><?= $o['nama_kategori'] ?></td>
                            <td><?= $o['username'] ?></td>
                            <td><?= $o['jumlah'] ?></td>
                            <td><?= date('d F Y', strtotime($o['tanggal_pesan'])) ?></td>
                            <td><?= date('d F Y', strtotime($o['tanggal_pengembalian'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    function formatHarga(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    var selectOrder = document.getElementById('id_order');
    selectOrder.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        document.getElementById('jumlah_pesanan').value = opt.getAttribute('data-jumlah');
        document.getElementById('total_harga').value = formatHarga(opt.getAttribute('data-harga'));
    });

    function validateReturn() {
        var returnQty = parseInt(document.getElementById('jumlah_pengembalian').value) || 0;
        var orderQty = parseInt(document.getElementById('jumlah_pesanan').value) || 0;
        if (returnQty > orderQty) {
            alert('Jumlah pengembalian tidak boleh melebihi jumlah pesanan');
            document.getElementById('jumlah_pengembalian').value = '';
        }
    }
</script>
<?= $this->endSection(); ?>