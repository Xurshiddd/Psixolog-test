<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>Ijtimoiy-psixologik passport</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 24px 28px;
        }

        body {
            color: #2f3747;
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            margin: 0;
        }

        .header {
            margin-bottom: 26px;
            width: 100%;
        }

        .header-table,
        .content-table,
        .info-table {
            border-collapse: collapse;
            width: 100%;
        }

        .logo-cell {
            vertical-align: top;
            width: 37%;
        }

        .title-cell {
            color: #55698d;
            font-size: 30px;
            font-weight: 700;
            padding-top: 8px;
            text-align: center;
            vertical-align: top;
        }

        .logo-box {
            border: 1px solid #d5d8de;
            padding: 8px 10px;
            width: 238px;
        }

        .logo-layout {
            width: 100%;
        }

        .logo-icon-cell {
            vertical-align: top;
            width: 58px;
        }

        .logo-text-cell {
            padding-left: 10px;
            vertical-align: top;
        }

        .logo-box img {
            display: block;
            height: 58px;
            width: 58px;
        }

        .logo-title {
            color: #1f2430;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .logo-subtitle {
            color: #4c5565;
            font-size: 10px;
            margin-top: 8px;
        }

        .content-table td {
            vertical-align: top;
        }

        .left-column {
            padding-right: 22px;
            width: 52%;
        }

        .right-column {
            width: 48%;
        }

        .student-photo {
            border: 1px solid #cfd4dc;
            height: 150px;
            margin: 4px 0 20px;
            text-align: center;
            width: 120px;
        }

        .student-photo img {
            height: 150px;
            object-fit: cover;
            width: 120px;
        }

        .student-photo-placeholder {
            color: #78808e;
            font-size: 12px;
            line-height: 150px;
            text-align: center;
        }

        .student-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .box {
            border: 1px solid #d5d8de;
            margin-bottom: 22px;
            padding: 16px;
        }

        .box-title {
            font-size: 16px;
            font-style: italic;
            font-weight: 700;
            margin: 0 0 12px;
        }

        .traits-list {
            margin: 0;
            padding-left: 24px;
        }

        .traits-list li {
            line-height: 1.6;
            margin-bottom: 6px;
        }

        .temperament-text,
        .conclusion-text {
            font-size: 15px;
            font-style: italic;
            font-weight: 700;
            line-height: 1.45;
            margin: 0;
        }

        .info-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 14px;
        }

        .info-row td {
            border-top: 1px solid #e6e9ef;
            padding: 10px 0;
        }

        .info-row:first-child td {
            border-top: 0;
            padding-top: 0;
        }

        .info-label {
            color: #606a79;
            width: 42%;
        }

        .info-value {
            text-align: right;
            width: 58%;
        }
    </style>
</head>
<body>
    <table class="header-table header">
        <tr>
            <td class="logo-cell">
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
            <td class="title-cell">Ijtimoiy-psixologik passport</td>
        </tr>
    </table>

    <table class="content-table">
        <tr>
            <td class="left-column">
                <div class="student-photo">
                    @if($studentPictureDataUri)
                        <img src="{{ $studentPictureDataUri }}" alt="{{ $student->name }}">
                    @else
                        <div class="student-photo-placeholder">Student picture</div>
                    @endif
                </div>

                <div class="student-name">{{ $student->name ?? '-' }}</div>

                <div class="box">
                    <div class="info-title">Ma'lumotlar</div>

                    <table class="info-table">
                        <tr class="info-row">
                            <td class="info-label">Login:</td>
                            <td class="info-value">{{ $student->login ?? '-' }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Telefon:</td>
                            <td class="info-value">{{ $student->phone ?? '-' }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Guruh:</td>
                            <td class="info-value">{{ $student->group?->name ?? '-' }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Yo'nalish:</td>
                            <td class="info-value">{{ $student->speciality?->name ?? '-' }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Kategoriyalar:</td>
                            <td class="info-value">
                                {{ $student->usersCategory->pluck('name')->filter()->implode(', ') ?: '-' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <td class="right-column">
                <div class="box">
                    <div class="box-title">Xarakterdagi qobiliyatlar ketma-ketligi :</div>
                    <ol class="traits-list">
                        @foreach($passportData['character_traits'] as $trait)
                            <li>{{ $trait }}</li>
                        @endforeach
                    </ol>
                </div>

                <div class="box">
                    <p class="temperament-text">Temperament tipi: {{ $passportData['temperament_type'] }}</p>
                </div>

                <div class="box" style="margin-top: 64px;">
                    <p class="conclusion-text">Talaba: {{ $passportData['student_conclusion'] }}</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
