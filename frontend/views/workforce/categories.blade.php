@extends('layout')
@section('title', __('Categorías y permisos'))
@section('page-css', 'workforce-categories')
@section('content')
<section class="panel">
<p class="eyebrow">{{ __('Equipo') }}</p>
<h1>{{ __('Categorías y permisos') }}</h1>
<p class="intro">{{ __('Definí qué puede consultar o editar cada categoría.') }}</p>
@include('workforce._nav')
@if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="category-layout">
    <div class="category-list">
        <h2>{{ __('Categorías de la empresa') }}</h2>
        <p class="muted">{{ __('Abrí una categoría para editar sus permisos.') }}</p>
        <div class="workforce-list">
            @forelse($categories as $category)
                <details class="inline-disclosure workforce-item">
                    <summary>{{ $category->name }} <span class="tag {{ $category->is_active ? '' : 'tag-muted' }}">{{ $category->is_active ? __('Activa') : __('Desactivada') }}</span></summary>
                    <form method="post" action="{{ route('workforce.categories.update', $category) }}" class="form-stack compact-form">
                        @csrf @method('PUT')
                        <label>{{ __('Nombre') }} <input name="name" value="{{ $category->name }}" required maxlength="100"></label>
                        <div class="permission-list">
                            @foreach(['contacts.read' => __('Ver contactos'), 'contacts.write' => __('Editar contactos'), 'opportunities.read' => __('Ver oportunidades'), 'opportunities.write' => __('Editar oportunidades')] as $permission => $label)
                                <label><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $category->permissions ?? [], true))> {{ __($label) }}</label>
                            @endforeach
                        </div>
                        <label>{{ __('Estado') }} <select name="is_active"><option value="1" @selected($category->is_active)>{{ __('Activa') }}</option><option value="0" @selected(!$category->is_active)>{{ __('Desactivada') }}</option></select></label>
                        <button type="submit" class="button button-primary">{{ __('Guardar cambios') }}</button>
                    </form>
                    <form method="post" action="{{ route('workforce.categories.delete', $category) }}" class="inline-form" data-confirm="{{ __('¿Eliminar esta categoría? Solo se puede eliminar si no tiene empleados asignados.') }}">
                        @csrf @method('DELETE')<button type="submit" class="button button-danger button-sm">{{ __('Eliminar categoría') }}</button>
                    </form>
                </details>
            @empty
                <p class="empty-state">{{ __('Todavía no hay categorías. Creá la primera a la derecha.') }}</p>
            @endforelse
        </div>
    </div>
    <aside class="category-editor" aria-labelledby="category-create-title">
        <h2 id="category-create-title">{{ __('Nueva categoría') }}</h2>
        <p class="muted">{{ __('Asigná un nombre y elegí sus permisos de CRM.') }}</p>
            <form method="post" action="{{ route('workforce.categories') }}" class="form-stack compact-form">
                @csrf
                <label for="category-name">{{ __('Nueva categoría') }}</label><input id="category-name" name="name" required maxlength="100" placeholder="{{ __('Ej. Repartidor') }}">
                <div class="permission-list">
                    @foreach(['contacts.read' => __('Ver contactos'), 'contacts.write' => __('Editar contactos'), 'opportunities.read' => __('Ver oportunidades'), 'opportunities.write' => __('Editar oportunidades')] as $permission => $label)
                        <label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ __($label) }}</label>
                    @endforeach
                </div>
                <button type="submit" class="button button-primary">{{ __('Crear categoría') }}</button>
            </form>
    </aside>
</div>
</section>
@endsection
