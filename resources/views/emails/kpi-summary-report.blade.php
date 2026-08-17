<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no, address=no, email=no, date=no, url=no" />
    <title>{{ $reportTitle }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, h1, h2, h3, p, a, span {font-family: Arial, sans-serif !important;}
    </style>
    <![endif]-->
    <style type="text/css">
        /* Client-specific Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }

        /* Delta Pills */
        .delta-pill-pos {
            background-color: #ecfdf5;
            color: #047857;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #a7f3d0;
            display: inline-block;
            white-space: nowrap;
        }
        .delta-pill-neg {
            background-color: #fef2f2;
            color: #b91c1c;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #fecaca;
            display: inline-block;
            white-space: nowrap;
        }
        .delta-pill-neutral {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            display: inline-block;
            white-space: nowrap;
        }

        /* Responsive Breakpoints */
        @media only screen and (max-width: 620px) {
            .container-table {
                width: 100% !important;
                max-width: 100% !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                padding-bottom: 12px !important;
            }
            .column-spacer {
                display: none !important;
            }
            .content-padding {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <!-- Preheader invisible -->
    <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        Synthèse Analytics Complète : {{ $reportTitle }} ({{ $periodLabel }}) - MetricsDash Enterprise
    </div>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 32px 12px;">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="container-table" style="background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); width: 600px;">
                    
                    <!-- Header avec Logo & Titre de Marque -->
                    <tr>
                        <td align="center" style="background-color: #151651; padding: 28px 24px; border-bottom: 3px solid #10b981;">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" valign="middle">
                                        <a href="{{ $dashboardUrl ?? '#' }}" target="_blank" style="text-decoration: none;">
                                            <img src="{{ config('app.url') }}/logo.png" alt="MetricsDash Enterprise" width="160" style="display: block; width: 160px; max-height: 48px; border: 0; font-family: Arial, sans-serif; font-size: 20px; font-weight: bold; color: #ffffff;" />
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 8px;">
                                        <span style="font-family: Arial, sans-serif; font-size: 11px; font-weight: bold; color: #34d399; text-transform: uppercase; letter-spacing: 2px;">
                                            Plateforme Télémesure & Performance
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Titre & Période -->
                    <tr>
                        <td class="content-padding" style="padding: 32px 32px 16px 32px; background-color: #ffffff;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <span style="font-size: 11px; font-weight: 800; color: #151651; text-transform: uppercase; letter-spacing: 1.5px; background-color: #e0e7ff; padding: 4px 10px; border-radius: 6px;">
                                            {{ $frequencyLabel ?? 'Rapport Périodique' }}
                                        </span>
                                        <h1 style="margin: 12px 0 6px 0; font-size: 22px; font-weight: 900; color: #0f172a; line-height: 1.3;">
                                            {{ $reportTitle }}
                                        </h1>
                                        <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">
                                            Instance : <strong style="color: #0f172a;">{{ $instanceName ?? 'Toutes les instances' }}</strong> ({{ $instanceDomain ?? 'Global' }})<br />
                                            Période couverte : <strong style="color: #0f172a;">{{ $periodLabel }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Notification de Comparaison -->
                    <tr>
                        <td class="content-padding" style="padding: 0 32px 16px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 12px; color: #475569; font-weight: 600;">
                                        📊 <em>Les pourcentages d'évolution indiqués comparent cette période avec la période précédente équivalente.</em>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- SECTION 1 : AUDIENCE WEB & TEMPS D'ENGAGEMENT ACTIF -->
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 6px 32px;">
                            <h3 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 800; color: #151651; text-transform: uppercase; letter-spacing: 1px;">
                                👥 1. Audience Web, Temps d'Engagement Actif & Interactions
                            </h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                
                                <!-- Ligne 1 : Pages Vues & Visiteurs Uniques -->
                                <tr>
                                    <!-- Pages Vues -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                                                PAGES VUES
                                                            </td>
                                                            <td align="right">
                                                                @php $delta = $kpiDeltas['page_views'] ?? 0; @endphp
                                                                <span class="{{ $delta == 0 ? 'delta-pill-neutral' : ($delta > 0 ? 'delta-pill-pos' : 'delta-pill-neg') }}">
                                                                    {{ $delta == 0 ? '0.0%' : ($delta > 0 ? '▲ +' . number_format($delta, 1) . '%' : '▼ ' . number_format($delta, 1) . '%') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 8px;">
                                                                <span style="font-size: 22px; font-weight: 900; color: #151651;">
                                                                    {{ number_format($kpis['page_views'] ?? 0, 0, ',', ' ') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Spacer 16px -->
                                    <td class="column-spacer" width="16" style="width: 16px; font-size: 1px; line-height: 1px;">&nbsp;</td>

                                    <!-- Visiteurs Uniques -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                                                VISITEURS UNIQUES
                                                            </td>
                                                            <td align="right">
                                                                @php $delta = $kpiDeltas['unique_visitors'] ?? 0; @endphp
                                                                <span class="{{ $delta == 0 ? 'delta-pill-neutral' : ($delta > 0 ? 'delta-pill-pos' : 'delta-pill-neg') }}">
                                                                    {{ $delta == 0 ? '0.0%' : ($delta > 0 ? '▲ +' . number_format($delta, 1) . '%' : '▼ ' . number_format($delta, 1) . '%') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 8px;">
                                                                <span style="font-size: 22px; font-weight: 900; color: #151651;">
                                                                    {{ number_format($kpis['unique_visitors'] ?? 0, 0, ',', ' ') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Ligne 2 : Temps Moyen Actif & Taux de Rebond -->
                                <tr>
                                    <!-- Temps Moyen Actif -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #15803d; text-transform: uppercase;">
                                                                TEMPS MOYEN ACTIF
                                                            </td>
                                                            <td align="right">
                                                                @php $delta = $kpiDeltas['avg_active_duration'] ?? 0; @endphp
                                                                <span class="{{ $delta == 0 ? 'delta-pill-neutral' : ($delta > 0 ? 'delta-pill-pos' : 'delta-pill-neg') }}">
                                                                    {{ $delta == 0 ? '0.0%' : ($delta > 0 ? '▲ +' . number_format($delta, 1) . '%' : '▼ ' . number_format($delta, 1) . '%') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 8px;">
                                                                <span style="font-size: 22px; font-weight: 900; color: #166534;">
                                                                    {{ $kpis['avg_active_duration'] ?? 45 }}s
                                                                </span>
                                                                <span style="font-size: 11px; color: #15803d; font-weight: 600; margin-left: 6px;">
                                                                    (Session : {{ $kpis['avg_session_duration'] ?? 120 }}s)
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Spacer 16px -->
                                    <td class="column-spacer" width="16" style="width: 16px; font-size: 1px; line-height: 1px;">&nbsp;</td>

                                    <!-- Taux de Rebond -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #7e22ce; text-transform: uppercase;">
                                                                TAUX DE REBOND
                                                            </td>
                                                            <td align="right">
                                                                @php $delta = $kpiDeltas['bounce_rate'] ?? 0; @endphp
                                                                <span class="{{ $delta == 0 ? 'delta-pill-neutral' : ($delta < 0 ? 'delta-pill-pos' : 'delta-pill-neg') }}">
                                                                    {{ $delta == 0 ? '0.0%' : ($delta < 0 ? '▼ ' . number_format($delta, 1) . '%' : '▲ +' . number_format($delta, 1) . '%') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 8px;">
                                                                <span style="font-size: 22px; font-weight: 900; color: #581c87;">
                                                                    {{ number_format($kpis['bounce_rate'] ?? 0, 1, ',', ' ') }}%
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- SECTION 2 : PERFORMANCE FINANCIÈRE & CHIFFRE D'AFFAIRES -->
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 6px 32px;">
                            <h3 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 800; color: #151651; text-transform: uppercase; letter-spacing: 1px;">
                                💳 2. Performance Financière & Chiffre d'Affaires
                            </h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                
                                <!-- Ligne 1 : CA & MRR -->
                                <tr>
                                    <!-- Card 1 : Chiffre d'Affaires (CA) -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #15803d; text-transform: uppercase;">
                                                                CHIFFRE D'AFFAIRES (CA)
                                                            </td>
                                                            <td align="right">
                                                                @php $delta = $kpiDeltas['total_revenue'] ?? 0; @endphp
                                                                <span class="{{ $delta == 0 ? 'delta-pill-neutral' : ($delta > 0 ? 'delta-pill-pos' : 'delta-pill-neg') }}">
                                                                    {{ $delta == 0 ? '0.0%' : ($delta > 0 ? '▲ +' . number_format($delta, 1) . '%' : '▼ ' . number_format($delta, 1) . '%') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 8px;">
                                                                <span style="font-size: 24px; font-weight: 900; color: #166534;">
                                                                    {{ number_format($financialKpis['total_revenue'] ?? 0, 0, ',', ' ') }} €
                                                                </span>
                                                                <div style="font-size: 10px; color: #15803d; font-weight: 600; margin-top: 2px;">
                                                                    Revenu brut cumulé sur la période
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Spacer 16px -->
                                    <td class="column-spacer" width="16" style="width: 16px; font-size: 1px; line-height: 1px;">&nbsp;</td>

                                    <!-- Card 2 : Revenu Mensuel (MRR) -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                                                REVENU MENSUEL (MRR)
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 8px;">
                                                                <span style="font-size: 24px; font-weight: 900; color: #151651;">
                                                                    {{ number_format($financialKpis['mrr'] ?? ($financialKpis['total_revenue'] * 0.85), 0, ',', ' ') }} €
                                                                </span>
                                                                <div style="font-size: 10px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                                                    Abonnements et récurrences
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Ligne 2 : Panier Moyen & Transactions Validées -->
                                <tr>
                                    <!-- Card 3 : Panier Moyen (AOV) -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                                                PANIER MOYEN (AOV)
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 8px;">
                                                                <span style="font-size: 24px; font-weight: 900; color: #151651;">
                                                                    {{ number_format($financialKpis['average_order_value'] ?? 0, 0, ',', ' ') }} €
                                                                </span>
                                                                <div style="font-size: 10px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                                                    Revenu moyen par commande
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Spacer 16px -->
                                    <td class="column-spacer" width="16" style="width: 16px; font-size: 1px; line-height: 1px;">&nbsp;</td>

                                    <!-- Card 4 : Transactions Validées -->
                                    <td class="stack-column" width="260" valign="top" style="padding-bottom: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; border-collapse: separate;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                                                TRANSACTIONS VALIDÉES
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 8px;">
                                                                <span style="font-size: 24px; font-weight: 900; color: #151651;">
                                                                    {{ number_format($financialKpis['total_transactions'] ?? 0, 0, ',', ' ') }}
                                                                </span>
                                                                <div style="font-size: 10px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                                                    Paiements et ventes confirmées
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- SECTION PACKS DE SOUSCRIPTION -->
                    @if(!empty($packBreakdown) && count($packBreakdown) > 0)
                    <tr>
                        <td class="content-padding" style="padding: 6px 32px 14px 32px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 800; color: #151651; text-transform: uppercase; letter-spacing: 0.5px;">
                                    📦 Répartition du Chiffre d'Affaires par Packs de Souscription
                                </h4>
                                <div style="font-size: 11px; color: #64748b; margin-bottom: 12px;">
                                    Analyse détaillée des revenus et du nombre de paiements par formule tarifaire.
                                </div>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    @foreach($packBreakdown as $pack)
                                    <tr>
                                        <td style="padding-bottom: 10px;">
                                            <div style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px;">
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td style="font-size: 12px; font-weight: 800; color: #151651; text-transform: uppercase;">
                                                            {{ $pack['name'] }}
                                                        </td>
                                                        <td align="right" style="font-size: 11px; font-weight: bold; color: #64748b;">
                                                            {{ $pack['count'] }} paiement(s)
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" style="padding-top: 6px;">
                                                            <span style="font-size: 20px; font-weight: 900; color: #0f172a;">
                                                                {{ number_format($pack['revenue'] ?? 0, 0, ',', ' ') }} €
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-top: 6px; font-size: 10px; color: #64748b; font-weight: 600;">
                                                            Part du CA Total :
                                                        </td>
                                                        <td align="right" style="padding-top: 6px; font-size: 10px; font-weight: 800; color: #047857;">
                                                            {{ $pack['percentage'] ?? 100 }}%
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" style="padding-top: 4px;">
                                                            <div style="background-color: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden;">
                                                                <div style="background-color: #10b981; height: 6px; width: {{ min(100, max(5, $pack['percentage'] ?? 100)) }}%;"></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- SECTION 3 : TOP PAGES LES PLUS CONSULTÉES -->
                    @if(!empty($topPages) && count($topPages) > 0)
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 14px 32px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
                                📄 3. Top Pages les plus consultées
                            </h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                <tr style="background-color: #f8fafc; font-size: 10px; font-weight: bold; color: #475569;">
                                    <td style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Chemin / URL</td>
                                    <td align="right" style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Vues</td>
                                    <td align="right" style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Temps Actif Moyen</td>
                                </tr>
                                @foreach(array_slice($topPages, 0, 5) as $page)
                                <tr style="font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 12px; font-family: monospace; font-weight: bold; color: #151651;">
                                        {{ Str::limit($page['path'] ?? '/', 35) }}
                                    </td>
                                    <td align="right" style="padding: 8px 12px; font-weight: 700;">
                                        {{ number_format($page['views'] ?? 0, 0, ',', ' ') }}
                                    </td>
                                    <td align="right" style="padding: 8px 12px; color: #64748b;">
                                        {{ $page['avgDuration'] ?? 0 }}s
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- SECTION 4 : TOP INTERACTIONS & CLICS -->
                    @if(!empty($topClicks) && count($topClicks) > 0)
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 14px 32px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
                                ⚡ 4. Top Éléments & Boutons Cliqués
                            </h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                <tr style="background-color: #f8fafc; font-size: 10px; font-weight: bold; color: #475569;">
                                    <td style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Élément / Action</td>
                                    <td style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Sélecteur CSS</td>
                                    <td align="right" style="padding: 9px 12px; border-bottom: 1px solid #e2e8f0;">Clics</td>
                                </tr>
                                @foreach(array_slice($topClicks, 0, 4) as $click)
                                <tr style="font-size: 11px; color: #334155; border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 12px; font-weight: bold; color: #151651;">
                                        {{ Str::limit($click['label'] ?? 'Bouton', 25) }}
                                    </td>
                                    <td style="padding: 8px 12px; font-family: monospace; font-size: 10px; color: #64748b;">
                                        {{ Str::limit($click['selector'] ?? '', 28) }}
                                    </td>
                                    <td align="right" style="padding: 8px 12px; font-weight: 700; color: #047857;">
                                        {{ number_format($click['count'] ?? 0, 0, ',', ' ') }}
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- SECTION 5 : RÉPARTITION APPAREILS -->
                    @if(!empty($deviceBreakdown))
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 14px 32px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
                                💻 5. Répartition par Type d'Appareil
                            </h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                                <tr>
                                    @foreach($deviceBreakdown as $device => $cnt)
                                    <td align="center" style="padding: 10px 8px; border-right: 1px solid #e2e8f0;">
                                        <span style="font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase;">{{ $device }}</span>
                                        <div style="font-size: 14px; font-weight: 900; color: #151651; margin-top: 2px;">{{ number_format($cnt, 0, ',', ' ') }}</div>
                                    </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Section Pièces Jointes & Export -->
                    @if(!empty($attachedFormats))
                    <tr>
                        <td class="content-padding" style="padding: 10px 32px 16px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 12px; color: #166534; font-weight: 600;">
                                        📎 <strong>Pièces Jointes Incluses :</strong> {{ $attachedFormats }} joint(s) à cet email pour vos analyses approfondies.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Bouton Appel à l'Action (CTA Sécurisé) -->
                    <tr>
                        <td align="center" class="content-padding" style="padding: 14px 32px 32px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="background-color: #151651; border-radius: 12px; box-shadow: 0 4px 12px rgba(21, 22, 81, 0.25);">
                                        <a href="{{ $dashboardUrl ?? '#' }}" target="_blank" style="display: inline-block; padding: 14px 32px; font-family: Arial, sans-serif; font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 12px;">
                                            Consulter le Dashboard en Direct &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 12px 0 0 0; font-size: 11px; color: #94a3b8; font-weight: 500;">
                                Accès direct et authentifié à l'espace d'analyse de votre instance.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="content-padding" style="background-color: #f8fafc; padding: 20px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 11px; color: #64748b; font-weight: 500;">
                                Cet email a été envoyé automatiquement selon la planification configurée sur MetricsDash Enterprise.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                Pour modifier la fréquence ou les destinataires, accédez aux <a href="{{ $schedulesUrl ?? '#' }}" target="_blank" style="color: #151651; text-decoration: underline; font-weight: bold;">Paramètres des Rapports</a>.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 10px; color: #cbd5e1; font-family: monospace;">
                                &copy; {{ date('Y') }} MetricsDash Analytics Enterprise &bull; Tous droits réservés.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
