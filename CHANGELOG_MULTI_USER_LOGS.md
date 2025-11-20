# Changelog - Système Multi-Utilisateur et Logs

## Nouvelles fonctionnalités

### 1. Système de logs complet

Un système de logs robuste a été ajouté avec les fonctionnalités suivantes :

- **Classe Logger** (`src/Helpers/Logger.php`) :
  - Niveaux de logs : DEBUG, INFO, WARNING, ERROR, CRITICAL
  - Rotation automatique des logs (max 10 MB par fichier)
  - Logs journaliers (un fichier par jour)
  - Conservation des 7 derniers jours de backups
  - Logs d'actions utilisateur avec contexte (IP, user agent, etc.)

- **Logs automatiques** :
  - Connexions/déconnexions
  - Création/modification/suppression d'entrées
  - Création/modification/suppression d'utilisateurs
  - Export CSV
  - Erreurs applicatives

- **Emplacement des logs** : `logs/timedesk_YYYY-MM-DD.log`

### 2. Système multi-utilisateur

#### Base de données

- **Nouvelle table `users`** :
  - `id` : Identifiant unique
  - `username` : Nom d'utilisateur (unique)
  - `email` : Email (optionnel)
  - `password_hash` : Hash du mot de passe (bcrypt)
  - `role` : Rôle (admin ou user)
  - `is_active` : Statut actif/inactif
  - `created_at`, `updated_at`, `last_login` : Timestamps

- **Modification de la table `entries`** :
  - Ajout de la colonne `user_id` (clé étrangère vers users)
  - Index sur `user_id` pour les performances
  - Contrainte CASCADE pour la suppression

#### Authentification

- **Classe Auth mise à jour** (`src/Helpers/Auth.php`) :
  - Support de plusieurs utilisateurs
  - Gestion des rôles (admin/user)
  - Méthodes : `isAdmin()`, `canAccess()`, `getUserId()`, `getRole()`
  - Compatible avec l'ancien système (si ENABLE_AUTH = false)

#### Gestion des utilisateurs

- **UserManager** (`src/Models/UserManager.php`) :
  - CRUD complet pour les utilisateurs
  - Validation des données
  - Vérification des doublons (username, email)
  - Protection contre la suppression du dernier admin
  - Mise à jour de la dernière connexion

- **UserController** (`src/Controllers/UserController.php`) :
  - Liste des utilisateurs (admin uniquement)
  - Création d'utilisateurs
  - Modification d'utilisateurs
  - Suppression d'utilisateurs
  - Protection des actions (admin uniquement)

#### Vues

- **Page de gestion des utilisateurs** (`views/pages/users.php`) :
  - Tableau avec tous les utilisateurs
  - Affichage des rôles, statuts, dernières connexions
  - Actions : modifier, supprimer

- **Formulaire utilisateur** (`views/pages/user-form.php`) :
  - Création et édition d'utilisateurs
  - Validation côté client et serveur
  - Gestion des mots de passe (optionnel en édition)

#### Filtrage par utilisateur

- **EntryManager** : Toutes les méthodes acceptent maintenant un `user_id` optionnel
- **StatsCalculator** : Les statistiques sont filtrées par utilisateur
- **EntryController** : Les utilisateurs non-admin voient uniquement leurs entrées
- **ApiController** : L'export CSV est filtré par utilisateur

#### Sécurité

- Les utilisateurs ne peuvent modifier/supprimer que leurs propres entrées
- Les administrateurs peuvent voir et gérer toutes les entrées
- Protection CSRF sur tous les formulaires
- Validation stricte des données utilisateur

## Migration

### Utilisateur par défaut

Un utilisateur admin est créé automatiquement au premier lancement :
- **Username** : `admin`
- **Password** : `admin`
- **Rôle** : `admin`

⚠️ **IMPORTANT** : Changez le mot de passe après la première connexion !

### Migration des données existantes

Les entrées existantes auront `user_id = NULL`. Pour les assigner à un utilisateur :

```sql
UPDATE entries SET user_id = 1 WHERE user_id IS NULL;
```

## Configuration

Aucune modification de configuration nécessaire. Le système est compatible avec l'ancien système d'authentification simple.

## Utilisation

### Pour les administrateurs

1. Accéder à la gestion des utilisateurs via le bouton "👥 Utilisateurs" dans le header
2. Créer/modifier/supprimer des utilisateurs
3. Voir toutes les entrées de tous les utilisateurs
4. Exporter toutes les données

### Pour les utilisateurs

1. Se connecter avec leurs identifiants
2. Voir uniquement leurs propres entrées
3. Créer/modifier/supprimer leurs entrées
4. Exporter leurs propres données

### Consultation des logs

Les logs sont disponibles dans le dossier `logs/` :
- Format : `timedesk_YYYY-MM-DD.log`
- Format des entrées : `[YYYY-MM-DD HH:MM:SS] [LEVEL] Message | Context: {...}`

Exemple :
```
[2025-01-20 14:30:15] [INFO] User action: login | Context: {"action":"login","user_id":1,"username":"admin","ip":"127.0.0.1"}
```

## Fichiers modifiés/créés

### Nouveaux fichiers
- `src/Helpers/Logger.php`
- `src/Models/UserManager.php`
- `src/Controllers/UserController.php`
- `views/pages/users.php`
- `views/pages/user-form.php`

### Fichiers modifiés
- `src/Models/Database.php` (schéma de base de données)
- `src/Helpers/Auth.php` (multi-utilisateurs)
- `src/Models/EntryManager.php` (filtrage par utilisateur)
- `src/Models/StatsCalculator.php` (filtrage par utilisateur)
- `src/Controllers/EntryController.php` (logs + filtrage)
- `src/Controllers/AuthController.php` (logs)
- `src/Controllers/ApiController.php` (logs + filtrage)
- `src/Helpers/Validator.php` (méthode csrfToken)
- `public/index.php` (routes utilisateurs + logs erreurs)
- `views/partials/header.php` (lien gestion utilisateurs)

## Notes importantes

1. **Sécurité** : Les mots de passe sont hashés avec bcrypt
2. **Performance** : Des index ont été ajoutés sur `user_id` pour optimiser les requêtes
3. **Compatibilité** : Le système reste compatible si `ENABLE_AUTH = false`
4. **Logs** : Les logs DEBUG sont ignorés en production

