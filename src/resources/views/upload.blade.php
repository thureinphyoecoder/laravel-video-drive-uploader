<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>



</head>

<body
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                        Log in
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>
    <div
        class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
        <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
            <div class="mt-10 w-full max-w-xl bg-white dark:bg-[#161615] p-6 rounded-lg shadow">
                <h2 class="text-lg font-medium mb-4">Upload Video</h2>

                <form id="uploadForm" class="space-y-4">
                    @csrf

                    <input type="file" name="video"
                        class="block w-full text-sm text-gray-700
                   file:mr-4 file:py-2 file:px-4
                   file:rounded file:border-0
                   file:text-sm file:font-semibold
                   file:bg-indigo-50 file:text-indigo-700
                   hover:file:bg-indigo-100"
                        required>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Upload
                    </button>
                </form>

                <div class="w-full bg-gray-200 rounded h-2 mt-4 overflow-hidden">
                    <div id="progress" class="h-full bg-indigo-600 transition-all" style="width:0%"></div>
                </div>

                <p id="status" class="mt-2 text-sm text-gray-600"></p>
            </div>

        </main>
    </div>

    @if (Route::has('login'))
        <div class="h-14.5 hidden lg:block"></div>
    @endif

    <script>
        const form = document.getElementById('uploadForm');
        const progress = document.getElementById('progress');
        const status = document.getElementById('status');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const xhr = new XMLHttpRequest();
            const formData = new FormData(form);

            xhr.open('POST', '/upload');

            //  CSRF header 
            const token = document.querySelector('input[name="_token"]').value;
            xhr.setRequestHeader('X-CSRF-TOKEN', token);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    //  loaded (not load)
                    const percent = (e.loaded / e.total) * 100;
                    progress.style.width = percent + '%';
                    status.innerText = `Uploading ${Math.round(percent)}%`;
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const res = JSON.parse(xhr.responseText);
                    status.innerText = 'Processing....';

                    pollStatus(res.id);
                } else {
                    status.innerText = 'Upload Failed';
                }
            };

            xhr.onerror = function() {
                status.innerText = 'Network error';
            };

            xhr.send(formData);

            function pollStatus(videoId) {
                const interval = setInterval(() => {
                    fetch(`/videos/${videoId}/status`)
                        .then(res => res.json())
                        .then(data => {
                            status.innerText = `Status: ${data.status}`;

                            if (data.status === 'done') {
                                clearInterval(interval);
                            }
                        });
                }, 2000);
            }
        });
    </script>

</body>

</html>
