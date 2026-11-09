@extends('layouts.app')

@section('content')
    @include('documents._form', ['mode' => 'duplicate', 'doc' => $sourceDoc, 'docType' => $sourceDoc->type])
@endsection
