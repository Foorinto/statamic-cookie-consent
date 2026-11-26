{{--
  Liste des cookies (politique de confidentialité). Un titre et un tableau par catégorie ;
  le tableau porte le nom de son titre (aria-labelledby). Sur un écran étroit, il défile
  dans son cadre, qui reçoit le focus au clavier pour pouvoir défiler sans souris.
--}}
<div class="cc-list{{ $class !== '' ? ' '.$class : '' }}">
  @foreach ($categories as $category)
    @php($hid = $id.'-'.$category['handle'])
    <h{{ $level }} id="{{ $hid }}" class="cc-list-title">{{ $category['name'] }}</h{{ $level }}>
    <p class="cc-list-desc">{{ $category['description'] }}</p>

    @if ($category['rows'])
      <div class="cc-table-wrap" role="region" aria-labelledby="{{ $hid }}" tabindex="0">
        <table class="cc-table{{ $tableClass !== '' ? ' '.$tableClass : '' }}" aria-labelledby="{{ $hid }}">
          <thead>
            <tr>
              <th scope="col">{{ $labels['name'] }}</th>
              <th scope="col">{{ $labels['service'] }}</th>
              <th scope="col">{{ $labels['purpose'] }}</th>
              <th scope="col">{{ $labels['duration'] }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($category['rows'] as $row)
              <tr>
                <th scope="row"><code>{{ $row['name'] }}</code><span class="cc-table-note">{{ $row['type'] }}</span></th>
                <td>{{ $row['service'] }}@if ($row['provider'])<span class="cc-table-note">{{ $row['provider'] }}</span>@endif</td>
                <td>{{ $row['purpose'] }}</td>
                <td>{{ $row['duration'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    @if ($category['without_cookies'])
      <p class="cc-list-desc">{{ $category['without_cookies'] }}</p>
    @endif

    @if ($category['policies'])
      <ul class="cc-list-policies">
        @foreach ($category['policies'] as $policy)
          <li><a href="{{ $policy['url'] }}"@if ($linkClass !== '') class="{{ $linkClass }}"@endif>{{ $policy['label'] }}</a></li>
        @endforeach
      </ul>
    @endif
  @endforeach

  @if ($openButton)
    <p class="cc-list-open">{!! $openButton !!}</p>
  @endif
</div>
