# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Unreleased]

### Added
- Modèle Employee avec migration complète
  - Enums Position et EmployeeStatus (PHP 8.1+)
  - Relations Eloquent (user, manager, subordinates)
  - Accessors pour normalisation des noms (affichage et login)
  - Factory et Seeder avec création d'utilisateurs associés
  - Form Requests (StoreEmployeeRequest, UpdateEmployeeRequest)
  - Tests complets (validation, relations, contraintes, normalisation)
- Système d'authentification personnalisé
  - Authentification par username (format : `lastname@phone.org`)
  - Génération automatique de mot de passe par défaut (`ML+phone`)
  - Première connexion obligatoire avec choix de mot de passe
  - Middleware RequirePasswordChange
  - Page FirstLogin avec interface boutons (shadcn-vue)
  - Modification de Register.vue pour créer Employee + User
  - Modification de Login.vue pour utiliser username
  - Tests complets (inscription, première connexion, authentification)
- Organisation des enums dans `app/Enums/`
- Seeder avec employés de test (Manager, Chef Superviseur, Superviseur, Employer)
- Page Employees avec gestion complète
  - Page Index avec tableau utilisant shadcn-vue Table
  - Autorisation via EmployeePolicy (Manager/ChefSuperviseur uniquement)
  - Recherche en temps réel côté client
  - Pagination avec composants shadcn-vue Button
  - Affichage avec Avatar, Badges pour statut et position
  - Navigation conditionnelle dans AppSidebar (lien visible uniquement pour Manager/ChefSuperviseur)
- CRUD complet pour les employés
  - Composant EmployeeFormDialog pour créer/éditer
  - Formulaire avec validation Inertia useForm
  - Champs : ID employé, prénom, nom, email, téléphone, poste, statut, département, manager, salaire, date d'embauche
  - Suppression avec dialog de confirmation
  - Menu dropdown avec actions (Modifier, Supprimer) sur chaque ligne
  - Gestion des erreurs de validation affichées sous chaque champ
- Système d'autorisation avec Policies
  - EmployeePolicy pour gérer l'accès aux opérations CRUD
  - Vérification basée sur la position Employee (Manager/ChefSuperviseur)
  - Partage des données employee via HandleInertiaRequests pour l'UI
  - Tests d'autorisation (EmployeeAccessTest)

### Changed
- Retrait de l'authentification à deux facteurs (2FA) de l'application
- Champ `phone` dans Employee rendu obligatoire (NOT NULL)
- Structure des enums : déplacés de `app/` vers `app/Enums/` avec namespace `App\Enums`
- UserFactory : Ajout de la génération automatique de `username` dans la factory
- HandleInertiaRequests : Partage des données employee de l'utilisateur pour l'UI

### Fixed
- Correction de la validation dans FirstLoginController pour permettre l'action "keep" sans password
- Correction de la redirection vers le dashboard après "Keep the default password"
- Ajout de l'import manquant `Model` dans le modèle Employee
- Correction de l'erreur « `ProfileController.update.form is not a function` » sur Settings (Profile, Password, Delete User)
  - Remplacement de `v-bind="*.form()"` par `:action="*()"` ; Wayfinder est généré sans `--with-form`, le composant Form Inertia accepte l'objet `{ url, method }` en prop `action`

---

## [0.2.0] - 2025-01-25

### Removed
- Authentification à deux facteurs (2FA)
  - Suppression de la fonctionnalité 2FA de Fortify
  - Suppression des colonnes `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` de la table `users`
  - Suppression des contrôleurs, Form Requests, pages et composants Vue liés à la 2FA
  - Suppression des tests 2FA
  - Retrait de la navigation 2FA dans les paramètres

### Changed
- Modèle `User` : retrait du trait `TwoFactorAuthenticatable`
- Configuration Fortify : retrait de `Features::twoFactorAuthentication()`
- `FortifyServiceProvider` : retrait de la vue `twoFactorChallengeView` et du rate limiter `two-factor`
- `UserFactory` : retrait de la méthode `withTwoFactor()` et des champs 2FA

---

## [Unreleased]

### À venir
- Système de gestion des employés
- Création et gestion de questionnaires
- Collecte de données via questionnaires
- Génération automatisée de rapports
- Système de permissions et rôles

---

## [0.1.0] - 2025-01-25

### Added
- Configuration initiale du projet Laravel 12
- Intégration Inertia.js v2 pour communication SPA
- Configuration Laravel Fortify avec authentification complète
  - Inscription utilisateurs
  - Connexion/Déconnexion
  - Réinitialisation de mot de passe
  - Vérification d'email
- Configuration Laravel Wayfinder pour génération de routes TypeScript
- Configuration frontend avec Vue.js 3, TypeScript et Tailwind CSS v4
- Intégration shadcn-vue pour composants UI
- Page d'accueil (Welcome)
- Dashboard de base
- Pages d'authentification complètes
  - Login
  - Register
  - Forgot Password
  - Reset Password
  - Verify Email
  - Confirm Password
- Pages de paramètres utilisateur
  - Profil utilisateur (édition et suppression de compte)
  - Gestion du mot de passe
  - Apparence (thème clair/sombre)
- Layout principal avec sidebar et header
- Navigation principale
- Configuration des outils de développement
  - Pest v4 pour les tests
  - Laravel Pint pour le formatage PHP
  - ESLint et Prettier pour le formatage JavaScript/TypeScript
  - Laravel Sail pour l'environnement Docker
  - Laravel Boost pour les outils MCP
- Tests d'authentification (Pest)
- Tests du dashboard
- Tests des paramètres utilisateur
- Documentation initiale (PROGRESS, ARCHITECTURE, CHANGELOG)

### Changed
- Structure Laravel 12 avec configuration simplifiée dans `bootstrap/app.php`
- Middleware configuré de manière déclarative

### Security
- Protection CSRF automatique
- Rate limiting sur les routes d'authentification
- Sessions sécurisées avec cookies HTTP-only

---

## Format des Versions

- **Major** : Changements incompatibles avec les versions précédentes
- **Minor** : Nouvelles fonctionnalités rétrocompatibles
- **Patch** : Corrections de bugs rétrocompatibles

---

## Types de Changements

- **Added** : Nouvelles fonctionnalités
- **Changed** : Changements dans les fonctionnalités existantes
- **Deprecated** : Fonctionnalités qui seront supprimées
- **Removed** : Fonctionnalités supprimées
- **Fixed** : Corrections de bugs
- **Security** : Corrections de vulnérabilités

---

*Dernière mise à jour : 25 janvier 2025*
