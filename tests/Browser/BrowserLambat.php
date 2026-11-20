<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

/**
 * Browser Dusk yang bisa diperlambat supaya jalannya test enak ditonton di jendela Chrome.
 *
 * Diatur lewat .env.dusk.local atau langsung di terminal:
 *   DUSK_SLOW_MS       jeda (milidetik) setelah setiap aksi: buka halaman, klik, tekan tombol, isi form
 *   DUSK_SLOW_TYPE_MS  jeda (milidetik) antar huruf saat mengetik
 * Nilai 0 (bawaan) = kecepatan normal.
 */
class BrowserLambat extends Browser
{
    public static int $jeda = 0;

    public static int $jedaKetik = 0;

    /** Berhenti sebentar setelah satu aksi, hanya bila mode lambat aktif. */
    protected function jedaSetelahAksi(): static
    {
        if (static::$jeda > 0) {
            parent::pause(static::$jeda);
        }

        return $this;
    }

    public function visit($url)
    {
        parent::visit($url);

        return $this->jedaSetelahAksi();
    }

    public function click($selector = null)
    {
        parent::click($selector);

        return $this->jedaSetelahAksi();
    }

    public function clickLink($link, $element = 'a')
    {
        parent::clickLink($link, $element);

        return $this->jedaSetelahAksi();
    }

    public function press($button)
    {
        parent::press($button);

        return $this->jedaSetelahAksi();
    }

    /** Mengetik huruf demi huruf bila DUSK_SLOW_TYPE_MS diisi. */
    public function type($field, $value)
    {
        if (static::$jedaKetik > 0) {
            parent::typeSlowly($field, $value, static::$jedaKetik);
        } else {
            parent::type($field, $value);
        }

        return $this->jedaSetelahAksi();
    }

    public function select($field, $value = null)
    {
        parent::select($field, $value);

        return $this->jedaSetelahAksi();
    }

    public function check($field, $value = null)
    {
        parent::check($field, $value);

        return $this->jedaSetelahAksi();
    }

    public function acceptDialog()
    {
        parent::acceptDialog();

        return $this->jedaSetelahAksi();
    }

    public function dismissDialog()
    {
        parent::dismissDialog();

        return $this->jedaSetelahAksi();
    }
}
