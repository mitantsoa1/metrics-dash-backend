# Guide d'Utilisation et de Test — MetricsDash Enterprise

Ce document présente les instructions complètes pour démarrer, utiliser et tester l'intégralité du projet **MetricsDash Enterprise** (Backend Laravel 12 + Frontend Next.js 16).

---

## 1. Architecture du Projet

Le projet est découpé en deux répertoires principaux :

```
metrics-dash/
├── backend/                           # API REST & Gateway Ingestion (Laravel 12 / DDD)
│   ├── app/Domains/IAM/               # Domaine IAM (EPIC-01)
│   ├── app/Domains/Instance/          # Domaine Instance & Clés API (EPIC-02)
│   ├── app/Domains/Ingestion/         # Domaine Ingestion, Traitement & Anonymisation (EPIC-03 & EPIC-04)
│   └── app/Domains/Analytics/         # Domaine Analytics, Agrégation & Quotas (EPIC-05 à EPIC-08)
├── frontend/                          # Dashboard Analytics Enterprise (Next.js 16 App Router)
│   ├── src/modules/auth/              # Module Authentification
│   ├── src/modules/iam/               # Module Gestion des Membres & RBAC
│   ├── src/modules/instance/          # Module Gestion des Instances Client
│   └── src/modules/analytics/         # Module Analytics & Visualisation (EPIC-06)
└── docs/                              # Documentation d'Architecture & Guide d'Utilisation
```

---

## 2. Pré-requis Systèmes & Installation

### Pré-requis
- **PHP** : `8.3+` (ou PHP 8.2+ compatible) avec extension `pdo`, `mbstring`, `openssl`, `json`.
- **Composer** : `2.8+`
- **Node.js** : `20.x` ou `22.x`
- **npm** : `10+`

### A. Initialisation du Backend (API Laravel 12)

```bash
# 1. Se déplacer dans le dossier backend
cd backend

# 2. Installer les dépendances Composer
composer install

# 3. Exécuter les migrations de base de données
php artisan migrate

# 4. Lancer le serveur d'API local
php artisan serve
```
Le serveur d'API s'exécute sur : **`http://localhost:8000`**.

### B. Initialisation du Frontend (Dashboard Next.js 16)

```bash
# 1. Se déplacer dans le dossier frontend (dans un second terminal)
cd frontend

# 2. Installer les dépendances Node.js
npm install

# 3. Lancer le serveur de développement Next.js
npm run dev
```
L'interface Web s'exécute sur : **`http://localhost:3000`**.

---

## 3. Matrice des Fonctionnalités & Endpoints Consommés

| EPIC | Fonctionnalité Attendue | Endpoint API Consommé | Composants UI / Clients |
| :--- | :--- | :--- | :--- |
| **EPIC-01** | Inscription Tenant & Owner | `POST /api/v1/tenants/register` | `/register` (`RegisterForm.tsx`) |
| **EPIC-01** | Connexion & Jeton Sanctum Bearer | `POST /api/v1/auth/login` | `/login` (`LoginForm.tsx`) |
| **EPIC-01** | Liste des Membres par Organisation | `GET /api/v1/tenants/{id}/members` | `/members` (`MembersList.tsx`) |
| **EPIC-01** | Invitation d'un Membre & Rôle RBAC | `POST /api/v1/tenants/{id}/members` | Modal (`AddMemberModal.tsx`) |
| **EPIC-02** | Création d'Instance (Site/App) | `POST /api/v1/instances` | `/instances` (`CreateInstanceModal.tsx`) |
| **EPIC-02** | Génération des Clés SDK & Push | Hachage SHA-256 en BDD | Modal (`KeysDisplayModal.tsx`) |
| **EPIC-02** | Rotation des Clés d'API | `POST /api/v1/instances/{id}/rotate-keys` | Boutons Rotate (`InstancesList.tsx`) |
| **EPIC-03** | Ingestion Événement SDK Web Public | `POST /api/v1/telemetry/sdk` | SDK Web Browser / Beacon |
| **EPIC-03** | Ingestion Événement Push Privé Backend | `POST /api/v1/telemetry/push` | Client Backend Node / Python / Go / PHP |
| **EPIC-04** | Processing & Anonymisation RGPD | Queue Horizon `high-ingestion` | `ProcessRawEventBatchJob.php` |
| **EPIC-04** | Enrichissement User-Agent & Device | `UserAgentParserService` | Attributs `device_type`, `browser`, `os` |
| **EPIC-05** | Agrégation des Métriques par Jour | `php artisan metrics:aggregate` | Table `daily_metrics` (`MetricAggregationService.php`) |
| **EPIC-06** | Dashboard Analytics & KPIs Live | `GET /api/v1/metrics` | `/analytics` (`AnalyticsDashboardClient.tsx`) |
| **EPIC-07** | Export de Rapports CSV / PDF | `POST /api/v1/reports/export` | `ReportExportStateProcessor.php` |
| **EPIC-07** | Envoi d'Emails de Rapport Automatises | Queue Horizon `exports-reports` | `SendAutomatedReportJob.php` |
| **EPIC-08** | Suivi du Quota d'Événements | `QuotaEvaluationService` | Méthode `getQuotaUsage()` |
| **EPIC-08** | Blocage HTTP 429 sur Dépassement | `EnforceQuotaMiddleware` | En-tête `X-Quota-Exceeded: true` |

---

## 4. Guide de Test Manuel Interactif (Pas à Pas)

Suivez ces 10 étapes dans votre navigateur ou votre terminal :

### Étape 1 : Inscription d'un nouveau Tenant (EPIC-01)
1. Ouvrez l'URL : `http://localhost:3000/register`.
2. Saisissez les données de test :
   - **Nom de l'Organisation** : `Acme Enterprise`
   - **Votre Nom** : `Alice Founder`
   - **E-mail du Propriétaire** : `alice@acme-enterprise.com`
   - **Mot de passe** : `SecurePassword123!`
3. Cliquez sur **"Créer l'Organisation"**.

---

### Étape 2 : Test de Déconnexion & Re-connexion (EPIC-01)
1. Sur le Dashboard, cliquez sur l'icône de déconnexion dans le **Header** (haut à droite).
2. Connectez-vous avec vos identifiants.

---

### Étape 3 : Gestion des Membres & Contrôle d'Accès RBAC (EPIC-01)
1. Allez sur `/members` et ajoutez un nouveau membre avec le rôle `ANALYST`.

---

### Étape 4 : Création d'une Instance & Affichage Sécurisé des Clés (EPIC-02)
1. Allez sur `/instances` et créez l'instance `shop.acme-enterprise.com`.
2. Obtenez les clés `pk_live_...` et `sk_live_...`.

---

### Étape 5 : Test de Rotation des Clés API (EPIC-02)
1. Cliquez sur **"Rotate SDK"** pour régénérer la clé d'API.

---

### Étape 6 : Test de Collecte d'Événements SDK Web (EPIC-03 & EPIC-04)
```bash
curl -X POST http://localhost:8000/api/v1/telemetry/sdk \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36" \
  -H "X-SDK-Key: pk_live_votre_cle_publique_sdk" \
  -d '{
    "eventName": "page_view",
    "visitorId": "vis_99887766",
    "sessionId": "sess_11223344",
    "payload": {
      "url": "https://shop.acme-enterprise.com/checkout",
      "title": "Page de Paiement"
    }
  }'
```

---

### Étape 7 : Test d'Ingestion API Push Privée Serveur-à-Serveur (EPIC-03 & EPIC-04)
```bash
curl -X POST http://localhost:8000/api/v1/telemetry/push \
  -H "Content-Type: application/json" \
  -H "X-Push-Secret-Key: sk_live_votre_cle_privee_push" \
  -d '{
    "eventName": "order_completed",
    "eventData": {
      "orderId": "ord_99482",
      "amount": 299.99,
      "currency": "EUR"
    }
  }'
```

---

### Étape 8 : Test du Moteur d'Agrégation des Métriques (EPIC-05)
```bash
php artisan metrics:aggregate
```

---

### Étape 9 : Visualisation sur le Tableau de Bord Analytics (EPIC-06)
1. Ouvrez l'URL : **`http://localhost:3000/analytics`**.
2. Consultez les cartes KPIs et graphiques interactifs.

---

### Étape 10 : Exportation des Rapports CSV (EPIC-07)
```bash
curl -X POST http://localhost:8000/api/v1/reports/export \
  -H "Content-Type: application/json" \
  -d '{
    "instanceId": "votre_instance_id",
    "format": "csv"
  }'
```
- **Comportement attendu** : HTTP `201 Created` avec lien de téléchargement et nombre d'enregistrements exportés.

---

## 5. Exécution des Tests Automatisés & Qualité

```bash
# Tests Backend (PHPUnit)
cd backend
php artisan test

# Build Frontend (Next.js 16)
cd frontend
npm run build
```

---

## 6. Documentation Interactive API (OpenAPI / Swagger)

La spécification OpenAPI est disponible et testable en direct sur :

👉 **`http://localhost:8000/docs`** (ou `http://localhost:8000/api`)
