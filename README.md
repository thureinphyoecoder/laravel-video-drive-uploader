# 🎥 Laravel Video Drive Uploader & Converter

ဒီ Project ကတော့ Video ဖိုင်တွေကို Upload တင်ပြီး MP3 အဖြစ် Convert လုပ်ကာ Google Drive ပေါ်ကို Folder အလိုက် အလိုအလျောက် သိမ်းဆည်းပေးမယ့် System တစ်ခုဖြစ်ပါတယ်။

## 🛠 Features

- **Video to MP3 Conversion:** FFmpeg ကို အသုံးပြုပြီး အရည်အသွေးမြင့် MP3 ပြောင်းလဲပေးခြင်း။
- **Google Drive Integration:** Google OAuth 2.0 ကို သုံးပြီး Drive ပေါ်သို့ တိုက်ရိုက် Upload တင်ခြင်း။
- **Automatic Folder Management:** Drive ထဲမှာ Folder မရှိသေးပါက အလိုအလျောက် ဆောက်ပေးခြင်း။
- **Queue System:** ဗီဒီယိုပြောင်းလဲခြင်းနှင့် Drive တင်ခြင်းကို Background မှာ Worker နှင့် လုပ်ဆောင်ခြင်း။
- **Dockerized:** Docker သုံးထားတဲ့အတွက် ဘယ် Environment မှာမဆို လွယ်လွယ်ကူကူ Setup လုပ်နိုင်ခြင်း။

## 📂 Project Structure

Project ကို ပိုမိုသန့်ရှင်းစေရန်အတွက် Laravel Code များကို `src` folder ထဲတွင် စုစည်းထားပါသည်။

````text
.
├── docker/               # Docker configuration (PHP, Nginx, Supervisor)
├── src/                  # Laravel Project Files (Main Source Code)
│   ├── app/Jobs/         # Background Processing Logic
│   ├── app/Services/     # Google Drive API Logic
│   └── ...
├── docker-compose.yml    # Orchestration for App, MySQL, Nginx, Worker

🚀 Getting Started
၁။ Prerequisites
Docker & Docker Compose တပ်ဆင်ထားရပါမည်။

Google Cloud Console မှ API Credentials (JSON) ရယူထားရပါမည်။

၂။ Environment Setup
src/.env ထဲတွင် အောက်ပါတို့ကို ဖြည့်သွင်းပါ-

```env


QUEUE_CONNECTION=database
GOOGLE_DRIVE_CLIENT_ID=your_id
GOOGLE_DRIVE_CLIENT_SECRET=your_secret
GOOGLE_DRIVE_CALLBACK_URL=your_callback

...

၃။ Running the Application
Terminal တွင် အောက်ပါ command ကို ရိုက်ပါ။


```bash
docker compose up -d --build


အလုပ်လုပ်နေသော Container များ-

laravel-video-app: Main Laravel Application (PHP-FPM)

laravel-video-worker: Background Queue Processor

laravel-video-nginx: Web Server (Port 8000)

mysql: Database Server

📝 Usage
localhost:8000 သို့ သွားပါ။

Google အကောင့်ဖြင့် Login ဝင်ပါ။

ဗီဒီယိုဖိုင်ကို ရွေးချယ်ပြီး Upload Video ကို နှိပ်ပါ။

"Syncing with Google Drive" အဆင့်ပြီးပါက Drive ထဲသို့ MP3 ရောက်ရှိသွားပါလိမ့်မည်။
````
