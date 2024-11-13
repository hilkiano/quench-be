<?php

return [
    /**
     * COMMON TRANSLATIONS
     */
    'error' => [
        '500' => 'Kesalahan Server Internal',
        'token' => 'Email/nama pengguna atau kata sandi tidak sesuai.',
        'token_not_parsed' => 'Token tidak termasuk dalam permintaan Anda. Penguraian gagal.',
        'token_expired' => 'Token telah kedaluwarsa. Mintalah token baru.',
        'token_invalid' => 'Token tidak valid. Penguraian gagal.',
        'product_limit_reached' => 'Batas maksimum produk telah diraih. Anda tidak dapat membuat produk baru.',
        'check_quota' => 'Gagal mengecek kuota. Tipe :type tidak valid.',
        'not_found' => 'Rute :route tidak ditemukan.',
        'wrong_credentials' => "Kredensial yang diberikan salah. Tidak dapat menghasilkan token akses baru.",
        's13_null_data' => 'Data tidak ditemukan untuk tahun :year.',
        's13_upload_failed' => 'Tidak dapat menghasilkan link unduhan.',
        'no_access' => 'Anda tidak memiliki akses untuk tindakan ini.'
    ],
    'message' => [
        'product_toggle' => 'Produk :productid telah :action.',
        'product_update' => 'Produk :productid telah diperbarui.',
        'product_unit_empty' => 'Satuan produk kosong. Harap buat yang baru terlebih dahulu.',
        'product_tag_empty' => 'Label produk kosong. Harap buat yang baru terlebih dahulu.',
        'product_category_empty' => 'Kategori produk kosong. Harap buat yang baru terlebih dahulu.',
        'logged_out' => 'Saudara telah log out.',
    ],
    'state' => [
        'disabled' => 'dinonaktifkan',
        'enabled' => 'diaktifkan',
        'success' => 'Sukses',
        'failed' => 'Gagal'
    ]
];
