<?php

class Barang {
    private $id;
    private $nama;
    private $stok;
    private $gambar;

    public function __construct($id, $nama, $stok, $gambar) {
        $this->id = $id;
        $this->nama = $nama;
        $this->stok = $stok;
        $this->gambar = $gambar;
    }

    public function getId() {
        return $this->id;
    }

    public function getNama() {
        return $this->nama;
    }

    public function getStok() {
        return $this->stok;
    }

    public function getGambar() {
        return $this->gambar;
    }
}
