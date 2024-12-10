@extends('mails.PDFParserMail.layouts.base')

@section('title', 'Import Processing')

@section('content')
    <h1 style="font-size: 22px; color: black;">Your Import Has Started</h1>
    <br>
    <p>
        Import is currently being processed, this may take anywhere between 10 - 30 minutes
    </p>

    <p><b>Details</b></p>
    <ul style="text-align: left; color: black;">
        <li>Publication No : {{$publicationNo}}</li>
        @foreach ($versionInfo as $key => $value)
        <li>{{ Str::headline($key) }}: {{ $value }}</li>
        @endforeach
        <li>Import Started Date: {{$startedDate}}</li>
    </ul>
@endsection
