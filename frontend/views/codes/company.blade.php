@extends('layout')
@section('title', 'Enlaces privados · Multitena')
@section('content')
<section class="panel">
    <p class="eyebrow">Mi empresa</p><h1>Enlaces privados</h1>
    <p class="intro">Consultá los enlaces de tus usuarios. Solo el superadministrador puede emitirlos, renovarlos o suspenderlos. Un enlace pendiente se puede recibir una sola vez para entregarlo a su titular.</p>
    <form method="get" action="{{ route('codes.company') }}" class="filter-bar"><input name="q" value="{{ request('q') }}" placeholder="Buscar usuario"><select name="status"><option value="">Todos los estados</option>@foreach(['active'=>'Activo','expired'=>'Vencido','suspended'=>'Suspendido','revoked'=>'Revocado'] as $key=>$name)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $name }}</option>@endforeach</select><button class="button button-secondary">Filtrar</button></form>
    @include('codes.partials.links', ['manage' => false, 'canClaim' => auth()->user()->role === 'company_admin'])
</section>
@endsection
