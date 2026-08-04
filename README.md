# MetricsDash Enterprise — Backend Engine ⚡

Moteur d'ingestion et d'analyse haute performance multi-tenant développé avec **Laravel 12**, **API Platform / REST Architecture** et **Eloquent ORM**.

---

## 🚀 Fonctionnalités Principales

- 🔐 **Sécurité & RBAC Multi-Tenant** : Isolation stricte des données analytiques par instance et par tenant (`X-Tenant-Id`).
- 📊 **Agrégation Analytique Temps Réel** : Calcul dynamique du Chiffre d'Affaires total (€), MRR, Panier Moyen (AOV) et ventilation par offres/packs.
- ⚡ **Proxy d'Instance Intermédiaire** : Interrogation directe des bases client via les clés SDK (`X-Push-Secret-Key`, `X-Instance-ID`).
- 📦 **Support SDK Client** : Endpoints REST et WebSocket pour la synchronisation automatique des transactions et événements.

---

## 📋 Prérequis

- **PHP** : `>= 8.2` (extensions `pdo`, `mbstring`, `openssl`, `json`, `sqlite3` ou `pdo_mysql`/`pdo_pgsql`)
- **Composer** : `>= 2.x`
- **Base de données** : SQLite (par défaut pour dev) ou PostgreSQL / MySQL

---

## 🛠️ Guide d'Installation Complète

### 1. Cloner le projet et accéder au dossier backend

```bash
cd backend
```

### 2. Installer les dépendances PHP via Composer

```bash
composer install
```

### 3. Configurer les variables d'environnement (`.env`)

Copiez le fichier d'exemple `.env.example` pour créer votre `.env` :

```bash
cp .env.example .env
```

Générez ensuite la clé d'application unique :

```bash
php artisan key:generate
```

Assurez-vous que la configuration de la base de données est correcte dans `.env` :

```env
APP_NAME="MetricsDash Engine"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Pour SQLite (créer le fichier si nécessaire) :
# DB_DATABASE=database/database.sqlite
```

Si vous utilisez SQLite, créez le fichier de base de données :

```bash
touch database/database.sqlite
```

### 4. Exécuter les migrations et les seeders de données initiales

```bash
php artisan migrate --seed
```

Cela créera la structure des tables (`instances`, `tenants`, `users`, `raw_events`, `payments`, `packs`) et alimentera la base avec les comptes administrateurs de test.

### 5. Démarrer le serveur de développement Laravel

```bash
php artisan serve --port=8000
```

Le serveur backend est accessible à l'adresse : **`http://localhost:8000`**

---

## 🧪 Tests & Qualité de Code

Exécutez la suite de tests PHPUnit / Pest :

```bash
php artisan test
```

Vérifiez le style du code avec Laravel Pint :

```bash
vendor/bin/pint
```

---

## 📡 Endpoints API Principaux

| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Authentification utilisateur & génération du jeton |
| `GET` | `/api/v1/instances` | Liste des instances autorisées pour le tenant |
| `GET` | `/api/v1/analytics/metrics` | Métriques financières et graphiques (filtres `instanceId`, `startDate`, `endDate`) |
| `POST` | `/api/v1/telemetry/push` | Ingestion d'événements haute fréquence par clé Push |

---

## 📜 Licence

Ce projet est sous licence propriétaire pour **MetricsDash Enterprise**.
