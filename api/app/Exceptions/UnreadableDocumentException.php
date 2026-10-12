<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Berkas yang di-upload tidak bisa diubah jadi teks: format tidak didukung,
 * rusak, terproteksi, hasil scan, atau melewati batas halaman. Pesan exception
 * ini langsung ditampilkan ke user, jadi tulis dalam bahasa Indonesia yang jelas.
 */
class UnreadableDocumentException extends RuntimeException
{
}
