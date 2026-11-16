<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #151651;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #151651;
            margin: 0 0 4px 0;
        }
        .subtitle {
            font-size: 10px;
            color: #64748b;
        }
        .badge {
            background-color: #151651;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
            display: inline-block;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #151651;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 16px;
            margin-bottom: 10px;
        }
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 10px;
        }
        .card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
        }
        .card-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .card-value {
            font-size: 16px;
            font-weight: bold;
            color: #151651;
            margin-top: 3px;
        }
        .card-delta {
            font-size: 9px;
            font-weight: bold;
            float: right;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .delta-pos {
            background-color: #ecfdf5;
            color: #047857;
        }
        .delta-neg {
            background-color: #fef2f2;
            color: #b91c1c;
        }
        .delta-neutral {
            background-color: #f1f5f9;
            color: #64748b;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #151651;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
        }
        .data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 10px;
        }
        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="footer">
        MetricsDash Enterprise &bull; Rapport Analytics Complet généré le {{ date('d/m/Y H:i') }} &bull; Confidentiel
    </div>

    <!-- En-tête -->
    <table class="header-table">
        <tr>
            <td>
                <h1 class="title">{{ $reportTitle }}</h1>
                <div class="subtitle">
                    Instance : <strong>{{ $instanceName }}</strong> ({{ $instanceDomain }}) &bull; Période : <strong>{{ $periodLabel }}</strong> &bull; Fréquence : <strong>{{ $frequencyLabel }}</strong>
                </div>
            </td>
            <td align="right" valign="top">
                <div class="badge">
                    METRICSDASH ENTERPRISE
                </div>
            </td>
        </tr>
    </table>

    <!-- Section 1 : Audience Web & Télémesure Engagement Actif -->
    <div class="section-title">1. Audience Web, Temps d'Engagement Actif & Interactions</div>
    <table class="grid-table">
        <tr>
            <td width="33%">
                <div class="card">
                    @php $d = $kpiDeltas['page_views'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d > 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d > 0 ? '▲ +' . number_format($d, 1) . '%' : '▼ ' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label">Pages Vues</div>
                    <div class="card-value">{{ number_format($kpis['page_views'] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="card">
                    @php $d = $kpiDeltas['unique_visitors'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d > 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d > 0 ? '▲ +' . number_format($d, 1) . '%' : '▼ ' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label">Visiteurs Uniques</div>
                    <div class="card-value">{{ number_format($kpis['unique_visitors'] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="card">
                    @php $d = $kpiDeltas['sessions'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d > 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d > 0 ? '▲ +' . number_format($d, 1) . '%' : '▼ ' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label">Sessions Totales</div>
                    <div class="card-value">{{ number_format($kpis['sessions'] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="33%">
                <div class="card" style="background-color: #ecfdf5; border-color: #a7f3d0;">
                    @php $d = $kpiDeltas['avg_active_duration'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d > 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d > 0 ? '▲ +' . number_format($d, 1) . '%' : '▼ ' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label" style="color: #047857;">Temps Moyen Actif</div>
                    <div class="card-value" style="color: #065f46;">{{ $kpis['avg_active_duration'] ?? 45 }}s</div>
                </div>
            </td>
            <td width="33%">
                <div class="card">
                    <div class="card-label">Durée de Session</div>
                    <div class="card-value">{{ $kpis['avg_session_duration'] ?? 120 }}s</div>
                </div>
            </td>
            <td width="33%">
                <div class="card" style="background-color: #faf5ff; border-color: #e9d5ff;">
                    @php $d = $kpiDeltas['bounce_rate'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d < 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d < 0 ? '▼ ' . number_format($d, 1) . '%' : '▲ +' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label" style="color: #7e22ce;">Taux de Rebond</div>
                    <div class="card-value" style="color: #581c87;">{{ number_format($kpis['bounce_rate'] ?? 0, 1, ',', ' ') }}%</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Section 2 : Chiffre d'Affaires & Performance Financière -->
    <div class="section-title">2. Chiffre d'Affaires, MRR & Performance Financière</div>
    <table class="grid-table">
        <tr>
            <td width="25%">
                <div class="card" style="background-color: #f0fdf4; border-color: #86efac;">
                    @php $d = $kpiDeltas['total_revenue'] ?? 0; @endphp
                    <span class="card-delta {{ $d == 0 ? 'delta-neutral' : ($d > 0 ? 'delta-pos' : 'delta-neg') }}">
                        {{ $d == 0 ? '0.0%' : ($d > 0 ? '▲ +' . number_format($d, 1) . '%' : '▼ ' . number_format($d, 1) . '%') }}
                    </span>
                    <div class="card-label" style="color: #15803d;">Chiffre d'Affaires (CA)</div>
                    <div class="card-value" style="color: #166534;">{{ number_format($financialKpis['total_revenue'] ?? 0, 0, ',', ' ') }} €</div>
                </div>
            </td>
            <td width="25%">
                <div class="card">
                    <div class="card-label">Revenu Mensuel (MRR)</div>
                    <div class="card-value" style="color: #151651;">{{ number_format($financialKpis['mrr'] ?? ($financialKpis['total_revenue'] * 0.85), 0, ',', ' ') }} €</div>
                </div>
            </td>
            <td width="25%">
                <div class="card">
                    <div class="card-label">Panier Moyen (AOV)</div>
                    <div class="card-value">{{ number_format($financialKpis['average_order_value'] ?? 0, 0, ',', ' ') }} €</div>
                </div>
            </td>
            <td width="25%">
                <div class="card">
                    <div class="card-label">Transactions Validées</div>
                    <div class="card-value">{{ number_format($financialKpis['total_transactions'] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Répartition par Packs de Souscription -->
    @if(!empty($packBreakdown) && count($packBreakdown) > 0)
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px;">
        <div style="font-size: 10px; font-weight: bold; color: #151651; text-transform: uppercase; margin-bottom: 4px;">
            Répartition du Chiffre d'Affaires par Packs de Souscription
        </div>
        <table class="data-table" style="margin-top: 4px; margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Formule / Pack</th>
                    <th style="text-align: right;">Paiements Confirmés</th>
                    <th style="text-align: right;">Montant Total (€)</th>
                    <th style="text-align: right;">Part du CA Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packBreakdown as $pack)
                <tr>
                    <td><strong>{{ $pack['name'] }}</strong></td>
                    <td style="text-align: right;">{{ $pack['count'] }} vente(s)</td>
                    <td style="text-align: right; font-weight: bold; color: #151651;">{{ number_format($pack['revenue'] ?? 0, 0, ',', ' ') }} €</td>
                    <td style="text-align: right; font-weight: bold; color: #047857;">{{ $pack['percentage'] ?? 100 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Section 3 : Top Pages les Plus Visitées -->
    @if(!empty($topPages) && count($topPages) > 0)
    <div class="section-title">3. Top Pages les Plus Visitées</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Chemin / URL</th>
                <th style="text-align: right;">Nombre de Vues</th>
                <th style="text-align: right;">Temps Actif Moyen</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($topPages, 0, 6) as $page)
            <tr>
                <td style="font-family: monospace; font-weight: bold; color: #151651;">{{ $page['path'] ?? '/' }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($page['views'] ?? 0, 0, ',', ' ') }}</td>
                <td style="text-align: right; color: #64748b;">{{ $page['avgDuration'] ?? 0 }}s</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Section 4 : Top Éléments & Boutons Cliqués -->
    @if(!empty($topClicks) && count($topClicks) > 0)
    <div class="section-title">4. Top Interactions & Boutons Cliqués</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Élément / Bouton</th>
                <th>Sélecteur CSS / Cible</th>
                <th style="text-align: right;">Nombre de Clics</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($topClicks, 0, 6) as $click)
            <tr>
                <td style="font-weight: bold; color: #151651;">{{ $click['label'] ?? 'Clic' }}</td>
                <td style="font-family: monospace; color: #64748b; font-size: 9px;">{{ $click['selector'] ?? '' }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($click['count'] ?? 0, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Section 5 : Répartition Appareils & Navigateurs -->
    @if(!empty($deviceBreakdown))
    <div class="section-title">5. Répartition Technique par Appareil & Navigateur</div>
    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right: 5px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type d'Appareil</th>
                            <th style="text-align: right;">Événements</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deviceBreakdown as $device => $count)
                        <tr>
                            <td style="font-weight: bold;">{{ $device }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($count, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td width="50%" valign="top" style="padding-left: 5px;">
                @if(!empty($browserBreakdown))
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Navigateur</th>
                            <th style="text-align: right;">Événements</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($browserBreakdown as $browser => $count)
                        <tr>
                            <td style="font-weight: bold;">{{ $browser }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($count, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </td>
        </tr>
    </table>
    @endif
</body>
</html>
