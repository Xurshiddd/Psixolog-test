# Psixologik Diagnostika Platformasi Bo'yicha Hisobot

## 1. Loyiha haqida qisqacha

Mazkur loyiha Toshkent To'qimachilik va yengil sanoat institutining talabalari uchun mo'ljallangan psixologik diagnostika platformasidir. Tizim orqali talabalar onlayn testlarni topshiradi, administrator va psixolog esa natijalarni ko'rib chiqadi, tahlil qiladi va kerak bo'lsa qo'shimcha diagnostika xulosasini shakllantiradi.

Platforma Laravel, Inertia va Vue texnologiyalari asosida qurilgan. Tizimda veb interfeys bilan bir qatorda mobil foydalanish uchun Android APK ham taqdim etilgan.

## 2. Loyihaning asosiy maqsadi

Platformaning asosiy maqsadi:

- talabalar psixologik holatini testlar orqali aniqlash;
- test natijalarini markazlashgan tarzda saqlash;
- administrator va psixolog uchun tahliliy boshqaruv panelini taqdim etish;
- talabalar bilan bevosita murojaat va yozishma imkoniyatini yaratish;
- mobil qurilmalarda ham qulay foydalanishni ta'minlash.

## 3. Tizimning asosiy imkoniyatlari

### 3.1. Bosh sahifa

Bosh sahifada platforma haqida umumiy ma'lumot beriladi. Foydalanuvchi tilni tanlashi, tizimga kirishi, video qo'llanmani ochishi va Android ilovani yuklab olishi mumkin.

Bosh sahifaning afzalliklari:

- zamonaviy va vizual jihatdan boy dizayn;
- foydalanuvchi uchun kirish ma'lumotlari ko'rsatilgan;
- video qo'llanma mavjud;
- mobil foydalanuvchilar uchun `app-release.apk` yuklab olish havolasi berilgan.

### 3.2. Autentifikatsiya va kirish

Tizimda HEMIS orqali autentifikatsiya ishlatiladi. Bu talabalar uchun mavjud institut ma'lumotlari orqali kirish imkonini beradi. Shuningdek, tilni almashtirish va foydalanuvchi rollariga ko'ra sahifalarni ajratish yo'lga qo'yilgan.

### 3.3. Talaba moduli

Talaba uchun quyidagi imkoniyatlar mavjud:

- aktiv modullar ro'yxatini ko'rish;
- testlarni bosqichma-bosqich yechish;
- har bir savol bo'yicha javoblarni belgilash;
- test yakunida natijalarni olish;
- administrator yoki psixologga murojaat yuborish;
- real vaqtga yaqin chat orqali yozishma qilish.

Talaba paneli test jarayonini sodda va tushunarli tashkil qilgan. Progress indikator, savollar bo'yicha navigatsiya va yuborish nazorati mavjud.

### 3.4. Administrator moduli

Administrator panelida quyidagi imkoniyatlar mavjud:

- barcha talabalar ro'yxatini ko'rish;
- qidiruv va filtrlar orqali talabalarni saralash;
- fakultet, mutaxassislik, guruh, kurs va test statusi bo'yicha filtrlash;
- talaba natijalarini ko'rish;
- Excel va PDF formatlarda eksport qilish;
- kategoriyalarni biriktirish;
- faoliyat jurnalini ko'rish.

Bu imkoniyatlar tizimni boshqarish va monitoring qilishni ancha qulaylashtiradi.

### 3.5. Psixolog moduli

Psixolog talabalar natijalarini ko'rishi, diagnostika xulosasini saqlashi va talabalar bilan murojaatlar bo'yicha yozishma olib borishi mumkin. Tizimda AI yordamida diagnostika matnini generatsiya qilish funksiyasi ham mavjud.

### 3.6. Dashboard va analitika

Dashboard qismida quyidagilar ko'rsatiladi:

- jami testlar soni;
- jami modullar soni;
- platformaga kirgan talabalar statistikasi;
- kamida bitta modul ishlagan talabalar soni;
- barcha modullarni ishlagan talabalar soni;
- modul kesimidagi natijalar;
- kategoriyalar va fakultetlar kesimidagi diagrammalar.

Bu bo'lim rahbariyat yoki mas'ul xodimlar uchun tahliliy nazorat vositasi hisoblanadi.

## 4. Texnik yechimlar

Loyiha quyidagi texnologiyalar asosida ishlab chiqilgan:

- Backend: Laravel
- Frontend: Vue 3
- UI integratsiyasi: Inertia.js
- Styling: Tailwind CSS
- Grafiklar: Chart.js
- Real-time yozishma: Laravel Echo / Reverb
- Eksport: Excel va PDF

Bu texnologiyalar platformaga tezkorlik, interaktivlik va yaxshi foydalanuvchi tajribasini ta'minlaydi.

## 5. Mobil versiya bo'yicha hisobot

Mazkur loyihada mobil foydalanish ikki ko'rinishda ko'zda tutilgan:

### 5.1. Responsive veb versiya

Sayt interfeysi kichik ekranlarga moslashtirilgan. Bunda:

- `sm`, `md`, `lg`, `xl` breakpointlar orqali bloklar qayta joylashadi;
- sidebar mobil ekranda `drawer/sheet` ko'rinishida ochiladi;
- kartalar va grid elementlar kichik ekranlarda bitta ustunga tushadi;
- talaba va administrator bo'limlaridan telefon orqali ham foydalanish mumkin.

Bu yondashuv saytni telefon va planshetlarda qulay ko'rinishda ishlatish imkonini beradi.

### 5.2. Android mobil ilova

Bosh sahifada foydalanuvchi uchun tayyor Android ilova yuklab olish havolasi mavjud. `public/app-release.apk` fayli orqali mobil qurilmaga o'rnatiladigan APK taqdim etilgan. Demak, loyiha faqat responsive sayt bilan cheklanmagan, balki alohida Android distributivni ham o'z ichiga oladi.

Mobil versiyaning afzalliklari:

- telefon orqali tezkor kirish;
- foydalanuvchi uchun qulay foydalanish muhiti;
- talabalarga doimiy foydalanish imkoniyati;
- platformani veb brauzersiz ham tarqatish imkoniyati.

## 6. Kuchli tomonlari

Platformaning kuchli jihatlari quyidagilar:

- ta'lim muassasasi ehtiyojiga moslashtirilgan;
- rollar bo'yicha aniq ajratilgan boshqaruv tizimi mavjud;
- test, natija va analitika bir joyda jamlangan;
- chat va murojaatlar moduli mavjud;
- AI yordamida diagnostika tayyorlash imkoniyati bor;
- eksport funksiyalari mavjud;
- mobil versiya ham nazarda tutilgan.

## 7. Takomillashtirish bo'yicha tavsiyalar

Kelgusida quyidagi yo'nalishlarda tizimni yanada rivojlantirish mumkin:

- iOS mobil versiyasini ham yaratish;
- push bildirishnomalarni kengaytirish;
- test natijalari bo'yicha yanada chuqurroq analitik hisobotlar qo'shish;
- foydalanuvchi faoliyatini audit qilish imkoniyatlarini kuchaytirish;
- mobil ilova va veb interfeys dizaynini yagona dizayn tizimiga keltirish.

## 8. Xulosa

Xulosa qilib aytganda, ushbu platforma talabalar psixologik diagnostikasini raqamlashtirishga xizmat qiluvchi zamonaviy axborot tizimidir. Tizimda test topshirish, natijalarni ko'rish, tahlil qilish, murojaat yuborish va boshqaruv jarayonlarini amalga oshirish uchun yetarli funksiyalar mavjud.

Eng muhim jihatlardan biri shundaki, platforma nafaqat veb ko'rinishda, balki mobil foydalanish uchun ham moslashtirilgan. Responsive interfeys va Android APK mavjudligi foydalanuvchilar uchun qulaylikni yanada oshiradi.

## 9. Hisobot tayyorlashda asos bo'lgan fayllar

- `routes/web.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/StudentController.php`
- `app/Http/Controllers/AdminStudentController.php`
- `resources/js/pages/Welcome.vue`
- `resources/js/pages/Dashboard.vue`
- `resources/js/pages/Student/TakeTest.vue`
- `resources/js/pages/Student/Requests/Index.vue`
- `resources/js/components/ui/sidebar/Sidebar.vue`
- `public/app-release.apk`
