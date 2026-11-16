====================================================
METRICSDASH ENTERPRISE — RAPPORT ANALYTICS COMPLET
====================================================

{{ $reportTitle }}
Instance : {{ $instanceName ?? 'Toutes les instances' }} ({{ $instanceDomain ?? 'Global' }})
Période couverte : {{ $periodLabel }}
Fréquence : {{ $frequencyLabel ?? 'Périodique' }}

----------------------------------------------------
1. AUDIENCE WEB & TEMPS D'ENGAGEMENT ACTIF
----------------------------------------------------
• Pages Vues : {{ number_format($kpis['page_views'] ?? 0, 0, ',', ' ') }} ({{ ($kpiDeltas['page_views'] ?? 0) >= 0 ? '+' : '' }}{{ $kpiDeltas['page_views'] ?? 0 }}%)
• Visiteurs Uniques : {{ number_format($kpis['unique_visitors'] ?? 0, 0, ',', ' ') }} ({{ ($kpiDeltas['unique_visitors'] ?? 0) >= 0 ? '+' : '' }}{{ $kpiDeltas['unique_visitors'] ?? 0 }}%)
• Sessions Totales : {{ number_format($kpis['sessions'] ?? 0, 0, ',', ' ') }} ({{ ($kpiDeltas['sessions'] ?? 0) >= 0 ? '+' : '' }}{{ $kpiDeltas['sessions'] ?? 0 }}%)
• Temps Moyen Actif : {{ $kpis['avg_active_duration'] ?? 45 }}s (Durée session : {{ $kpis['avg_session_duration'] ?? 120 }}s)
• Taux de Rebond : {{ number_format($kpis['bounce_rate'] ?? 0, 1, ',', ' ') }}%

----------------------------------------------------
2. CHIFFRE D'AFFAIRES & VENTES
----------------------------------------------------
• Chiffre d'Affaires : {{ number_format($financialKpis['total_revenue'] ?? 0, 2, ',', ' ') }} € ({{ ($kpiDeltas['total_revenue'] ?? 0) >= 0 ? '+' : '' }}{{ $kpiDeltas['total_revenue'] ?? 0 }}%)
• Ventes / Transactions : {{ number_format($financialKpis['total_transactions'] ?? 0, 0, ',', ' ') }}
• Panier Moyen : {{ number_format($financialKpis['average_order_value'] ?? 0, 2, ',', ' ') }} €

@if(!empty($topPages) && count($topPages) > 0)
----------------------------------------------------
3. TOP PAGES LES PLUS CONSULTÉES
----------------------------------------------------
@foreach(array_slice($topPages, 0, 5) as $page)
- {{ $page['path'] ?? '/' }} : {{ number_format($page['views'] ?? 0, 0, ',', ' ') }} vues ({{ $page['avgDuration'] ?? 0 }}s actif)
@endforeach
@endif

@if(!empty($topClicks) && count($topClicks) > 0)
----------------------------------------------------
4. TOP INTERACTIONS & BOUTONS CLIQUÉS
----------------------------------------------------
@foreach(array_slice($topClicks, 0, 5) as $click)
- {{ $click['label'] ?? 'Bouton' }} [{{ $click['selector'] ?? '' }}] : {{ number_format($click['count'] ?? 0, 0, ',', ' ') }} clics
@endforeach
@endif

@if(!empty($attachedFormats))
----------------------------------------------------
PIÈCES JOINTES
----------------------------------------------------
{{ $attachedFormats }} joint(s) à cet email.
@endif

Consulter le tableau de bord en direct :
{{ $dashboardUrl ?? 'https://metricsdash.app' }}

Gérer vos planifications de rapports :
{{ $schedulesUrl ?? 'https://metricsdash.app/dashboard/reports/schedules' }}

© {{ date('Y') }} MetricsDash Analytics Enterprise. Tous droits réservés.
