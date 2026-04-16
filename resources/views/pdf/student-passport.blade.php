<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>Ijtimoiy-psixologik passport</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 14px 18px;
        }

        body {
            background: #ffffff;
            color: #243247;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
        }

        .page {
            border: 1px solid #cdd6e1;
            padding: 12px 14px 10px;
        }

        .top-rule {
            background: #425b84;
            height: 5px;
            margin: -12px -14px 10px;
        }

        .header-table,
        .main-table,
        .logo-layout,
        .meta-table,
        .profile-head-table,
        .info-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header {
            margin-bottom: 10px;
        }

        .header-left {
            vertical-align: top;
            width: 36%;
        }

        .header-center {
            padding: 0 10px;
            text-align: center;
            vertical-align: middle;
            width: 44%;
        }

        .header-right {
            vertical-align: top;
            width: 20%;
        }

        .logo-box,
        .meta-box,
        .profile-card,
        .section-card,
        .accent-card {
            background: #ffffff;
            border: 1px solid #d7dfe8;
        }

        .logo-box {
            padding: 6px 8px;
        }

        .logo-icon-cell {
            vertical-align: top;
            width: 54px;
        }

        .logo-text-cell {
            padding-left: 8px;
            vertical-align: top;
        }

        .logo-box img {
            display: block;
            height: 50px;
            width: 50px;
        }

        .logo-title {
            color: #1d2736;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .logo-subtitle {
            color: #667489;
            font-size: 9px;
            margin-top: 5px;
        }

        .document-name {
            color: #425b84;
            font-family: DejaVu Serif, serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.2px;
            margin: 0;
        }

        .document-subtitle {
            color: #6d7b90;
            font-size: 9px;
            letter-spacing: 1.1px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .meta-box {
            padding: 7px 9px;
        }

        .meta-label {
            color: #758297;
            font-size: 8px;
            text-transform: uppercase;
        }

        .meta-value {
            color: #223147;
            font-size: 10px;
            font-weight: 700;
            padding-top: 2px;
        }

        .meta-row + .meta-row td {
            border-top: 1px solid #e4eaf0;
            padding-top: 5px;
        }

        .meta-row td {
            padding-bottom: 5px;
        }

        .main-table td {
            vertical-align: top;
        }

        .left-column {
            padding-right: 10px;
            width: 41%;
        }

        .right-column {
            width: 59%;
        }

        .profile-card {
            padding: 12px;
        }

        .profile-head-table {
            margin-bottom: 10px;
        }

        .photo-cell {
            vertical-align: top;
            width: 112px;
        }

        .identity-cell {
            padding-left: 10px;
            vertical-align: top;
        }

        .student-photo {
            border: 1px solid #cad3df;
            height: 132px;
            text-align: center;
            width: 102px;
        }

        .student-photo img {
            display: block;
            height: 132px;
            object-fit: cover;
            width: 102px;
        }

        .student-photo-placeholder {
            color: #8592a5;
            font-size: 10px;
            line-height: 132px;
            text-transform: uppercase;
        }

        .student-name {
            color: #1d2736;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.25;
            margin: 1px 0 4px;
        }

        .student-role {
            color: #647287;
            font-size: 10px;
            line-height: 1.35;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .identity-note {
            background: #f3f6fa;
            border-left: 3px solid #425b84;
            color: #4d5e74;
            font-size: 10px;
            line-height: 1.35;
            padding: 6px 8px;
        }

        .section-title {
            border-bottom: 1px solid #dbe3ec;
            color: #425b84;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            margin: 0 0 8px;
            padding-bottom: 5px;
            text-transform: uppercase;
        }

        .info-row td {
            border-top: 1px solid #e8edf3;
            padding: 7px 0;
        }

        .info-row:first-child td {
            border-top: 0;
            padding-top: 0;
        }

        .info-label {
            color: #667489;
            width: 40%;
        }

        .info-value {
            color: #1f2b3d;
            font-weight: 700;
            text-align: right;
            width: 60%;
        }

        .section-card,
        .accent-card {
            margin-bottom: 10px;
            padding: 10px 12px;
        }

        .card-caption {
            color: #77859a;
            font-size: 9px;
            letter-spacing: 1.1px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .card-heading {
            color: #223147;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            margin: 0 0 8px;
        }

        .traits-list {
            margin: 0;
            padding-left: 18px;
        }

        .traits-list li {
            color: #2c3a4f;
            line-height: 1.45;
            margin-bottom: 2px;
        }

        .accent-card {
            background: #f6f8fb;
        }

        .temperament-text {
            color: #1e2a3b;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
            margin: 0;
        }

        .conclusion-text {
            border-left: 4px solid #425b84;
            color: #28374c;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
            padding-left: 10px;
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="top-rule"></div>

        <table class="header-table header">
            <tr>
                <td class="header-left">
                    <div class="logo-box">
                        <table class="logo-layout">
                            <tr>
                                <td class="logo-icon-cell">
                                    @if($logoDataUri)
                                        <img src="{{ $logoDataUri }}" alt="Logo">
                                    @endif
                                </td>
                                <td class="logo-text-cell">
                                    <p class="logo-title">Toshkent to‘qimachilik</p>
                                    <p class="logo-title">va yengil sanoat</p>
                                    <p class="logo-title">instituti</p>
                                    <div class="logo-subtitle">1932-yildan beri</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="header-center">
                    <h1 class="document-name">Ijtimoiy-psixologik passport</h1>
                    <div class="document-subtitle">Rasmiy psixologik tavsif hujjati</div>
                </td>
                <td class="header-right">
                    <div class="meta-box">
                        <table class="meta-table">
                            <tr class="meta-row">
                                <td>
                                    <div class="meta-label">Hujjat sanasi</div>
                                    <div class="meta-value">{{ now()->format('d.m.Y') }}</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="main-table">
            <tr>
                <td class="left-column">
                    <div class="profile-card">
                        <table class="profile-head-table">
                            <tr>
                                <td class="photo-cell">
                                    <div class="student-photo">
                                        @if($studentPictureDataUri)
                                            <img src="{{ $studentPictureDataUri }}" alt="{{ $student->name }}">
                                        @else
                                            <div class="student-photo-placeholder">Student picture</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="identity-cell">
                                    <div class="student-name">{{ $student->name ?? '-' }}</div>
                                    <div class="student-role">Talabaning ijtimoiy-psixologik profili</div>
                                    <div class="identity-note">
                                        Mazkur passport talabaning umumiy ma’lumotlari hamda psixologik tavsifini
                                        rasmiy ko‘rinishda aks ettirish uchun tayyorlandi.
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title">Asosiy ma’lumotlar</div>

                        <table class="info-table">
                            <tr class="info-row">
                                <td class="info-label">Login</td>
                                <td class="info-value">{{ $student->login ?? '-' }}</td>
                            </tr>
                            <tr class="info-row">
                                <td class="info-label">Telefon</td>
                                <td class="info-value">{{ $student->phone ?? '-' }}</td>
                            </tr>
                            <tr class="info-row">
                                <td class="info-label">Guruh</td>
                                <td class="info-value">{{ $student->group?->name ?? '-' }}</td>
                            </tr>
                            <tr class="info-row">
                                <td class="info-label">Yo‘nalish</td>
                                <td class="info-value">{{ $student->speciality?->name ?? '-' }}</td>
                            </tr>
                            <tr class="info-row">
                                <td class="info-label">Kategoriyalar</td>
                                <td class="info-value">
                                    {{ $student->usersCategory->pluck('name')->filter()->implode(', ') ?: '-' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>

                <td class="right-column">
                    <div class="section-card">
                        <div class="card-caption">1-bo‘lim</div>
                        <h2 class="card-heading">Xarakterdagi qobiliyatlar ketma-ketligi</h2>
                        <ol class="traits-list">
                            @foreach($passportData['character_traits'] as $trait)
                                <li>{{ $trait }}</li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="accent-card">
                        <div class="card-caption">2-bo‘lim</div>
                        <h2 class="card-heading">Temperament tavsifi</h2>
                        <p class="temperament-text">{{ $passportData['temperament_type'] }}</p>
                    </div>

                    <div class="section-card">
                        <div class="card-caption">3-bo‘lim</div>
                        <h2 class="card-heading">Talaba bo‘yicha xulosa</h2>
                        <p class="conclusion-text">{{ $passportData['student_conclusion'] }}</p>
                    </div>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>
