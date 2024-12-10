@extends('mails.PDFParserMail.layouts.base')

@section('title', 'Import Processing')

@section('content')
    <h1 style="font-size: 22px; color: black;">An Import Process Is About To Start</h1>
    <br>
    <p>
        The Import webhooks has been triggered and a package has been added to the queue to be processed.
    </p>

    <p><b>Details</b></p>
    <ul style="text-align: left; color: black;">
        {{-- <li>Package Name: {{$packageName}}</li>
        <li>Trigger Date: {{$triggerDate}}</li> --}}
    </ul>
@endsection 