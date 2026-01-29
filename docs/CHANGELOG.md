# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Unreleased]

### Added
- **Bouton bascule thème (dark mode)** dans la sidebar (au-dessus du bloc utilisateur) et dans le header ; même logique que Paramètres > Apparence (useAppearance, bascule light/dark)
- **Dashboard adapté aux rôles (TASK-005)** : DashboardController avec stats et listes par position (employer, superviseur, chef_superviseur, manager) ; props Inertia (stats, recentReports, pendingCorrections, lastReport, availableQuestionnairesCount, canAccess*) ; requêtes agrégées sans N+1 ; stats enrichies (mySubmissionsLast30DaysCount, teamTotalSubmissionsCount) ; tests DashboardTest (accès, props pour utilisateur avec employee)
- **TASK-005** : Spécification Dashboard adapté aux rôles (`docs/tasks/TASK-005.md`) — contenu par rôle (employer, superviseur, chef_superviseur, manager), spécifications backend/frontend
- **Page Rapport (TASK-004)** : Page principale avec cartes par rôle ; remplissage questionnaires (tableau + mode copier-coller) ; Mes rapports, Corrections, Analyse ; RapportController avec filtres par rôle (supervisedEmployees pour superviseurs)
- **Hiérarchie Employee** : Champ `supervisor_id` (employé → superviseur obligatoire) ; validation manager/supervisor par département ; relations supervisedEmployees, supervisedSupervisors, supervisedChefSuperviseurs ; DatabaseSeeder avec hiérarchie par département (Marketing)
- **QuestionnaireResponse** : Modèle et migration pour réponses aux questionnaires (status submitted/returned_for_correction, correction_reason, reviewed_by)
- Système complet de gestion des questionnaires (TASK-003)
  - Modèles Questionnaire et Question avec migrations complètes
  - Enums QuestionnaireStatus, QuestionnaireTargetType et QuestionType (PHP 8.1+)
  - Relations Eloquent (questions, creator, conditionalQuestion, conditionalQuestions)
  - Form Requests StoreQuestionnaireRequest et UpdateQuestionnaireRequest avec validation complète
  - QuestionnaireController avec CRUD complet et transactions pour cohérence
  - QuestionnairePolicy pour autorisation (Manager/ChefSuperviseur pour CRUD, tous pour lecture)
  - Pages Vue Inertia complètes (Index, Create, Edit, Show)
  - Support de 8 types de questions : text, textarea, radio, checkbox, select, number, date, email
  - Ciblage par type : employees, supervisors
  - Interface utilisateur avec shadcn-vue (Table, Card, Dialog, Select, Badge, etc.)
- Modèle Employee avec migration complète, EmployeePolicy, Page Employees, CRUD, authentification personnalisée (username, première connexion), autorisation via Policies (voir détails ci-dessous)

### Changed
- **Dashboard** : Refactorisation en dossier `resources/js/pages/Dashboard/` — `Index.vue`, `types.ts`, `utils.ts`, composable `useDashboardPosition`, composants `StatCard`, `MetricCard` (une carte par indicateur), `QuickActions`, `RecentActivityCard` ; affichage des indicateurs par rôle en cartes ; Inertia rend `Dashboard/Index`
- **Questionnaires** : Amélioration de la gestion des questions conditionnelles
  - Questions conditionnelles disponibles pour tous les types de questions
  - Valeur conditionnelle via Select basé sur les options de la question parente
  - Gestion des retours à la ligne dans le textarea des options (white-space: pre-wrap)
  - Ciblage simplifié : employees et supervisors uniquement
- Retrait de l'authentification à deux facteurs (2FA) de l'application
- Champ `phone` dans Employee rendu obligatoire (NOT NULL)
- Structure des enums : déplacés de `app/` vers `app/Enums/` avec namespace `App\Enums`
- UserFactory : génération automatique de `username` ; HandleInertiaRequests : partage des données employee pour l'UI

### Fixed
- **Questionnaires** : Correction de la violation de contrainte de clé étrangère lors de la mise à jour
  - Utilisation de `conditional_question_index` au lieu de `conditional_question_id` lors de la soumission
  - Résolution correcte des IDs conditionnels après création des questions dans le contrôleur
  - Gestion cohérente entre création et mise à jour pour éviter les erreurs de contrainte
- **QuestionnairePolicy** : viewAny et view restreints à Manager et ChefSuperviseur (employés et superviseurs n'ont plus accès à la page Questionnaires)
- **AppSidebar** : Lien « Questionnaires » affiché uniquement pour Manager et ChefSuperviseur (`canViewQuestionnaires`)
- **Migrations** : Consolidation — `create_users_table` (username, password_changed_at, email nullable) ; `create_employees_table` (supervisor_id) ; suppression migrations two_factor, add_username, add_supervisor_id, optimize_database_indexes
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

*Dernière mise à jour : 29 janvier 2026*
