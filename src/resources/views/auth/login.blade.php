<x-guest-layout>
    <div class="space-y-6 text-center">

        <h1 class="text-xl font-semibold text-gray-800">
            Sign in
        </h1>

        <p class="text-sm text-gray-600">
            Continue with Google to upload videos to your Drive.
        </p>

        <a href="{{ route('google.redirect') }}"
            class="block w-full px-4 py-3
                  bg-green-600 text-white rounded-lg font-medium
                  hover:bg-green-700 focus:outline-none focus:ring-2
                  focus:ring-green-500 focus:ring-offset-2 transition">
            Continue with Google
        </a>

    </div>
</x-guest-layout>
