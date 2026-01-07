<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6">

        <!-- Title -->
        <h1 class="text-xl font-semibold mb-1">Upload Video</h1>


        <!-- Form -->
        <form id="uploadForm" class="space-y-4" enctype="multipart/form-data">
            @csrf

            <input type="file" name="video" accept="video/mp4,video/quicktime,video/x-matroska" required
                class="block w-full text-sm
                       file:mr-4 file:py-2.5 file:px-4
                       file:rounded-lg file:border-0
                       file:bg-indigo-600 file:text-white
                       hover:file:bg-indigo-700
                       border border-gray-300 rounded-lg" />

            <button id="submitBtn" type="submit"
                class="w-full py-3 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
                ⬆️ Upload Video
            </button>
        </form>

        <!-- Progress -->
        <div class="mt-5 h-2 w-full bg-gray-200 rounded-full overflow-hidden">
            <div id="progress" class="h-full bg-indigo-600" style="width:0%"></div>
        </div>

        <!-- Status -->
        <div id="status" class="mt-4 text-sm text-gray-600"></div>

        <!-- Download -->
        <div id="download" class="mt-4 hidden">
            <a id="downloadLink"
                class="block w-full text-center py-3 rounded-lg
                      bg-emerald-600 text-white font-semibold hover:bg-emerald-700"
                href="#">
                ⬇️ Download Video
            </a>
        </div>

    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const progress = document.getElementById('progress');
        const statusBox = document.getElementById('status');
        const downloadBox = document.getElementById('download');
        const downloadLink = document.getElementById('downloadLink');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // reset UI
            progress.style.width = '0%';
            statusBox.innerText = 'Starting upload...';
            downloadBox.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60');

            const xhr = new XMLHttpRequest();
            const formData = new FormData(form);

            xhr.open('POST', '/upload');
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                document.querySelector('input[name="_token"]').value
            );

            // upload progress
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progress.style.width = percent + '%';
                    statusBox.innerText = `Uploading ${percent}%`;
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const res = JSON.parse(xhr.responseText);
                    statusBox.innerText = 'Processing video...';
                    pollStatus(res.id);

                } else if (xhr.status === 422) {
                    const res = JSON.parse(xhr.responseText);
                    statusBox.innerText = Object.values(res.errors)[0][0];
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60');

                } else {
                    statusBox.innerText = 'Upload failed';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60');
                }
            };

            xhr.onerror = function() {
                statusBox.innerText = 'Network error';
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-60');
            };

            xhr.send(formData);
        });

        function pollStatus(videoId) {
            const interval = setInterval(() => {
                fetch(`/videos/${videoId}/status`)
                    .then(res => res.json())
                    .then(data => {
                        statusBox.innerText = `Status: ${data.status}`;

                        if (data.status === 'done' && data.download_url) {
                            clearInterval(interval);

                            downloadLink.href = data.download_url;
                            downloadBox.classList.remove('hidden');

                            // optional auto-download
                            window.location.href = data.download_url;

                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-60');
                        }

                        if (data.status === 'failed') {
                            clearInterval(interval);
                            statusBox.innerText = 'Processing failed';
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-60');
                        }
                    })
                    .catch(() => {
                        clearInterval(interval);
                        statusBox.innerText = 'Network error';
                    });
            }, 2000);
        }
    </script>


</body>

</html>
