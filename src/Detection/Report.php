<?php

namespace Foorintodev\CookieConsent\Detection;

use Illuminate\Support\Carbon;

/**
 * Tableau des noms non déclarés, pour l'onglet « Détection » du global (champ html).
 * Les noms et les pages viennent des navigateurs des visiteurs : tout est échappé.
 */
final class Report
{
    private const STORAGES = ['cookie' => 'Cookie', 'local' => 'Stockage local', 'session' => 'Stockage de session'];

    public static function html(?array $entries = null): string
    {
        if (! config('foorintodev-cookie-consent.detection')) {
            return '<p>Détection coupée (<code>COOKIE_CONSENT_DETECTION=false</code>).</p>';
        }

        $entries ??= Detections::undeclared();

        if ($entries === []) {
            return '<p>Aucun nom non déclaré signalé pour l\'instant. Les navigateurs des visiteurs signalent ici, au fil des visites, les cookies et données stockées qui ne correspondent à aucun service.</p>';
        }

        $cell = 'padding:.4rem .6rem;border-bottom:1px solid rgba(127,127,127,.25);text-align:left;vertical-align:top';
        $rows = '';

        foreach ($entries as $entry) {
            $pages = implode('<br>', array_map(fn ($page) => e($page), (array) ($entry['pages'] ?? [])));

            $rows .= '<tr>'
                .'<td style="'.$cell.'"><code>'.e($entry['name']).'</code></td>'
                .'<td style="'.$cell.'">'.e(self::STORAGES[$entry['storage']] ?? $entry['storage']).'</td>'
                .'<td style="'.$cell.'">'.(int) ($entry['count'] ?? 0).'</td>'
                .'<td style="'.$cell.'">'.e(self::date($entry['first_seen'] ?? null)).'</td>'
                .'<td style="'.$cell.'">'.e(self::date($entry['last_seen'] ?? null)).'</td>'
                .'<td style="'.$cell.'">'.$pages.'</td>'
                .'</tr>';
        }

        $head = '';
        foreach (['Nom', 'Type', 'Signalé', 'Première fois', 'Dernière fois', 'Pages'] as $label) {
            $head .= '<th scope="col" style="'.$cell.';font-weight:600">'.$label.'</th>';
        }

        return '<table style="width:100%;border-collapse:collapse;font-size:.875rem">'
            .'<caption style="text-align:left;padding-bottom:.5rem">'.count($entries).' '.(count($entries) > 1 ? 'noms non déclarés' : 'nom non déclaré').'</caption>'
            .'<thead><tr>'.$head.'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    private static function date(?string $date): string
    {
        try {
            return $date ? Carbon::parse($date)->format('d/m/Y') : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
