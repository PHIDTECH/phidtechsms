@extends('layouts.app')

@section('title', 'Contact Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contact Details</h5>
                    <a href="{{ route('contacts.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Contacts
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Basic Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $contact->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $contact->phone }}</td>
                                </tr>
                                @if($contact->email)
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $contact->email }}</td>
                                </tr>
                                @endif
                                @if($contact->date_of_birth)
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td>{{ $contact->date_of_birth->format('M d, Y') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Group Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Contact Group:</strong></td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $contact->contactGroup->color ?? '#6c757d' }}">
                                            {{ $contact->contactGroup->name ?? 'No Group' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge {{ $contact->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $contact->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $contact->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated:</strong></td>
                                    <td>{{ $contact->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection