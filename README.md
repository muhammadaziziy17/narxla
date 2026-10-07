

# Narxla — AI Qurilmalar Bozor Narxini Baholash Tizimi


https://github.com/user-attachments/assets/b132b2b3-3b5a-45dd-a442-6cbdf9d58902

<p align="center">
  <strong>Telefon, planshet va noutbuklarning O'zbekiston ikkilamchi bozoridagi haqiqiy narxini soniyalarda AI yordamida aniqlang.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/Tailwind_CSS-v4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white" alt="Tailwind CSS v4">
  <img src="https://img.shields.io/badge/Tests-Pest_79_Passed-22C55E?style=flat-square&logo=pest&logoColor=white" alt="Tests">
</p>

---

## ⚡ Asosiy Imkoniyatlar (Features)

- **🔍 Jonli Bozor Tahlili:** OLX.uz va BirBir.uz platformalaridagi e'lonlarni parallel ravishda yig'ish va tahlil qilish.
- **🤖 Sun'iy Intellekt Tahlilchisi (Dual AI Engine):**
  - Asosiy: **Cloudflare Workers AI** (Llama 3.3 / Llama 4 Scout).
  - Zaxira (Automatic Fallback): **Google Gemini 3.8 Flash** API — asosiy AI uzilib qolganda avtomatik ulanadi.
- **💻 Keng Qurilmalar Qamrovi:** Smartfonlar (iPhone, Samsung, Xiaomi, Honor va h.k.), planshetlar va noutbuklar (Intel Core i3/i5/i7/i9, AMD Ryzen, Apple M-series).
- **🧠 Aqlli Qidiruv Normalizatori:** Erkin va xalqona so'rovlarni (masalan: *"core i5 13chi pakalenya"*, *"i7 12-avlod noutbuk"*) tushunib, bozor e'lonlariga to'g'ri bog'lash.
- **💡 Bozor Maslahati va Sertifikat:** Qurilmani sotish yoki kutish bo'yicha AI tavsiyasi, rasmiy QR-kodli baholash sertifikati yuklab olish.
- **🔐 Xavfsiz Kirish (OAuth & OIDC):**
  - **Google OAuth 2.0**
  - **Telegram Login Widget** (OpenID Connect / PKCE)
- **🎨 Premium Minimalist Dizayn:** Monochrome palette (`#141412` ink, `#F7F6F3` paper), Tailwind CSS v4, qora-oq pilyula paginatsiya va silliq mikro-animatsiyalar.
- **🛡️ Xavfsizlik va Rate Limiting:** Suiiste'mol va botlardan himoyalovchi maxsus `RateLimiter`, xavfsizlik sarlavhalari va Apache/InfinityFree `.htaccess` himoyasi.

---

## 🛠 Texnologiyalar (Tech Stack)

- **Backend:** Laravel 11, PHP 8.4
- **Frontend:** Blade, Tailwind CSS v4, Alpine.js, Vite
- **Test:** Pest v3 (79 feature & unit testlar)
- **Ma'lumotlar bazasi:** SQLite / MySQL
- **Integratsiyalar:** Cloudflare Workers AI, Google Gemini API, Tavily Search, Jina Reader, Google Socialite, Telegram OIDC

---

## 🚀 O'rnatish va Ishga Tushirish (Getting Started)

### Talablar:
- PHP >= 8.2 (tavsiya etiladi: PHP 8.4)
- Composer
- Node.js & NPM

### 1. Repozitoriyni klonlash:
```bash
git clone https://github.com/USERNAME/narxla.git
cd narxla
```

### 2. Bog'liqliklarni o'rnatish:
```bash
composer install
npm install
```

### 3. Konfiguratsiya faylini tayyorlash:
```bash
cp .env.example .env
php artisan key:generate
```

`.env` faylida quyidagi kerakli API kalitlarini kiriting:
- `GEMINI_API_KEY=` (Google AI Studio)
- `CLOUDFLARE_API_TOKEN=` & `CLOUDFLARE_ACCOUNT_ID=` (Cloudflare Workers AI)
- `TAVILY_API_KEY=` (Tavily Search)
- `GOOGLE_CLIENT_ID=` & `GOOGLE_CLIENT_SECRET=` (Google OAuth)
- `TELEGRAM_CLIENT_ID=` & `TELEGRAM_CLIENT_SECRET=` (Telegram OIDC)

### 4. Ma'lumotlar bazasini yaratish:
```bash
touch database/database.sqlite
php artisan migrate
```

### 5. Frontend resurslarini yig'ish:
```bash
npm run build
```

### 6. Sinovlarni ishga tushirish:
```bash
php artisan test --compact
```

### 7. Serverni ishga tushirish:
```bash
php artisan serve
```

Saytni brauzerda oching: `http://127.0.0.1:8000`

---

## 🌐 Deploy (InfinityFree / Apache)

Loyiha InfinityFree va boshqa umumiy Apache hostinglarida xatosiz ishlashga to'liq moslashtirilgan:
- Ildiz katalogdagi `.htaccess` orqali `.env`, `.git` va `storage/logs` fayllari tashqaridan `403 Forbidden` bilan bloklanadi.
- Barcha so'rovlar avtomatik ravishda `/public` papkasiga yo'naltiriladi.
- `CACHE_STORE=file` va `SESSION_DRIVER=file` orqali Redis'siz 100% tezlik ta'minlanadi.

---

## 📄 Litsenziya (License)

Loyiha ochiq manbali va [MIT litsenziyasi](LICENSE) asosida tarqatiladi.
