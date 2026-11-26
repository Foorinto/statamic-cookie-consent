<?php

namespace Foorintodev\CookieConsent\Tests;

use Foorintodev\CookieConsent\Support\Duration;
use PHPUnit\Framework\Attributes\DataProvider;

class DurationTest extends TestCase
{
    public static function durees(): array
    {
        return [
            'minutes' => ['minutes', 45, 'en', '45 minutes'],
            'heures' => ['minutes', 120, 'en', '2 hours'],
            'une heure' => ['minutes', 60, 'en', '1 hour'],
            'jours en minutes' => ['minutes', 2880, 'en', '2 days'],
            'session' => ['minutes', 0, 'en', 'Until the browser is closed'],
            'un mois' => ['days', 30, 'en', '1 month'],
            'trois mois' => ['days', 90, 'en', '3 months'],
            'six mois' => ['days', 182, 'en', '6 months'],
            'un an' => ['days', 365, 'en', '1 year'],
            'deux ans' => ['days', 730, 'en', '2 years'],
            'jours' => ['days', 10, 'en', '10 days'],
            'mois en français' => ['days', 90, 'fr', '3 mois'],
            'an en français' => ['days', 365, 'fr', '1 an'],
            'ans en français' => ['days', 730, 'fr', '2 ans'],
            'heures en allemand' => ['minutes', 120, 'de', '2 Stunden'],
            'mois en allemand' => ['days', 182, 'de', '6 Monate'],
        ];
    }

    #[DataProvider('durees')]
    public function test_duree_lisible(string $method, int $value, string $lang, string $expected): void
    {
        $this->assertSame($expected, Duration::$method($value, $lang));
    }
}
