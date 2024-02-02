<?php

class Peminjaman {
    private $id;
    private $barang;
    private $peminjam;
    private $admin;
    private $tanggalPakai;
    private $jumlah;

    public function __construct($id, $barang, $peminjam, $admin, $tanggalPakai, $jumlah) {
        $this->id = $id;
        $this->barang = $barang;
        $this->peminjam = $peminjam;
        $this->admin = $admin;
        $this->tanggalPakai = $tanggalPakai;
        $this->jumlah = $jumlah;
    }

    public function getBarang() {
        return $this->barang;
    }

    public function getPeminjam() {
        return $this->peminjam;
    }

    public function getAdmin() {
        return $this->admin;
    }

    public function getTanggalPakai() {
        return $this->tanggalPakai;
    }

    public function getJumlah() {
        return $this->jumlah;
    }
}
