<?php

declare(strict_types=1);

namespace App\OpenApi;

/**
 * Description des routes écrites à la main, absentes de la documentation générée par API Platform.
 * Chaque entrée : id, method, path, tag, summary, description, auth, params, body, responses.
 */
class CustomRoutes
{
    /** @return array<string, array<string, string>> */
    public static function securitySchemes(): array
    {
        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'Sanctum',
                'description' => 'Jeton obtenu par POST /api/v1/auth/login, envoyé dans « Authorization: Bearer <jeton> ».',
            ],
            'pushKey' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'sk_live_',
                'description' => 'Clé privée de l\'instance (sk_live_…), réservée au serveur du client. Header « Authorization: Bearer sk_live_… » (ou « X-Push-Secret-Key »). Régénérable depuis Instances & Clés ; la précédente est alors refusée.',
            ],
            'sdkKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-SDK-Key',
                'description' => 'Clé publique de l\'instance (pk_live_…), utilisable dans un navigateur.',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function tags(): array
    {
        return [
            'Ingestion' => 'Réception des événements métier envoyés par le serveur du client.',
            'SDK' => 'Vérification de la clé publique utilisée par le SDK navigateur.',
            'Filtres' => 'Valeurs de filtre du tableau de bord.',
            'Rapports générés' => 'Historique, création, téléchargement et suppression des rapports.',
            'Rapports planifiés' => 'Envois automatiques de rapports par e-mail (réservés aux rôles de gestion).',
            'Support' => 'Demandes d\'assistance et leur gestion par les administrateurs.',
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function operations(): array
    {
        $uuid = ['type' => 'string', 'format' => 'uuid'];
        $message = ['type' => 'object', 'properties' => ['message' => ['type' => 'string']]];
        $unauthenticated = ['description' => 'Non authentifié (jeton absent ou expiré).', 'schema' => $message];
        $forbidden = ['description' => 'Rôle insuffisant ou ressource d\'un autre tenant.', 'schema' => $message];
        $notFound = ['description' => 'Ressource introuvable.', 'schema' => $message];
        $invalid = ['description' => 'Données invalides ; le message indique le champ en cause.', 'schema' => ['type' => 'object', 'properties' => ['message' => ['type' => 'string'], 'errors' => ['type' => 'object']]]];
        $instanceId = ['name' => 'instanceId', 'in' => 'query', 'description' => 'Identifiant d\'instance, ou « all » pour toutes celles accessibles.', 'schema' => ['type' => 'string'], 'required' => false];
        $report = [
            'type' => 'object',
            'properties' => [
                'id' => $uuid, 'name' => ['type' => 'string'], 'format' => ['type' => 'string', 'enum' => ['PDF', 'EXCEL', 'CSV']],
                'instance_id' => $uuid + ['nullable' => true], 'period_start' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                'period_end' => ['type' => 'string', 'format' => 'date', 'nullable' => true], 'file_size' => ['type' => 'integer'],
                'records_count' => ['type' => 'integer'], 'kpi_summary' => ['type' => 'object', 'nullable' => true], 'created_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
        $scheduleBody = [
            'type' => 'object',
            'required' => ['name', 'frequency', 'time', 'formats', 'recipients'],
            'properties' => [
                'name' => ['type' => 'string', 'maxLength' => 255],
                'instance_id' => $uuid + ['nullable' => true, 'description' => 'Vide = toutes les instances accessibles.'],
                'frequency' => ['type' => 'string', 'enum' => ['DAILY', 'WEEKLY', 'MONTHLY']],
                'day_of_week' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 7, 'description' => '1 = lundi ; utilisé par WEEKLY.'],
                'day_of_month' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 31, 'description' => 'Utilisé par MONTHLY.'],
                'time' => ['type' => 'string', 'pattern' => '^[0-2][0-9]:[0-5][0-9]$', 'example' => '08:30'],
                'timezone' => ['type' => 'string', 'default' => 'Europe/Paris', 'example' => 'Europe/Paris'],
                'formats' => ['type' => 'string', 'enum' => ['PDF', 'EXCEL', 'BOTH']],
                'recipients' => ['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string', 'format' => 'email']],
                'included_kpis' => ['type' => 'array', 'items' => ['type' => 'string'], 'default' => ['audience', 'financials', 'top_pages']],
                'is_active' => ['type' => 'boolean', 'default' => true],
            ],
        ];
        $schedule = ['type' => 'object', 'properties' => ['id' => $uuid, 'tenant_id' => $uuid, 'instance_id' => $uuid + ['nullable' => true]] + $scheduleBody['properties']];
        $ticketStatus = ['type' => 'string', 'enum' => ['open', 'in_progress', 'resolved', 'closed']];
        $ticketPriority = ['type' => 'string', 'enum' => ['low', 'normal', 'high', 'urgent']];
        $ticket = [
            'type' => 'object',
            'properties' => [
                'id' => $uuid, 'name' => ['type' => 'string'], 'email' => ['type' => 'string', 'format' => 'email'], 'role' => ['type' => 'string'],
                'subject' => ['type' => 'string'], 'message' => ['type' => 'string'], 'status' => $ticketStatus, 'priority' => $ticketPriority,
                'admin_notes' => ['type' => 'string', 'nullable' => true], 'created_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
        $admins = 'Réservé aux rôles SUPERADMIN, OWNER et ADMIN. Le SUPERADMIN voit tous les tickets, les autres uniquement ceux de leur tenant.';
        $managers = 'Réservé aux rôles de gestion (OWNER, ADMIN, SUPERADMIN) ; les autres rôles reçoivent 403.';

        return [
            // ---------------------------------------------------------------- Ingestion
            [
                'id' => 'ingestion_push_event', 'method' => 'post', 'path' => ['/api/events', '/api/v1/events'], 'tag' => 'Ingestion', 'auth' => 'pushKey',
                'summary' => 'Envoyer un événement métier (commande, lead, panier…)',
                'description' => "Reçoit un événement poussé par le serveur du client et répond **202 sans attendre l'écriture** : l'événement est mis dans une file persistante puis enregistré par un worker (reprises automatiques en cas d'échec).\n\n- **Idempotence** : un rejeu avec le même `Idempotency-Key` (ou `event_id`) dans les 24 h renvoie le même `event_id` avec `duplicate: true`, sans doublon.\n- **Corrélation** : `X-Correlation-Id` (8 à 64 caractères) est repris s'il est fourni, sinon généré ; il est renvoyé dans la réponse et l'en-tête.\n- **Horodatage** : l'heure de réception est ajoutée côté serveur ; `timestamp` est l'heure de l'événement (formats courants acceptés).\n- **Limites** : corps de 256 Ko au plus ; 1000 événements par minute et par clé.\n- **Schéma extensible** : seul `event_type` est obligatoire, les champs inconnus de `payload` sont conservés tels quels. Une commande compte dans le chiffre d'affaires si `event_type` est un événement de commande connu (`order_completed`, `payment_success`, `subscription_created`, `checkout_success`) ou si `payload.amount` est présent.",
                'params' => [
                    ['name' => 'Idempotency-Key', 'in' => 'header', 'description' => 'Identifiant unique de l\'événement (par exemple le numéro de commande).', 'required' => false, 'schema' => ['type' => 'string']],
                    ['name' => 'X-Correlation-Id', 'in' => 'header', 'description' => '8 à 64 caractères : lettres, chiffres, point, tiret, souligné.', 'required' => false, 'schema' => ['type' => 'string']],
                ],
                'body' => [
                    'schema' => [
                        'type' => 'object', 'required' => ['event_type'],
                        'properties' => [
                            'event_type' => ['type' => 'string', 'example' => 'order_completed'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-10T10:00:00Z'],
                            'payload' => ['type' => 'object', 'description' => 'Champs libres ; usuels : order_id, amount, category, region, customer_id.', 'example' => ['order_id' => '1042', 'amount' => 59.9, 'category' => 'shoes', 'customer_id' => 'c-7']],
                            'visitor_id' => ['type' => 'string'], 'session_id' => ['type' => 'string'], 'event_id' => ['type' => 'string', 'description' => 'Équivalent de Idempotency-Key.'],
                        ],
                    ],
                ],
                'responses' => [
                    202 => ['description' => 'Accepté.', 'schema' => ['type' => 'object', 'properties' => ['status' => ['type' => 'string', 'example' => 'accepted'], 'event_id' => $uuid, 'duplicate' => ['type' => 'boolean'], 'correlation_id' => ['type' => 'string']]]],
                    400 => ['description' => 'Schéma invalide ; le champ manquant ou erroné est nommé.', 'schema' => ['type' => 'object', 'properties' => ['error' => ['type' => 'object', 'properties' => ['field' => ['type' => 'string', 'example' => 'event_type'], 'code' => ['type' => 'string', 'example' => 'required'], 'message' => ['type' => 'string']]], 'correlation_id' => ['type' => 'string']]]],
                    401 => ['description' => 'Clé absente, invalide ou régénérée.', 'schema' => $message],
                    413 => ['description' => 'Corps supérieur à 256 Ko.', 'schema' => ['type' => 'object', 'properties' => ['error' => ['type' => 'object'], 'correlation_id' => ['type' => 'string']]]],
                    429 => ['description' => 'Plus de 1000 événements par minute ; réessayer après le délai indiqué (en-tête Retry-After).'],
                ],
            ],
            // ---------------------------------------------------------------- SDK
            [
                'id' => 'sdk_verify_key', 'method' => 'get', 'path' => '/api/v1/sdk/verify', 'tag' => 'SDK', 'auth' => 'sdkKey',
                'summary' => 'Vérifier la clé publique du SDK',
                'description' => 'Appelé par le SDK au démarrage. Une clé invalide ou révoquée renvoie 401 : le SDK affiche alors une erreur dans la console et arrête d\'envoyer. Limité à 60 appels par minute.',
                'params' => [['name' => 'key', 'in' => 'query', 'description' => 'Clé publique, si le header X-SDK-Key ne peut pas être utilisé.', 'required' => false, 'schema' => ['type' => 'string']]],
                'responses' => [
                    200 => ['description' => 'Clé valable.', 'schema' => ['type' => 'object', 'properties' => ['valid' => ['type' => 'boolean', 'example' => true], 'instance_id' => $uuid]]],
                    401 => ['description' => 'Clé invalide ou révoquée.', 'schema' => $message],
                    429 => ['description' => 'Trop d\'appels.'],
                ],
            ],
            // ---------------------------------------------------------------- Filtres
            [
                'id' => 'metrics_filter_options', 'method' => 'get', 'path' => '/api/v1/metrics/filter-options', 'tag' => 'Filtres', 'auth' => 'bearerAuth',
                'summary' => 'Valeurs de filtre présentes sur la période',
                'description' => 'Catégories, régions et appareils réellement présents dans les événements de la période, avec leur nombre ; les valeurs sans donnée sont absentes. Sans catégorie explicite, le nom de l\'offre (`pack`) sert de catégorie.',
                'params' => [
                    $instanceId,
                    ['name' => 'startDate', 'in' => 'query', 'description' => 'Défaut : il y a 30 jours.', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date']],
                    ['name' => 'endDate', 'in' => 'query', 'description' => 'Défaut : aujourd\'hui.', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date']],
                ],
                'responses' => [
                    200 => ['description' => 'Valeurs disponibles.', 'schema' => ['type' => 'object', 'properties' => array_fill_keys(['categories', 'regions', 'devices'], ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['value' => ['type' => 'string'], 'count' => ['type' => 'integer']]]])]],
                    401 => $unauthenticated, 403 => $forbidden,
                ],
            ],
            // ---------------------------------------------------------------- Rapports générés
            [
                'id' => 'reports_download_csv', 'method' => 'get', 'path' => '/api/v1/reports/download', 'tag' => 'Rapports générés', 'auth' => 'bearerAuth',
                'summary' => 'Exporter les données en CSV',
                'description' => 'Télécharge un export CSV de la période, limité aux instances accessibles.',
                'params' => [
                    $instanceId,
                    ['name' => 'format', 'in' => 'query', 'description' => 'Format du fichier.', 'required' => false, 'schema' => ['type' => 'string', 'default' => 'csv']],
                    ['name' => 'startDate', 'in' => 'query', 'description' => 'Défaut : il y a 30 jours.', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date']],
                    ['name' => 'endDate', 'in' => 'query', 'description' => 'Défaut : aujourd\'hui.', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date']],
                ],
                'responses' => [200 => ['description' => 'Fichier CSV.', 'type' => 'text/csv'], 401 => $unauthenticated, 403 => $forbidden],
            ],
            [
                'id' => 'reports_list', 'method' => 'get', 'path' => '/api/v1/reports', 'tag' => 'Rapports générés', 'auth' => 'bearerAuth',
                'summary' => 'Historique des rapports générés',
                'description' => 'Les 100 derniers rapports du tenant, du plus récent au plus ancien.',
                'params' => [$instanceId, ['name' => 'format', 'in' => 'query', 'description' => 'PDF, EXCEL, CSV, ou « all ».', 'required' => false, 'schema' => ['type' => 'string']]],
                'responses' => [200 => ['description' => 'Liste de rapports.', 'schema' => ['type' => 'array', 'items' => $report]], 401 => $unauthenticated],
            ],
            [
                'id' => 'reports_create', 'method' => 'post', 'path' => '/api/v1/reports', 'tag' => 'Rapports générés', 'auth' => 'bearerAuth',
                'summary' => 'Enregistrer un rapport dans l\'historique',
                'description' => 'Pour le format CSV, le fichier est généré par le serveur à partir de la période ; pour PDF et EXCEL, `file_data` (contenu en base64) est conservé tel quel.',
                'body' => ['schema' => ['type' => 'object', 'required' => ['name', 'format'], 'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 255], 'format' => ['type' => 'string', 'enum' => ['PDF', 'EXCEL', 'CSV']], 'instance_id' => $uuid + ['nullable' => true],
                    'period_start' => ['type' => 'string', 'format' => 'date'], 'period_end' => ['type' => 'string', 'format' => 'date'], 'file_data' => ['type' => 'string', 'description' => 'Contenu en base64.'],
                    'file_size' => ['type' => 'integer'], 'records_count' => ['type' => 'integer'], 'kpi_summary' => ['type' => 'object'],
                ]]],
                'responses' => [201 => ['description' => 'Rapport enregistré.', 'schema' => $report], 401 => $unauthenticated, 403 => $forbidden, 422 => $invalid],
            ],
            [
                'id' => 'reports_download', 'method' => 'get', 'path' => '/api/v1/reports/{id}/download', 'tag' => 'Rapports générés', 'auth' => 'bearerAuth',
                'summary' => 'Télécharger un rapport de l\'historique',
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Fichier du rapport (PDF, Excel ou CSV).', 'type' => 'application/octet-stream'], 401 => $unauthenticated, 404 => $notFound],
            ],
            [
                'id' => 'reports_delete', 'method' => 'delete', 'path' => '/api/v1/reports/{id}', 'tag' => 'Rapports générés', 'auth' => 'bearerAuth',
                'summary' => 'Supprimer un rapport de l\'historique', 'description' => $managers,
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Supprimé.', 'schema' => $message], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound],
            ],
            // ---------------------------------------------------------------- Rapports planifiés
            [
                'id' => 'scheduled_reports_list', 'method' => 'get', 'path' => '/api/v1/scheduled-reports', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Lister les rapports planifiés',
                'description' => 'Chaque planification est renvoyée avec ses 5 derniers envois (statut, date, erreur éventuelle).',
                'params' => [$instanceId],
                'responses' => [200 => ['description' => 'Planifications.', 'schema' => ['type' => 'array', 'items' => $schedule]], 401 => $unauthenticated],
            ],
            [
                'id' => 'scheduled_reports_create', 'method' => 'post', 'path' => '/api/v1/scheduled-reports', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Créer un rapport planifié',
                'description' => $managers."\n\nLe rapport part par e-mail à l'heure prévue dans le fuseau indiqué (tâche planifiée du serveur, ± 5 minutes). Une planification identique à une existante (même instance, fréquence, jour, heure, fuseau, format) est refusée par **422** avec le nom de celle qui existe déjà. En cas d'échec d'envoi, les administrateurs reçoivent un e-mail d'alerte avec la prochaine tentative.",
                'body' => ['schema' => $scheduleBody],
                'responses' => [201 => ['description' => 'Planification créée.', 'schema' => $schedule], 401 => $unauthenticated, 403 => $forbidden, 422 => $invalid],
            ],
            [
                'id' => 'scheduled_reports_show', 'method' => 'get', 'path' => '/api/v1/scheduled-reports/{id}', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Détail d\'un rapport planifié',
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Planification.', 'schema' => $schedule], 401 => $unauthenticated, 404 => $notFound],
            ],
            [
                'id' => 'scheduled_reports_update', 'method' => 'patch', 'path' => '/api/v1/scheduled-reports/{id}', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Modifier, mettre en pause ou réactiver un rapport planifié',
                'description' => $managers.' Seuls les champs envoyés sont modifiés ; `is_active: false` met la planification en pause. `PUT` est accepté de la même façon.',
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'body' => ['schema' => ['type' => 'object', 'properties' => $scheduleBody['properties']]],
                'responses' => [200 => ['description' => 'Planification mise à jour.', 'schema' => $schedule], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound, 422 => $invalid],
            ],
            [
                'id' => 'scheduled_reports_replace', 'method' => 'put', 'path' => '/api/v1/scheduled-reports/{id}', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Modifier un rapport planifié (identique à PATCH)',
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'body' => ['schema' => ['type' => 'object', 'properties' => $scheduleBody['properties']]],
                'responses' => [200 => ['description' => 'Planification mise à jour.', 'schema' => $schedule], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound, 422 => $invalid],
            ],
            [
                'id' => 'scheduled_reports_delete', 'method' => 'delete', 'path' => '/api/v1/scheduled-reports/{id}', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Supprimer un rapport planifié', 'description' => $managers,
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Supprimé.', 'schema' => $message], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound],
            ],
            [
                'id' => 'scheduled_reports_test', 'method' => 'post', 'path' => '/api/v1/scheduled-reports/{id}/test', 'tag' => 'Rapports planifiés', 'auth' => 'bearerAuth',
                'summary' => 'Envoyer un e-mail de test immédiatement',
                'description' => $managers.' Sans `recipient`, l\'e-mail de test part à tous les destinataires de la planification.',
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'body' => ['required' => false, 'schema' => ['type' => 'object', 'properties' => ['recipient' => ['type' => 'string', 'format' => 'email']]]],
                'responses' => [
                    200 => ['description' => 'E-mail de test envoyé.', 'schema' => ['type' => 'object']],
                    401 => $unauthenticated, 403 => $forbidden, 404 => $notFound,
                    500 => ['description' => 'Échec de l\'envoi de test.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean', 'example' => false], 'message' => ['type' => 'string']]]],
                ],
            ],
            // ---------------------------------------------------------------- Support
            [
                'id' => 'support_ticket_create', 'method' => 'post', 'path' => '/api/v1/support/tickets', 'tag' => 'Support', 'auth' => null,
                'summary' => 'Envoyer une demande d\'assistance',
                'description' => 'Route publique (aucune connexion requise), limitée à 10 demandes par minute.',
                'body' => ['schema' => ['type' => 'object', 'required' => ['name', 'email', 'role', 'subject', 'message'], 'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100], 'email' => ['type' => 'string', 'format' => 'email', 'description' => 'Le domaine doit exister.'],
                    'role' => ['type' => 'string', 'maxLength' => 80], 'subject' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 200],
                    'message' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 5000], 'priority' => $ticketPriority + ['default' => 'normal'],
                ]]],
                'responses' => [
                    201 => ['description' => 'Demande enregistrée.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean'], 'message' => ['type' => 'string'], 'data' => $ticket]]],
                    422 => $invalid, 429 => ['description' => 'Plus de 10 demandes par minute.'],
                ],
            ],
            [
                'id' => 'admin_support_tickets_list', 'method' => 'get', 'path' => '/api/v1/admin/support/tickets', 'tag' => 'Support', 'auth' => 'bearerAuth',
                'summary' => 'Lister les demandes d\'assistance', 'description' => $admins,
                'params' => [
                    ['name' => 'status', 'in' => 'query', 'description' => 'Statut, ou « all ».', 'required' => false, 'schema' => $ticketStatus],
                    ['name' => 'priority', 'in' => 'query', 'description' => 'Priorité, ou « all ».', 'required' => false, 'schema' => $ticketPriority],
                    ['name' => 'search', 'in' => 'query', 'description' => 'Recherche dans le sujet, le message et l\'expéditeur.', 'required' => false, 'schema' => ['type' => 'string']],
                ],
                'responses' => [
                    200 => ['description' => 'Page de tickets, avec le nombre de tickets par statut.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean'], 'stats' => ['type' => 'object'], 'data' => ['type' => 'array', 'items' => $ticket], 'meta' => ['type' => 'object', 'properties' => ['current_page' => ['type' => 'integer'], 'last_page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer']]]]]],
                    401 => $unauthenticated, 403 => $forbidden,
                ],
            ],
            [
                'id' => 'admin_support_tickets_show', 'method' => 'get', 'path' => '/api/v1/admin/support/tickets/{id}', 'tag' => 'Support', 'auth' => 'bearerAuth',
                'summary' => 'Détail d\'une demande', 'description' => $admins,
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Ticket.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean'], 'data' => $ticket]]], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound],
            ],
            [
                'id' => 'admin_support_tickets_update', 'method' => 'patch', 'path' => '/api/v1/admin/support/tickets/{id}', 'tag' => 'Support', 'auth' => 'bearerAuth',
                'summary' => 'Changer le statut, la priorité ou les notes d\'une demande', 'description' => $admins,
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'body' => ['schema' => ['type' => 'object', 'properties' => ['status' => $ticketStatus, 'priority' => $ticketPriority, 'admin_notes' => ['type' => 'string', 'maxLength' => 5000, 'nullable' => true]]]],
                'responses' => [200 => ['description' => 'Ticket mis à jour.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean'], 'data' => $ticket]]], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound, 422 => $invalid],
            ],
            [
                'id' => 'admin_support_tickets_delete', 'method' => 'delete', 'path' => '/api/v1/admin/support/tickets/{id}', 'tag' => 'Support', 'auth' => 'bearerAuth',
                'summary' => 'Supprimer une demande', 'description' => $admins,
                'params' => [['name' => 'id', 'in' => 'path', 'schema' => $uuid]],
                'responses' => [200 => ['description' => 'Supprimée.', 'schema' => ['type' => 'object', 'properties' => ['success' => ['type' => 'boolean'], 'message' => ['type' => 'string']]]], 401 => $unauthenticated, 403 => $forbidden, 404 => $notFound],
            ],
        ];
    }
}
