@extends('admin.layouts.app')
@section('panel')
    <div class="email-container"
        style="max-width:900px; margin:2rem auto; font-family:Arial, sans-serif; border:1px solid #ddd; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">

        <!-- Header -->
        <div class="email-header"
            style="padding:1.5rem; border-bottom:1px solid #eee; background-color:#f9f9f9; display:flex; flex-direction:column;">
            <h2 style="margin:0 0 0.5rem 0; font-size:1.5rem; color:#333;">Subject: {{ $email->subject ?? '(No Subject)' }}
            </h2>
            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#666;">
                <span><strong>From:</strong> {{ $email->from }}</span>
                <span><strong>Date:</strong> {{ $email->created_at->format('d/m/Y h:i:s A') }}</span>
            </div>
        </div>

        <!-- Email Body -->
        <div class="email-body" style="padding:1.5rem; line-height:1.6; color:#333; background-color:#fff;">
            {!! nl2br(e($email->body)) !!}
        </div>
    </div>
    @if($email->is_checked)
    <div class="email-container" style="max-width:900px; margin:2rem auto; font-family:Arial, sans-serif; border:1px solid #ddd; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <div class="email-header"
            style="padding:1.5rem; border-bottom:1px solid #eee; background-color:#f9f9f9; display:flex; flex-direction:column;">
            <h2 style="margin:0 0 0.5rem 0; font-size:1.5rem; color:#333;">Note</h2>
        </div>

        <!-- Email Body -->
        <div class="email-body" style="padding:1.5rem; line-height:1.6; color:#333; background-color:#fff;">
            {!! nl2br(e($email->note)) !!}
        </div>
    </div>
    <div class="email-container" style="max-width:900px; margin:2rem auto; font-family:Arial, sans-serif; border:1px solid #ddd; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <div class="email-header"
            style="padding:1.5rem; border-bottom:1px solid #eee; background-color:#f9f9f9; display:flex; flex-direction:column;">
            <h2 style="margin:0 0 0.5rem 0; font-size:1.5rem; color:#333;">Updated By</h2>
        </div>

        <!-- Email Body -->
        <div class="email-body" style="padding:1.5rem; line-height:1.6; color:#333; background-color:#fff;">
            {!! nl2br(e($email->checked_by_admin->name)) !!}
        </div>
    </div>
    @endif

@endsection