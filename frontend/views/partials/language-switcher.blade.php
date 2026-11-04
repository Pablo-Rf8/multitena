@php
    $languages = config('locales.available');
    $defaultName = $languages[config('locales.default')];
    $selectedName = $languages[app()->getLocale()] ?? $defaultName;
@endphp
<details class="language-picker" data-language-picker>
    <summary class="language-trigger" aria-label="{{ __('Lenguaje') }}: {{ $selectedName }}">
        <svg class="icon" aria-hidden="true"><use href="#icon-globe"/></svg>
        <span class="language-pair" aria-hidden="true"><span>{{ __('Lenguaje') }}</span><span>{{ $selectedName }}</span></span>
        <svg class="icon language-chevron" aria-hidden="true"><use href="#icon-chevron-down"/></svg>
    </summary>
    <div class="language-popover">
        <form method="post" action="{{ route('locale.update') }}" aria-label="{{ __('Seleccionar idioma') }}">
            @csrf
            @if($inAccount ?? false)<input type="hidden" name="return_to_account" value="1">@endif
            @foreach($languages as $code => $name)
                <button type="submit" name="locale" value="{{ $code }}" aria-label="{{ $name }} {{ __($name) }}" @if(app()->getLocale() === $code) aria-current="true" @endif>
                    <span class="language-pair" aria-hidden="true"><span>{{ $name }}</span><span>{{ __($name) }}</span></span>
                </button>
            @endforeach
        </form>
    </div>
</details>
