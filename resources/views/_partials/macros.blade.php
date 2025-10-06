@php
  $width = $width ?? '38';
  $height = $height ?? '38';
@endphp

<img src="{{ asset('storage/mubslogo.png') }}" alt="MUBS Logo" width="{{ $width }}" height="{{ $height }}" class="app-brand-img"
  onerror="this.onerror=null; this.src='{{ asset('favicon.ico') }}'" />
