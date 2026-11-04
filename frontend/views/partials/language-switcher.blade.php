<form method="post" action="{{ route('locale.update') }}" class="language-switcher" aria-label="{{ __('Idioma') }}">
    @csrf
    <button type="submit" name="locale" value="es" @if(app()->getLocale() === 'es') aria-current="true" @endif>Español</button>
    <button type="submit" name="locale" value="en" @if(app()->getLocale() === 'en') aria-current="true" @endif>English</button>
</form>
