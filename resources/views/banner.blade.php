{{--
  Bandeau de consentement. Caché au départ : cookie-consent.js l'affiche tant qu'aucun choix
  valable n'est gardé dans le navigateur. Région non modale, placée tôt dans la page : au
  clavier, on y arrive tout de suite. Les noms de classes « cc-… » sont l'API de style.
--}}
<section id="cookie-consent" class="cc" data-cookie-consent aria-labelledby="cookie-consent-title" tabindex="-1" hidden>
  <h2 id="cookie-consent-title" class="cc-title">{{ $texts['title'] }}</h2>
  <p class="cc-text">
    {{ $texts['message'] }}
    @if ($policyUrl)
      <a href="{{ $policyUrl }}" class="cc-link {{ $linkClass }}">{{ $texts['policy_label'] }}</a>
    @endif
  </p>

  <div class="cc-actions">
    <button type="button" class="cc-btn cc-btn--choice {{ $buttonClass }}" data-cc-accept>{{ $texts['accept_all_label'] }}</button>
    <button type="button" class="cc-btn cc-btn--choice {{ $buttonClass }}" data-cc-decline>{{ $texts['decline_all_label'] }}</button>
    <button type="button" class="cc-btn cc-btn--customize {{ $secondaryClass }}" data-cc-customize aria-expanded="false" aria-controls="cookie-consent-prefs">{{ $texts['customize_label'] }}</button>
  </div>

  <div id="cookie-consent-prefs" class="cc-prefs" data-cc-prefs hidden>
    @foreach ($categories as $category)
      @php($cid = 'cookie-consent-'.$category['handle'])
      <div class="cc-cat">
        <div class="cc-cat-head">
          @if ($category['required'])
            <span class="cc-cat-name" id="{{ $cid }}-name">{{ $category['name'] }}</span>
            <span class="cc-always">{{ $texts['always_active_label'] }}</span>
          @else
            <label class="cc-cat-name" for="{{ $cid }}">{{ $category['name'] }}</label>
            <input type="checkbox" role="switch" class="cc-switch" id="{{ $cid }}" data-cc-category="{{ $category['handle'] }}" aria-describedby="{{ $cid }}-desc">
          @endif
        </div>
        <p class="cc-cat-desc" id="{{ $cid }}-desc">{{ $category['description'] }}</p>

        @php($count = array_sum(array_map(fn ($service) => count($service->cookies), $category['services'])))
        <details class="cc-details">
          <summary>{{ $texts['show_cookies_label'] }} ({{ $count }})</summary>
          @foreach ($category['services'] as $service)
            <div class="cc-service">
              <p class="cc-service-name">
                {{ $service->name }}@if ($service->provider) <span class="cc-provider">· {{ $service->provider }}</span>@endif
              </p>
              @if ($service->cookies)
                <ul class="cc-cookies">
                  @foreach ($service->cookies as $cookie)
                    <li>
                      <code>{{ $cookie->name }}</code>
                      <span class="cc-meta">· {{ $cookie->duration }}@if ($cookie->host) · {{ $cookie->host }}@endif</span>
                      @if ($cookie->purpose)<span class="cc-purpose">{{ $cookie->purpose }}</span>@endif
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          @endforeach
        </details>
      </div>
    @endforeach

    <button type="button" class="cc-btn cc-save {{ $buttonClass }}" data-cc-save>{{ $texts['save_label'] }}</button>
  </div>
</section>
<script type="application/json" id="cookie-consent-config">{!! $config !!}</script>
@if ($styles)
<link rel="stylesheet" href="{{ $css }}">
@endif
<script src="{{ $js }}" defer></script>
