# ADR 0001: Layered Application Flow And Schema Boundaries

## Status

Accepted - April 21, 2026

## Context

`DashboardController` va `AdminStudentController` ichida query composition, business rules, prompt building, export orchestration va persistence logic bir joyga yig‘ilib qolgan edi. Bu holat:

- controllerlarni semiz qilardi
- testlash va qayta ishlatishni qiyinlashtirardi
- cache invalidation va query evolyutsiyasini markazlashtirishni qiyinlashtirardi

Schema tomonda ham ayrim denormalized maydonlar bor: `users_tests_results` pivotidagi natijalar va `conversations.last_message_at`.

## Decision

Kod qatlamlari quyidagicha ajratiladi:

- `Http/Controllers`: request validation, authorization, response composition
- `Http/Requests`: input validation va access gate
- `Application/*/Data`: typed filter/value objectlar
- `Application/*/Queries`: reusable query assembly va read-model context
- `Application/*/Services`: business orchestration, export, diagnosis, state changes
- `Services/*`: cross-cutting infratuzilma yoki shared domain-support servislar

Refactor doirasida:

- `DashboardController` page-building va export orchestration’dan tozalandi
- `AdminStudentController` filter/query, diagnosis, export, passport va result mutation logic’dan tozalandi

## Schema Audit

Saqlanadigan denormalization:

- `users_tests_results.result_fake`, `result_real`, `diagnosis`
  - Asos: bu jadval faqat bog‘lovchi pivot emas, balki test yakuni snapshoti hisoblanadi.
  - Foyda: result sahifasi, export va diagnosis oqimlarida qayta hisoblashni kamaytiradi.
  - Qoida: bu maydonlar derived summary sifatida ishlatiladi, lekin raw javoblar manbasi baribir `solve_tests`.

- `conversations.last_message_at`
  - Asos: conversation list sorting uchun hot-path ustun.
  - Foyda: har safar `MAX(messages.created_at)` aggregate qilish shart emas.
  - Qoida: message create oqimida transactionally yangilanadi va index bilan qo‘llanadi.

Normalization sifatida saqlanadigan manbalar:

- `solve_tests` raw answer history uchun source of truth bo‘lib qoladi
- `groups`, `specialities`, `faculities`, `categories`, `modules` alohida lookup jadval sifatida saqlanadi
- user profilida lookup nomlarini ko‘chirib saqlash qilinmaydi

Hozircha qo‘shilmaydigan denormalization:

- `users` jadvaliga `group_name`, `speciality_name`, `faculity_name` kabi duplicate text ustunlar
- `modules` yoki `users` ga tayyor statistik counter ustunlar
- chat unread count uchun persisted counter column

Sabab:

- bu qiymatlar query + cache bilan arzon yechiladi
- write-path complexity va inconsistency xavfini oshiradi
- hozirgi trafik uchun zarur emas

## Consequences

Ijobiy:

- controllerlar endi HTTP boundary bilan cheklanadi
- query logic reuse qilinadi
- domainga yaqin servislar testlash uchun qulayroq bo‘ladi
- schema qarorlari aniq chegaralandi: kerakli denormalization saqlandi, keraksizi qo‘shilmadi

Trade-off:

- fayllar soni ko‘paydi
- kichik feature uchun ko‘proq qatlam bo‘ladi
- service/query naming intizomi saqlanmasa, qatlamlash yana chalkashib ketishi mumkin

## Follow-up

- keyingi refactorlarda boshqa semiz controllerlar ham shu pattern bo‘yicha ajratiladi
- yangi persistence shortcut yoki denormalized column qo‘shishdan oldin shu ADR ga tayangan holda alohida qaror yoziladi
