# 📊 TimeDesk - Gestion de Temps Professionnelle

Application web de suivi du temps de travail moderne, sécurisée et facile à utiliser.

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## ✨ Fonctionnalités

- ⏱️ **Suivi du temps** : Enregistrement précis des heures de travail et pauses
- 📈 **Statistiques détaillées** : Vue journalière, hebdomadaire, mensuelle et annuelle
- 🎯 **Objectifs** : Suivi de progression par rapport aux objectifs contractuels
- 🌓 **Thème sombre/clair** : Interface adaptable selon vos préférences
- 📱 **Design responsive** : Fonctionne sur desktop, tablette et mobile
- 💾 **SQLite** : Base de données locale, pas de configuration serveur
- 🔒 **Sécurisé** : Protection CSRF, validation des données, authentification optionnelle
- 📥 **Export CSV** : Exportez vos données facilement

## 🚀 Installation

### Prérequis

- PHP 8.1 ou supérieur
- Extension SQLite3 activée
- Serveur web (Apache, Nginx)

### Installation rapide

1. **Téléchargez les fichiers**
   ```bash
   git clone https://github.com/votre-repo/timedesk.git
   cd timedesk
   ```

2. **Configurez les permissions**
   ```bash
   chmod 755 data/ logs/
   ```

3. **Configurez votre serveur web**
   
   **Apache** : Le fichier `.htaccess` est déjà configuré
   
   **Nginx** : Ajoutez cette configuration
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

4. **Accédez à l'application**
   ```
   http://votre-domaine.com/
   ```

5. **Configuration initiale**
   
   Éditez `config/config.php` pour personnaliser :
   - Fuseau horaire
   - Heures contractuelles
   - Authentification (optionnelle)

## ⚙️ Configuration

### Fichier `config/config.php`

```php
// Horaires de travail
define('CONTRACT_WEEKLY_HOURS', 35.0);    // Heures hebdomadaires
define('MONTHLY_TARGET_HOURS', 151.67);   // Heures mensuelles

// Authentification
define('ENABLE_AUTH', false);             // true pour activer
define('AUTH_USERNAME', 'admin');
define('AUTH_PASSWORD_HASH', '...');      // Hash du mot de passe

// Sécurité
define('MAX_DESCRIPTION_LENGTH', 500);    // Longueur max descriptions
define('MAX_ENTRIES_PER_DAY', 50);        // Limite entrées/jour
```

### Activer l'authentification

1. Générez un hash de mot de passe :
   ```php
   <?php
   echo password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);
   ```

2. Dans `config/config.php` :
   ```php
   define('ENABLE_AUTH', true);
   define('AUTH_PASSWORD_HASH', 'le_hash_généré');
   ```

## 📁 Structure du projet

```
timedesk/
├── public/                 # Dossier public (racine web)
│   ├── index.php          # Point d'entrée
│   ├── .htaccess          # Config Apache
│   └── assets/
│       ├── css/           # Styles
│       └── js/            # Scripts
├── config/
│   └── config.php         # Configuration
├── src/
│   ├── Controllers/       # Contrôleurs
│   ├── Models/            # Modèles
│   ├── Helpers/           # Utilitaires
│   └── Core/              # Noyau (Router, Session)
├── views/
│   ├── layouts/           # Layouts
│   ├── pages/             # Pages
│   ├── partials/          # Partiels (header, footer)
│   └── components/        # Composants réutilisables
├── data/                  # Base de données SQLite
└── logs/                  # Fichiers de log
```

## 🎨 Architecture

### Pattern MVC

- **Models** : Gestion des données (Database, EntryManager, StatsCalculator)
- **Views** : Templates de présentation
- **Controllers** : Logique applicative (EntryController, ApiController)

### Classes principales

- `Database` : Singleton pour la connexion SQLite
- `EntryManager` : CRUD des entrées de temps
- `StatsCalculator` : Calculs statistiques
- `Validator` : Validation des données
- `TimeHelper` : Manipulation du temps
- `Auth` : Authentification
- `Session` : Gestion des sessions

## 🔒 Sécurité

### Mesures implémentées

✅ Protection CSRF sur tous les formulaires  
✅ Validation stricte des entrées  
✅ Requêtes SQL préparées (protection injection SQL)  
✅ Échappement HTML systématique (protection XSS)  
✅ Headers de sécurité (X-Frame-Options, CSP, etc.)  
✅ Hachage des mots de passe (bcrypt)  
✅ Limitation du nombre d'entrées  
✅ Contraintes de base de données  
✅ Sessions sécurisées  

## 📊 Utilisation

### Ajouter une entrée

1. Sélectionnez la date
2. Entrez l'heure de début (auto-complétée avec la dernière heure de fin)
3. Entrez l'heure de fin
4. Choisissez le type (Travail ou Pause)
5. Ajoutez une description (optionnel)
6. Cliquez sur "Enregistrer"

### Modifier une entrée

1. Cliquez sur "Modifier" dans le tableau
2. Modifiez les champs souhaités
3. Cliquez sur "Enregistrer"

### Filtrer les entrées

1. Utilisez les champs "Du" et "Au" dans le formulaire de filtres
2. Cliquez sur "Appliquer les filtres"

### Exporter les données

1. Cliquez sur le bouton "📥 Exporter en CSV"
2. Le fichier CSV sera téléchargé automatiquement

## 🛠️ Développement

### Mode développement

Dans `config/config.php` :
```php
define('ENV', 'development');
```

Cela active :
- Affichage des erreurs
- Messages de debug détaillés

### Tests

Pour ajouter des tests unitaires (PHPUnit) :

```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit tests/
```

### Contribution

1. Fork le projet
2. Créez une branche (`git checkout -b feature/AmazingFeature`)
3. Commitez vos changements (`git commit -m 'Add AmazingFeature'`)
4. Pushez la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📝 TODO / Évolutions futures

- [ ] Export PDF
- [ ] Graphiques interactifs (Chart.js)
- [ ] Gestion multi-utilisateurs
- [ ] API REST
- [ ] Application mobile (PWA)
- [ ] Import de données
- [ ] Rapports personnalisables
- [ ] Notifications par email
- [ ] Backup automatique
- [ ] Thèmes personnalisables
- [ ] Support multi-langues

## 🐛 Résolution de problèmes

### Base de données non créée

```bash
# Vérifiez les permissions
chmod 755 data/
```

### Erreur 500

- Vérifiez que PHP 8.1+ est installé
- Vérifiez que l'extension SQLite3 est activée
- Consultez les logs : `logs/php_errors.log`

### Styles CSS non chargés

- Vérifiez que le dossier `public/assets/` est accessible
- Vérifiez la configuration `.htaccess`

## 📄 License

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👤 Auteur

Développé avec ❤️ pour faciliter le suivi du temps de travail.

## 🙏 Remerciements

- [Bootstrap 5](https://getbootstrap.com/) - Framework CSS
- [Inter Font](https://rsms.me/inter/) - Police
- Inspiré par les meilleures pratiques de développement PHP moderne

---

**Note** : Cette application est conçue pour un usage personnel ou en petite équipe. Pour un usage en entreprise avec de nombreux utilisateurs, envisagez d'utiliser MySQL/PostgreSQL au lieu de SQLite.
