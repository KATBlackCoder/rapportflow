# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Unreleased]

### Changed
- Retrait de l'authentification à deux facteurs (2FA) de l'application

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
