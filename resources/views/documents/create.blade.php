@extends('layouts.app')

@section('content')
    @include('documents._form', ['mode' => 'create', 'doc' => null, 'docType' => $documentType])
@endsection
