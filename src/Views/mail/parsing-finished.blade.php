@extends('mails.PDFParserMail.layouts.base')

@section('title', 'Import Finished')

@section('content')
    <h1 style="font-size: 22px; color: black;">Your Import Has Finished</h1>
    <br>
    <p>
        Import has finished, and is ready for approval.
    </p>

    <p><b>Details</b></p>
    <ul style="text-align: left; color: black;">
        <li>Publication No : {{$publicationNo}}</li>
        @foreach ($versionInfo as $key => $value)
        <li>{{ Str::headline($key) }}: {{ $value }}</li>
        @endforeach
        <li>Import Started Date: {{$startedDate}} </li>
        <li>Import Finished Date: {{$finishedDate}} </li>
        <li>Import Duration: {{$processTime}} </li>
    </ul>


    <div style="display: flex; justify-content: center; margin: 3rem;">
        <a href="{{$previewLink}}" style="display: inline-block; width: 160px; height: 40px; background: #F6F6F6; border: 2px solid #007CAD; border-radius: 30px; text-align: center; line-height: 40px; font-family: 'Roboto', sans-serif; font-size: 13px; color: #000000; text-decoration: none;">
            Preview Import
        </a>
    </div>


    <div>
        <p style="line-height: 2px"><b>Approve or Schedule the version</b></p>
        <p>Once Approved, you can publish now or schedule the version to be published at a later date.</p>
    </div>

    <div style="display: flex; justify-content: center; gap: 17px; margin-top: 3rem;">
        <!-- Publish Now Button -->
        <a href="{{$approvedLink}}" style="display: inline-block; width: 170px; height: 40px; background: #007CAD; border-radius: 20px; text-align: center; line-height: 40px; font-family: 'Roboto', sans-serif; font-size: 16px; color: #FFFFFF; text-decoration: none;">
            Publish Now
        </a>
        <!-- Schedule Button -->
        <a href="{{ $scheduleLink }}" style="display: inline-block; width: 170px; height: 40px; background: #007CAD; border-radius: 20px; text-align: center; line-height: 40px; font-family: 'Roboto', sans-serif; font-size: 16px; color: #FFFFFF; text-decoration: none;">
            Schedule
        </a>
    </div>
@endsection
