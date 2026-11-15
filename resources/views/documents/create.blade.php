@extends('layouts.app')

@section('content')
    @include('documents._form', [
        'mode' => 'create',
        'doc' => $doc ?? null,
        'docType' => $documentType,
        'sourcePo' => $sourcePo ?? null,
    ])
@endsection
