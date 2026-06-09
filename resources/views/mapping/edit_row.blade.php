@extends('layouts.app')

@section('title', 'Edit Mapping')

@section('content')

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('mapping.profile', $mapping->profile_id) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali ke Profile</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Edit Mapping</h1>
        <p class="text-sm text-slate-500 mt-1">
            Profile: <strong>{{ $mapping->profile->name }}</strong>
            &middot; Entity: <strong>{{ $mapping->profile->entity->name }}</strong>
            &middot; Mapping Key: <code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded">{{ $mapping->mapping_key }}</code>
        </p>
    </div>

    <form action="{{ route('mapping.row.update', $mapping->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf @method('PUT')

        @include('mapping._form_row', ['mapping' => $mapping, 'profile' => $mapping->profile])

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('mapping.profile', $mapping->profile_id) }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
            <button type="submit" class="inline-flex items-center px-5 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">Update Mapping</button>
        </div>

        @if($errors->any())
            <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </form>
</div>

@endsection