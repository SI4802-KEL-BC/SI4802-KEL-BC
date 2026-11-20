<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\Browser\BrowserLambat;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            // Tanpa ini Chrome memunculkan popup "simpan/periksa kata sandi" setelah login,
            // dan ketikan pada test berikutnya masuk ke popup, bukan ke form.
            '--disable-features=PasswordLeakDetection,PasswordCheck,PasswordManagerOnboarding',
            '--password-store=basic',
            '--no-first-run',
            '--no-default-browser-check',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $options->setExperimentalOption('prefs', [
            'credentials_enable_service' => false,
            'profile.password_manager_enabled' => false,
            'profile.password_manager_leak_detection' => false,
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Pakai BrowserLambat supaya jalannya test bisa diperlambat untuk ditonton.
     * Kecepatan diatur DUSK_SLOW_MS dan DUSK_SLOW_TYPE_MS (lihat tests/Browser/BrowserLambat.php).
     */
    protected function newBrowser($driver)
    {
        BrowserLambat::$jeda = max(0, (int) env('DUSK_SLOW_MS', 0));
        BrowserLambat::$jedaKetik = max(0, (int) env('DUSK_SLOW_TYPE_MS', 0));

        return new BrowserLambat($driver);
    }
}
