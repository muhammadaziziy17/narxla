# "Narxla" Loyihasi - UI/UX va Frontend Rejasi (InfinityFree muhiti uchun)

## 🛠 Texnologik Stek (NPM siz, sof CDN arxitekturasi)
- **Styling:** Tailwind CSS cdn orqali ulab ishlat
- **Interaktivlik (Modallar, Dropdownlar):** Alpine.js (CDN)
- **Animatsiyalar (Scroll, Hero effektlar):** GSAP va ScrollTrigger (CDN)
- **Ikonkalar:** FontAwesome (CDN) yoki Heroicons (SVG kodi orqali)
- **Shrift:** Google Fonts (Inter yoki Poppins)

---

## 📦 1-Bosqich: Loyiha Asosi va CDN Sozlamalari
- [ ] Asosiy layout faylini yaratish (`resources/views/layouts/app.blade.php`).
- [ ] Layout `head` qismiga barcha CDN havolalarni joylashtirish (Tailwind, Alpine, GSAP, Fonts).
- [ ] Tailwind CDN uchun kustom ranglar va shriftlarni konfiguratsiya qilish (maxsus `<script>` ichida `tailwind.config` yoziladi).

---

## 🎨 2-Bosqich: Sahifalar Dizayni va Animatsiyalari (Faqat UI)

Loyihada 3 ta asosiy sahifa (View) bo'ladi:

### 1. Landing Page (Asosiy Qo'nish Sahifasi)
Bu sahifa mijozni jalb qilish uchun mo'ljallangan, animatsiyalarga boy bo'ladi.
- **Navbar:** Logotip va "Tizimga kirish" tugmasi.
- **Hero Section:** Chap tomonda "Telefoningizning haqiqiy narxini AI yordamida soniyalarda bilib oling!" degan matn. O'ng tomonda havoda muallaq turgan (floating) telefon rasmi yoki 3D mockupi (GSAP yordamida yengil harakatlanadi).
- **Qanday ishlaydi?:** 3 ta qadamdan iborat blok (1. Tanlash -> 2. Rasm yuklash -> 3. Narxni bilish). Skroll qilinganda elementlar fade-up bo'lib chiqadi.
- **Footer:** Mualliflik huquqi va ijtimoiy tarmoqlar.

### 2. Login Page (Avtorizatsiya Sahifasi)
Zamonaviy va minimalistik sahifa, faqat Google orqali kirishga moslashtirilgan.
- **UI:** Ekran o'rtasida chiroyli shisha effektli (Glassmorphism) kartochka.
- **Kontent:** Logotip, "Xush kelibsiz" matni, va bitta katta "Google orqali davom etish" tugmasi (Google ikonasi bilan).
- **Animatsiya:** Sahifa ochilganda kartochka pastdan sekin qalqib chiqadi (fade-in + translate-y).

### 3. Valuation Page (Telefonni Narxlash Sahifasi)
Ilovaning asosiy ishchi sahifasi. Foydalanuvchi ma'lumotlarni kiritadi.
- **UI Arxitekturasi:** 2 ta kolonkali dizayn.
  - **Chap tomon (Forma):**
    - Brendni tanlash (Select).
    - Modelni tanlash (Select - brendga qarab ochiladi, Alpine.js yordamida simulyatsiya qilinadi).
    - Xotira va Batareya foizi (Input yoki Range slider).
    - Telefon holati (Radio buttonlar kartochka ko'rinishida: "Ideal", "Yaxshi", "Tirnalgan").
    - Rasm yuklash maydoni (Drag & Drop UI ko'rinishida).
  - **O'ng tomon (Natija - Mockup):**
    - Forma to'ldirilgandan so'ng (hozircha tugma bosilganda deb simulyatsiya qilamiz), chiroyli animatsiya bilan "Bozor narxi: $450 - $500" va AI xulosasi yozilgan kartochka chiqadi.

---

## ✨ 3-Bosqich: GSAP Animatsiyalarini Yozish
- [ ] Sahifa yuklanish (On-load) animatsiyalarini `app.blade.php` ning footer qismida alohida script orqali yozish.
- [ ] Hero qismidagi matnlar va rasmlar animatsiyasi.
- [ ] Skroll qilinganda (ScrollTrigger) chiqadigan elementlarni sozlash.

---

## 🧪 4-Bosqich: Tayyorgarlik va InfinityFree'ga Deploy Testi
- [ ] Barcha sahifalarning Mobile/Tablet (Responsive) holatlarini tekshirish.
- [ ] Sahifalar o'rtasida faqat URL orqali bog'lanishlarni (a href) sozlash.
- [ ] Fayllarni InfinityFree htdocs papkasiga yuklab, CDN kutubxonalar ishlayotganini tekshirish.