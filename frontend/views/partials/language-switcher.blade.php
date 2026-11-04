<details class="language-picker" data-language-picker>
    <summary class="language-trigger">
        <svg class="icon" aria-hidden="true"><use href="#icon-globe"/></svg>
        <span>{{ __('Idioma') }}</span>
        <svg class="icon language-chevron" aria-hidden="true"><use href="#icon-chevron-down"/></svg>
    </summary>
    <div class="language-popover">
        <form method="post" action="{{ route('locale.update') }}" aria-label="{{ __('Seleccionar idioma') }}">
            @csrf
            @foreach(config('locales.available') as $code => $name)
                <button type="submit" name="locale" value="{{ $code }}" aria-label="{{ $name }}" @if(app()->getLocale() === $code) aria-current="true" @endif>
                    <span>{{ $name }}</span>
                    @if(app()->getLocale() === $code)
                        <svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg>
                    @endif
                </button>
            @endforeach
        </form>
    </div>
</details>
