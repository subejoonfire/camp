<?= $this->extend('layout/template'); ?>

<?= $this->section('content'); ?>

<section class="content">
    <div class="container-fluid">

        <?php if (session()->getFlashdata('errors')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terjadi Kesalahan Inputan </strong>
                <?= session()->getFlashdata('errors'); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Form Transaksi</h3>
            </div>
            <div class="card-body">
                <form action="<?= base_url('transaksi/simpan'); ?>" method="POST">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="disimpan_oleh" value="<?= session()->get('username') ?>">
                    <input type="hidden" name="status" value="Tunggu">

                    <div class="form-row">
                        <div class="col-3">
                            <label class="col-form-label">Tanggal Transaksi</label>
                            <input type="date" name="tgl_trans_alat" id="tgl_trans_alat" class="form-control"
                                value="<?= old('tgl_trans_alat', date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-3">
                            <label class="col-form-label">Tanggal Estimasi Kembali</label>
                            <input type="date" name="tgl_est" id="tgl_est" class="form-control"
                                value="<?= old('tgl_est', date('Y-m-d', strtotime('+3 days'))); ?>" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>Pilih Alat</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Pilih</th>
                                    <th>Nama Alat</th>
                                    <th>Stok</th>
                                    <th>Harga Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alat as $alt) : ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="alat-checkbox"
                                                name="alat[<?= $alt['id_alat'] ?>][id]" value="<?= $alt['id_alat'] ?>">
                                        </td>
                                        <td><?= $alt['nama_alat'] ?></td>
                                        <td><?= $alt['jumlah'] ?></td>
                                        <td>Rp <?= number_format($alt['harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <input type="number" min="0" class="form-control jumlah-input"
                                                name="alat[<?= $alt['id_alat'] ?>][qty]" disabled
                                                data-harga="<?= $alt['harga'] ?>" data-max="<?= $alt['jumlah'] ?>">
                                        </td>
                                        <td class="total-item">Rp 0</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Total Keseluruhan</strong></td>
                                    <td id="total-keseluruhan">Rp 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Data</button>
                        <?php if (session()->get('role') == 'Admin') : ?>
                            <a class="btn btn-secondary btn-sm" href="<?= base_url('transaksi'); ?>">Kembali</a>
                        <?php else : ?>
                            <a class="btn btn-secondary btn-sm" href="<?= base_url('transaksi/plg'); ?>">Kembali</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    function formatRupiah(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function hitungHari(tglMulai, tglSelesai) {
        const satuHari = 24 * 60 * 60 * 1000;
        const tgl1 = new Date(tglMulai);
        const tgl2 = new Date(tglSelesai);
        return Math.round(Math.abs((tgl2 - tgl1) / satuHari)) + 1;
    }

    function hitungTotal() {
        let totalKeseluruhan = 0;
        const tglMulai = document.getElementById('tgl_trans_alat').value;
        const tglSelesai = document.getElementById('tgl_est').value;

        if (!tglMulai || !tglSelesai) return;

        const hari = hitungHari(tglMulai, tglSelesai);

        document.querySelectorAll('.alat-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const inputQty = row.querySelector('.jumlah-input');
            const harga = parseInt(inputQty.dataset.harga);
            const qty = parseInt(inputQty.value) || 0;

            if (qty > 0) {
                const totalItem = harga * qty * hari;
                row.querySelector('.total-item').textContent = formatRupiah(totalItem);
                totalKeseluruhan += totalItem;
            }
        });

        document.getElementById('total-keseluruhan').textContent = formatRupiah(totalKeseluruhan);
    }

    // Aktifkan input jumlah saat checkbox dicentang
    document.querySelectorAll('.alat-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const inputQty = this.closest('tr').querySelector('.jumlah-input');
            inputQty.disabled = !this.checked;

            if (!this.checked) {
                inputQty.value = '';
                this.closest('tr').querySelector('.total-item').textContent = 'Rp 0';
                hitungTotal();
            }
        });
    });

    // Validasi stok saat input jumlah
    document.querySelectorAll('.jumlah-input').forEach(input => {
        input.addEventListener('input', function() {
            const maxStok = parseInt(this.dataset.max);
            if (parseInt(this.value) > maxStok) {
                alert('Jumlah melebihi stok tersedia!');
                this.value = maxStok;
            }
            hitungTotal();
        });
    });

    // Hitung ulang saat tanggal berubah
    document.getElementById('tgl_trans_alat').addEventListener('change', hitungTotal);
    document.getElementById('tgl_est').addEventListener('change', hitungTotal);
</script>
<?= $this->endSection(); ?>