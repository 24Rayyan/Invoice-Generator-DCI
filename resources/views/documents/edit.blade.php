@extends('layouts.app')

@section('content')
    @include('documents._form', ['mode' => 'edit', 'doc' => $document, 'docType' => $document->type])
@endsection
