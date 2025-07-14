<?= $this->extend('layout/template'); ?>
<?= $this->section('content'); ?>

<section class="content">
    <div class="container-fluid">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('message') ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#Order</th>
                            <th>Alat</th>
                            <th>Kategori</th>
                            <th>User</th>
                            <th>Jumlah</th>
                            <th>Total Harga</th>
                            <th>Tgl Pesan</th>
                            <th>Est. Kembali</th>
                            <!-- <th style="width:100px">Aksi</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada order pending.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= $o['id_order'] ?></td>
                                    <td><?= esc($o['nama_alat']) ?></td>
                                    <td><?= esc($o['nama_kategori']) ?></td>
                                    <td><?= esc($o['username']) ?></td>
                                    <td><?= $o['jumlah'] ?></td>
                                    <td>Rp<?= number_format($o['total_harga'], 0, ',', '.') ?></td>
                                    <td><?= date('d F Y', strtotime($o['tanggal_pesan'])) ?></td>
                                    <td><?= date('d F Y', strtotime($o['tanggal_pengembalian'])) ?></td>
                                    <!-- <td>
                                        <?php if ($o['status'] === 'Pending'): ?>
                                            <form action="<?= base_url("order/approve/{$o['id_order']}") ?>" method="POST"
                                                onsubmit="return confirm('Setujui order #<?= $o['id_order'] ?>?')"
                                                style="display:inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    Approve
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Approved</span>
                                        <?php endif; ?>
                                    </td> -->

                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection(); ?>