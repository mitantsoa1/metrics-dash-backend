# Guide d'Utilisation et de Test — MetricsDash Enterprise

Ce document présente les instructions complètes pour démarrer, utiliser et tester le projet **MetricsDash Enterprise** (Backend Laravel 12 + Frontend Next.js 16).

---

## 1. Architecture du Projet

Le projet est découpé en deux répertoires principaux :

```
metrics-dash/
├── backend/                           # API REST & Gateway Ingestion (Laravel 12 / DDD)
│   ├── app/Domains/IAM/               # Domaine IAM (EPIC-01)
│   └── app/Domains/Instance/          # Domaine Instance & Clés API (EPIC-02)
├── frontend/                          # Dashboard Analytics Enterprise (Next.js 16 App Router)
│   ├── src/modules/auth/              # Module Authentification
│   ├── src/modules/iam/               # Module Gestion des Membres & RBAC
│   └── src/modules/instance/          # Module Gestion des Instances Client
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

---

## 4. Guide de Test Manuel Interactif (Pas à Pas)

Suivez ces 7 étapes dans votre navigateur ou votre terminal :

### Étape 1 : Inscription d'un nouveau Tenant (EPIC-01)
1. Ouvrez l'URL : `http://localhost:3000/register`.
2. Saisissez les données de test :
   - **Nom de l'Organisation** : `Acme Enterprise`
   - **Votre Nom** : `Alice Founder`
   - **E-mail du Propriétaire** : `alice@acme-enterprise.com`
   - **Mot de passe** : `SecurePassword123!`
3. Cliquez sur **"Créer l'Organisation"**.
4. **Comportement attendu** :
   - Le backend enregistre atomiquement (`DB::transaction`) le Tenant et le compte Owner sous le rôle `OWNER`.
   - Le jeton Bearer Sanctum est généré et stocké dans l'`AuthContext`.
   - Vous êtes automatiquement redirigé vers la page d'accueil du Dashboard (`/`).

---

### Étape 2 : Test de Déconnexion & Re-connexion (EPIC-01)
1. Sur le Dashboard, cliquez sur l'icône de déconnexion dans le **Header** (haut à droite).
2. **Comportement attendu** :
   - Le jeton est purgé du `localStorage`.
   - Redirection vers `http://localhost:3000/login`.
3. Saisissez vos identifiants (`alice@acme-enterprise.com` / `SecurePassword123!`) et validez.
4. **Comportement attendu** : Accès rétabli au Dashboard.

---

### Étape 3 : Gestion des Membres & Contrôle d'Accès RBAC (EPIC-01)
1. Cliquez sur **"Membres & Rôles"** dans la Sidebar de gauche (`/members`).
2. Observez la liste : Le compte `Alice Founder` apparaît avec le badge violet **`OWNER`**.
3. Cliquez sur le bouton **"Ajouter un Membre"**.
4. Renseignez :
   - Nom : `Bob Analyst`
   - E-mail : `bob@acme-enterprise.com`
   - Mot de passe : `TempPassword123!`
   - Rôle : `ANALYST`
5. Cliquez sur **"Ajouter le membre"**.
6. **Comportement attendu** :
   - La requête `POST` est exécutée.
   - TanStack Query invalide automatiquement le cache.
   - Bob apparaît instantanément dans la table avec le badge vert **`ANALYST`**.

---

### Étape 4 : Création d'une Instance & Affichage Sécurisé des Clés (EPIC-02)
1. Cliquez sur **"Instances & Clés"** dans la Sidebar (`/instances`).
2. Cliquez sur **"Nouvelle Instance"**.
3. Renseignez :
   - Nom de l'Instance : `Application Web E-Commerce`
   - Nom de domaine (FQDN) : `shop.acme-enterprise.com`
   - Environnement : `PRODUCTION`
4. Cliquez sur **"Générer l'Instance et Clés"**.
5. **Comportement attendu** :
   - L'instance est enregistrée en BDD.
   - Les clés sont hachées en SHA-256 dans PostgreSQL.
   - Le **Modal de Sécurité** s'ouvre et affiche en clair :
     - **Clé Publique SDK** : `pk_live_...`
     - **Clé Privée Push** : `sk_live_...`
6. Cliquez sur l'icône **Copier** pour chaque clé et vérifiez le message de confirmation.
7. Fermez le modal : Dans le tableau, les clés apparaissent masquées (ex: `pk_live_••••••••`).

---

### Étape 5 : Test de Rotation des Clés API (EPIC-02)
1. Dans la ligne de l'instance créée, cliquez sur **"Rotate SDK"** ou **"Rotate Push"**.
2. Un message de confirmation vous avertit que l'ancienne clé sera immédiatement révoquée.
3. Validez la confirmation.
4. **Comportement attendu** :
   - L'Action `RotateApiKeysAction` supprime l'ancien hash SHA-256 en BDD et enregistre le nouveau hash.
   - Le modal s'ouvre avec la nouvelle clé brute régénérée.

---

### Étape 6 : Test de Collecte d'Événements SDK Web (EPIC-03)
Exécutez cette commande cURL en remplaçant `pk_live_...` par la clé publique de votre instance :

```bash
curl -X POST http://localhost:8000/api/v1/telemetry/sdk \
  -H "Content-Type: application/json" \
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
- **Comportement attendu** : HTTP `201 Created` avec la confirmation de l'événement et son `eventId`. L'adresse IP cliente est anonymisée en base de données dans la table `raw_events`.

---

### Étape 7 : Test d'Ingestion API Push Privée Serveur-à-Serveur (EPIC-03)
Exécutez cette commande cURL avec la clé privée `sk_live_...` :

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
- **Comportement attendu** : HTTP `201 Created` avec statut `ACCEPTED`. L'événement métier est immédiatement inséré dans la table `raw_events`.

---

## 5. Exécution des Tests Automatisés & Qualité

Pour valider l'intégrité du code avant tout déploiement :

### Tests Unitaires & Intégration Backend (PHPUnit)
```bash
cd backend
vendor/bin/phpunit
```
- **Vérifie** : La validation des formats FQDN, le hachage SHA-256 des clés API, l'enregistrement atomique des Tenants et l'ingestion d'événements SDK/Push (EPIC-03).

### Contrôle Qualité du Code Backend (Laravel Pint)
```bash
cd backend
vendor/bin/pint
```

### Vérification Statique & Typecheck Frontend (TypeScript)
```bash
cd frontend
npx tsc --noEmit
```

### Build de Production Frontend
```bash
cd frontend
npm run build
```
- **Vérifie** : La compilation sans avertissement de toutes les routes Next.js 16.

---

## 6. Documentation Interactive API (OpenAPI / Swagger)

La spécification OpenAPI est générée automatiquement par API Platform. Vous pouvez la consulter et tester les endpoints directement depuis votre navigateur à l'adresse :

👉 **`http://localhost:8000/docs`** (ou `http://localhost:8000/api`)


