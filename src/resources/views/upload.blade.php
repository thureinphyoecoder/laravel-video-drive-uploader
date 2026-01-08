<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video to MP3 Converter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#4F46E5',
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 border border-gray-100 relative">

        <div class="flex justify-between items-center mb-8 pb-4 border-b border-gray-50">
            <div class="flex flex-col">
                <span class="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Logged in as</span>
                <span
                    class="text-xs text-indigo-600 font-medium truncate max-w-[150px]">{{ auth()->user()->email }}</span>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="text-[11px] font-bold text-rose-500 hover:bg-rose-50 py-1.5 px-3 rounded-lg transition-all border border-rose-100">
                    Logout
                </button>
            </form>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Video Uploader</h1>
            <p class="text-sm text-gray-400 mt-1">Convert your moments to MP3</p>
        </div>

        <form id="uploadForm" class="space-y-6" enctype="multipart/form-data">
            @csrf
            <div class="group">
                <label class="block text-[11px] font-bold text-gray-400 mb-2 ml-1 uppercase">Select Video File</label>
                <input type="file" name="video" accept="video/mp4,video/quicktime,video/x-matroska" required
                    class="block w-full text-sm text-gray-500
                           file:mr-4 file:py-2.5 file:px-4
                           file:rounded-xl file:border-0
                           file:text-xs file:font-bold
                           file:bg-indigo-600 file:text-white
                           hover:file:bg-indigo-700
                           border border-gray-200 rounded-2xl p-2 bg-gray-50 group-hover:border-indigo-200 transition-all" />
            </div>

            <button id="submitBtn" type="submit"
                class="w-full py-4 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all transform active:scale-95 shadow-xl shadow-indigo-100 flex items-center justify-center gap-2">
                <span>🚀</span> Upload Video
            </button>
        </form>

        <div id="progressContainer" class="mt-8 hidden bg-gray-50 p-4 rounded-2xl border border-gray-100">
            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                <div id="progress" class="h-full bg-indigo-600 transition-all duration-500" style="width:0%"></div>
            </div>
            <div id="status" class="mt-3 text-[11px] font-bold text-gray-500 text-center uppercase tracking-wide">
            </div>
        </div>

        <div id="convertSection" class="mt-6 hidden">
            <button id="convertBtn"
                class="w-full py-4 rounded-2xl bg-orange-500 text-white font-bold hover:bg-orange-600 transition-all transform active:scale-95 shadow-xl shadow-orange-100 flex items-center justify-center gap-2">
                <span>🎵</span> Convert to MP3
            </button>
        </div>

        <div id="downloadBox" class="mt-6 hidden">
            <a id="downloadLink"
                class="flex items-center justify-center w-full py-4 rounded-2xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-100"
                href="#" target="_blank" download>
                <span class="mr-2 text-xl">⬇️</span> Download MP3
            </a>
            <p class="text-[10px] text-gray-400 text-center mt-4 italic">
                *File securely synced with Google Drive
            </p>
        </div>
    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const progress = document.getElementById('progress');
        const statusBox = document.getElementById('status');
        const downloadBox = document.getElementById('downloadBox');
        const downloadLink = document.getElementById('downloadLink');
        const submitBtn = document.getElementById('submitBtn');
        const convertSection = document.getElementById('convertSection');
        const convertBtn = document.getElementById('convertBtn');

        let currentVideoId = null;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = form.querySelector('input[name="video"]');
            const file = fileInput.files[0];

            // --- UI Validation Start ---
            const allowedTypes = [
                'video/mp4', 'video/quicktime', 'video/x-matroska',
                'video/x-msvideo', 'video/x-ms-wmv'
            ];
            const maxSize = 512000 * 1024; // 500MB

            if (!file || !allowedTypes.includes(file.type) || file.size > maxSize) {
                // Error ပြဖို့အတွက် Container ကို အရင်ဖော်လိုက်မယ်
                progressContainer.classList.remove('hidden');
                progress.style.width = '0%';

                if (!file) {
                    showStatus('Please select a video file.', 'text-rose-600');
                } else if (!allowedTypes.includes(file.type)) {
                    showStatus('Invalid format. Please upload MP4, MOV, MKV, AVI or WMV only.', 'text-rose-600');
                    fileInput.value = '';
                } else if (file.size > maxSize) {
                    showStatus('File is too large. Maximum size allowed is 500MB.', 'text-rose-600');
                    fileInput.value = '';
                }
                return;
            }
            // --- UI Validation End ---

            progressContainer.classList.remove('hidden');
            progress.style.width = '0%';
            progress.classList.remove('bg-orange-500');
            progress.classList.add('bg-indigo-600');

            showStatus('Starting upload...', 'text-gray-500');

            downloadBox.classList.add('hidden');
            convertSection.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

            const xhr = new XMLHttpRequest();
            const formData = new FormData(form);

            xhr.open('POST', '/upload');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progress.style.width = percent + '%';
                    showStatus(`Uploading to Server: ${percent}%`, 'text-indigo-600');
                }
            };

            xhr.onload = function() {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (xhr.status === 200) {
                        currentVideoId = res.id;
                        showStatus('Server received. Moving to Google Drive...', 'text-indigo-600');
                        pollStatus(res.id);
                    } else {
                        throw new Error(res.message || 'Upload failed');
                    }
                } catch (err) {
                    showStatus('Error: ' + err.message, 'text-rose-600');
                    resetSubmitBtn();
                }
            };

            xhr.send(formData);
        });

        function pollStatus(videoId) {
            const interval = setInterval(() => {
                fetch(`/videos/${videoId}/status`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'uploading_to_drive') {
                            showStatus('Syncing with Google Drive...', 'text-indigo-600');
                            progress.style.width = '95%';
                        } else if (data.status === 'completed') {
                            clearInterval(interval);
                            showStatus('Video safely uploaded to Drive.', 'text-emerald-600');
                            progress.style.width = '100%';
                            convertSection.classList.remove('hidden');
                            resetSubmitBtn();
                        } else if (data.status === 'converting') {
                            showStatus('Converting Video to MP3. Please wait...', 'text-orange-600');
                            progressContainer.classList.remove('hidden');
                            progress.classList.replace('bg-indigo-600', 'bg-orange-500');
                            progress.style.width = '60%';
                        } else if (data.status === 'done') {
                            clearInterval(interval);
                            showStatus('All done! Your MP3 is ready.', 'text-emerald-600');
                            progress.style.width = '100%';

                            downloadLink.href =
                                `https://drive.google.com/uc?id=${data.download_url}&export=download`;
                            downloadBox.classList.remove('hidden');
                            convertSection.classList.add('hidden');
                        } else if (data.status === 'failed') {
                            clearInterval(interval);
                            showStatus('Task failed. Please check server logs.', 'text-rose-600');
                            resetSubmitBtn();
                        }
                    })
                    .catch(() => {
                        clearInterval(interval);
                        showStatus('Connection lost. Please refresh.', 'text-rose-600');
                    });
            }, 3000);
        }

        convertBtn.addEventListener('click', function() {
            if (!currentVideoId) return;

            showStatus('Starting conversion job...', 'text-orange-600');
            progress.style.width = '20%';
            progressContainer.classList.remove('hidden');

            convertBtn.disabled = true;
            convertBtn.classList.add('opacity-50', 'cursor-not-allowed');

            fetch(`/videos/${currentVideoId}/convert`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Server Error');
                    return data;
                })
                .then(data => {
                    pollStatus(currentVideoId);
                })
                .catch(err => {
                    showStatus('Conversion Error: ' + err.message, 'text-rose-600');
                    convertBtn.disabled = false;
                    convertBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
        });

        // စာသားအရောင်ကိုပါ တစ်ခါတည်း ပြောင်းပေးမယ့် Helper Function
        function showStatus(message, colorClass) {
            statusBox.innerText = message;
            const isError = colorClass.includes('rose');
            statusBox.className = `mt-3 text-[11px] font-bold text-center uppercase tracking-wide ${colorClass}`;
        }

        function resetSubmitBtn() {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    </script>
</body>

</html>
