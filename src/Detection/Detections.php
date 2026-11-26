<?php

namespace Foorintodev\CookieConsent\Detection;

use Illuminate\Support\Carbon;

/**
 * Les noms non déclarés signalés par les navigateurs, rangés dans un fichier JSON
 * (storage/app/statamic-cookie-consent/detections.json) : ni contenu, ni base de données.
 * Par nom : type de stockage, nombre de signalements, premier et dernier jour, quelques
 * pages. Plafonné (MAX_NAMES) contre le remplissage, oublié après EXPIRE_DAYS sans signal.
 */
final class Detections
{
    public const MAX_NAMES = 200;

    public const MAX_PATHS = 5;

    public const EXPIRE_DAYS = 90;

    public static function path(): string
    {
        return storage_path('app/statamic-cookie-consent/detections.json');
    }

    /**
     * @param  array<int, array{name: string, storage: string}>  $items  déjà filtrés
     * @param  string[]  $known  noms déclarés ou ignorés : retirés du fichier au passage
     */
    public static function record(array $items, ?string $page, array $known = []): void
    {
        $file = static::path();

        if (! is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }

        $handle = @fopen($file, 'c+');

        if (! $handle) {
            return;
        }

        try {
            flock($handle, LOCK_EX);

            $data = json_decode((string) stream_get_contents($handle), true);
            $data = static::prune(is_array($data) ? $data : [], $known);
            $today = Carbon::now()->toDateString();

            foreach ($items as $item) {
                $key = $item['storage'].':'.$item['name'];

                if (! isset($data[$key])) {
                    if (count($data) >= static::MAX_NAMES) {
                        continue;
                    }

                    $data[$key] = [
                        'name' => $item['name'],
                        'storage' => $item['storage'],
                        'count' => 0,
                        'first_seen' => $today,
                        'last_seen' => $today,
                        'pages' => [],
                    ];
                }

                $data[$key]['count']++;
                $data[$key]['last_seen'] = $today;

                if ($page !== null && ! in_array($page, $data[$key]['pages'], true) && count($data[$key]['pages']) < static::MAX_PATHS) {
                    $data[$key]['pages'][] = $page;
                }
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Ce qui reste non déclaré aujourd'hui (un nom déclaré ou ignoré depuis disparaît),
     * le plus récent d'abord.
     */
    public static function undeclared(?array $known = null): array
    {
        $file = static::path();
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        $data = static::prune(is_array($data) ? $data : [], $known ?? Names::known());

        uasort($data, fn ($a, $b) => [$b['last_seen'], $b['count']] <=> [$a['last_seen'], $a['count']]);

        return array_values($data);
    }

    private static function prune(array $data, array $known): array
    {
        $limit = Carbon::now()->subDays(static::EXPIRE_DAYS)->toDateString();

        return array_filter($data, fn ($entry) => is_array($entry)
            && isset($entry['name'], $entry['storage'], $entry['last_seen'])
            && $entry['last_seen'] >= $limit
            && ! Names::matches($entry['name'], $known));
    }
}
