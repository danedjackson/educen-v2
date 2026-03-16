<!-- resources/views/errors/unconfirmed.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Awaiting Confirmation</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="text-center p-8 bg-white shadow-lg rounded-lg">
        <h1 class="text-2xl font-bold text-red-600">Awaiting Confirmation</h1>
        <p class="mt-4 text-gray-600">Please contact your administrator to finalize your access.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm text-blue-500 underline">Log Out</button>
        </form>
    </div>
</body>
</html>
