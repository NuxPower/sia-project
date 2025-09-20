@extends('layouts.app')

@section('content')
<div id="app">
    <!-- Vue root component will be rendered here automatically -->
</div>

<!-- Fallback content in case Vue doesn't load -->
<noscript>
    <div style="padding: 20px; text-align: center;">
        <h1>Weather Dashboard</h1>
        <p>Please enable JavaScript to view the weather dashboard.</p>
    </div>
</noscript>

<script>
// Debug script
console.log('Dashboard page loaded');
console.log('Vue app element:', document.getElementById('app'));
</script>
@endsection
