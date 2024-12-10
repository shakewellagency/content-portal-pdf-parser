@extends('mails.PDFParserMail.layouts.base')

@section('title', 'Import Failed')

@section('content')
    <h1 style="font-size: 22px; color: black;">Your Import Has Failed</h1>
    <br>
    <p>
        Import has failed due to: {{$errorMessage}}
    </p>

    <p><b>Details</b></p>
    <ul style="text-align: left; color: black;">
        <li>Publication No : {{$publicationNo}}</li>
        @foreach ($versionInfo as $key => $value)
        <li>{{ Str::headline($key) }}: {{ $value }}</li>
        @endforeach
        <li>Import Started Date: {{$startedDate}}</li>
        <li>System Failed Exception: {{$failedException}}</li>
    </ul>
@endsection