<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MetricsDash Enterprise')</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 20px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #151651;
            padding: 32px 24px;
            text-align: center;
        }
        .header img {
            max-height: 48px;
            width: auto;
        }
        .content {
            padding: 32px 32px 24px 32px;
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
        }
        .content h1 {
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            display: inline-block;
            background-color: #151651;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(21, 22, 81, 0.2);
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #151651;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header avec Logo MetricsDash -->
            <div class="header">
                <table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto;">
                    <tr>
                        <td align="center" valign="middle" style="padding-right: 12px;">
                            <img src="{{ config('app.url') }}/logo.png" alt="MetricsDash Logo" width="60" height="60" style="display: block; width: 60px; height: 60px; border-radius: 8px; border: 0;" />
                        </td>
                        <td align="left" valign="middle">
                            <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 900; color: #ffffff; line-height: 1.1; letter-spacing: -0.5px;">
                                MetricsDash 
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Contenu Principal -->
            <div class="content">
                @yield('content')
            </div>

            <!-- Pied de page réutilisable -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} MetricsDash Enterprise. Tous droits réservés.</p>
                <p>Cet e-mail automatique a été envoyé par la plateforme MetricsDash.</p>
            </div>
        </div>
    </div>
</body>
</html>
