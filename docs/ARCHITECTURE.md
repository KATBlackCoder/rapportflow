# Architecture RapportFlow

## Vue d'ensemble

RapportFlow est une application full-stack moderne construite avec Laravel 12 et Vue.js 3, utilisant Inertia.js pour créer une expérience SPA sans la complexité d'une API séparée.

---

## Stack Technique

### Backend
- **Laravel 12.0** - Framework PHP moderne avec structure simplifiée
- **PHP 8.4.1** - Version récente de PHP avec toutes les fonctionnalités modernes
- **Inertia.js v2** - Communication SPA entre Laravel et Vue.js
- **Laravel Fortify v1.30** - Authentification headless
- **Laravel Wayfinder v0.1.9** - Génération de routes TypeScript

### Frontend
- **Vue.js 3.5.13** - Framework JavaScript réactif
- **TypeScript 5.2.2** - Typage statique pour JavaScript
- **Tailwind CSS v4.1.1** - Framework CSS utility-first
- **shadcn-vue** - Composants UI réutilisables
- **Vite 7.0.4** - Build tool moderne et rapide

### Outils de Développement
- **Pest v4.3** - Framework de tests PHP avec support navigateur
- **Laravel Pint v1.24** - Formateur de code PHP
- **ESLint 9.17.0** - Linter JavaScript/TypeScript
- **Prettier 3.4.2** - Formateur de code JavaScript/TypeScript
- **Laravel Sail v1.41** - Environnement Docker
- **Laravel Boost v1.8** - Outils MCP pour développement

---

## Architecture Générale

### Pattern Architectural

L'application suit une architecture **MVC modifiée** avec Inertia.js :

```
┌─────────────────┐
│   Vue.js (SPA)  │
│   Components    │
└────────┬────────┘
         │ Inertia.js
         │ (JSON Props)
┌────────▼────────┐
│  Laravel API   │
│  Controllers   │
└────────┬────────┘
         │
┌────────▼────────┐
│  Eloquent ORM  │
│    Models       │
└────────┬────────┘
         │
┌────────▼────────┐
│   Database     │
└────────────────┘
```

### Communication Frontend/Backend

- **Inertia.js** gère la communication entre Vue.js et Laravel
- Les contrôleurs Laravel retournent des props via `Inertia::render()`
- Pas d'API REST séparée - tout passe par Inertia
- Les formulaires utilisent `useForm` d'Inertia pour la soumission

---

## Structure du Projet

### Backend (Laravel 12)

```
app/
├── Actions/
│   └── Fortify/          # Actions d'authentification
├── Concerns/              # Traits réutilisables
├── Enums/                 # Enums PHP (Position, EmployeeStatus)
├── Http/
│   ├── Controllers/      # Contrôleurs
│   ├── Middleware/       # Middleware personnalisés
│   └── Requests/         # Form Requests (validation)
├── Models/               # Modèles Eloquent
└── Providers/            # Service Providers

bootstrap/
└── app.php               # Configuration Laravel 12 (middleware, routes)

routes/
├── web.php               # Routes web principales
├── settings.php          # Routes de paramètres
└── console.php           # Commandes Artisan

config/                   # Fichiers de configuration
database/
├── factories/           # Factories pour tests
├── migrations/          # Migrations de base de données
└── seeders/             # Seeders
```

### Frontend (Vue.js)

```
resources/js/
├── components/           # Composants Vue réutilisables
│   ├── ui/              # Composants UI (shadcn-vue)
│   └── ...
├── layouts/             # Layouts de pages
│   ├── app/             # Layout principal avec sidebar
│   └── settings/        # Layout des paramètres
├── pages/               # Pages Inertia (équivalent aux vues)
│   ├── auth/            # Pages d'authentification
│   ├── settings/        # Pages de paramètres
│   └── ...
├── types/               # Types TypeScript
└── app.ts               # Point d'entrée de l'application
```

---

## Décisions Techniques (ADR)

### ADR-001 : Utilisation d'Inertia.js au lieu d'une API REST

**Contexte :** Besoin d'une expérience SPA sans la complexité d'une API séparée.

**Décision :** Utiliser Inertia.js v2 pour la communication entre Laravel et Vue.js.

**Conséquences :**
- ✅ Pas besoin de créer une API REST complète
- ✅ Partage de code backend/frontend simplifié
- ✅ Authentification via sessions (plus simple que JWT)
- ✅ Props typées avec TypeScript via Wayfinder
- ⚠️ Moins de flexibilité pour des clients externes (mobile, etc.)

**Alternatives considérées :**
- API REST avec Vue.js séparé (rejeté : trop de complexité)
- Livewire (rejeté : moins de contrôle sur le frontend)

---

### ADR-002 : Structure Laravel 12 simplifiée

**Contexte :** Laravel 12 introduit une structure simplifiée sans `app/Http/Kernel.php`.

**Décision :** Utiliser la nouvelle structure Laravel 12 avec configuration dans `bootstrap/app.php`.

**Conséquences :**
- ✅ Configuration centralisée et plus claire
- ✅ Moins de fichiers de configuration
- ✅ Aligné avec les meilleures pratiques Laravel modernes
- ⚠️ Migration nécessaire si upgrade depuis Laravel 11

---

### ADR-003 : Authentification avec Laravel Fortify

**Contexte :** Besoin d'un système d'authentification complet et sécurisé.

**Décision :** Utiliser Laravel Fortify pour l'authentification headless.

**Conséquences :**
- ✅ Authentification complète (login, register, 2FA, password reset)
- ✅ Personnalisable via Actions
- ✅ Intégration native avec Laravel
- ✅ Support 2FA avec QR codes
- ⚠️ Moins de contrôle que Breeze (mais plus simple)

**Fonctionnalités activées :**
- Registration
- Password Reset
- Email Verification

---

### ADR-007 : Système d'Authentification Personnalisé avec Username

**Contexte :** Besoin d'un système d'authentification adapté au contexte malien avec génération automatique d'identifiants.

**Décision :** Implémenter un système d'authentification par username avec génération automatique depuis les données Employee.

**Spécifications :**
- **Format username** : `normalized_last_name@phone.org` (ex: `traore@12345678.org`)
- **Mot de passe par défaut** : `ML+phone` (ex: `ML12345678`)
- **Normalisation** : `last_name` normalisé en minuscules sans accents pour le username
- **Première connexion** : Obligation de choisir de garder ou changer le mot de passe

**Conséquences :**
- ✅ Authentification simplifiée sans email
- ✅ Génération automatique des identifiants
- ✅ Sécurité : changement de mot de passe obligatoire à la première connexion
- ✅ Cohérence avec les données Employee
- ⚠️ Format de mot de passe par défaut faible (d'où l'obligation de changement)

**Implémentation :**
- Migration : Ajout de `username` et `password_changed_at` à `users`, `email` nullable
- `CreateNewUser` : Génération automatique username/password depuis Employee
- Middleware `RequirePasswordChange` : Force le changement de mot de passe
- Page `FirstLogin` : Interface pour choisir de garder ou changer le mot de passe

---

### ADR-004 : TypeScript pour le Frontend

**Contexte :** Besoin de typage statique pour éviter les erreurs à l'exécution.

**Décision :** Utiliser TypeScript pour tout le code frontend.

**Conséquences :**
- ✅ Détection d'erreurs à la compilation
- ✅ Meilleure autocomplétion dans l'IDE
- ✅ Refactoring plus sûr
- ✅ Types générés par Wayfinder pour les routes
- ⚠️ Courbe d'apprentissage pour les développeurs non familiers

---

### ADR-005 : Tailwind CSS + shadcn-vue

**Contexte :** Besoin d'une interface moderne et cohérente.

**Décision :** Utiliser Tailwind CSS v4 avec shadcn-vue pour les composants.

**Conséquences :**
- ✅ Design system cohérent
- ✅ Composants accessibles
- ✅ Personnalisation facile via Tailwind
- ✅ Composants copiables (pas de dépendance npm)
- ✅ Dark mode natif

---

### ADR-006 : Tests avec Pest v4

**Contexte :** Besoin d'un framework de tests moderne et expressif.

**Décision :** Utiliser Pest v4 pour tous les tests.

**Conséquences :**
- ✅ Syntaxe expressive et lisible
- ✅ Support des tests navigateur (E2E)
- ✅ Intégration native avec Laravel
- ✅ Datasets pour tests paramétrés
- ✅ Meilleure expérience développeur

---

### ADR-008 : Organisation des Enums PHP

**Contexte :** Besoin d'organiser les enums PHP de manière cohérente avec la structure Laravel moderne.

**Décision :** Placer tous les enums dans `app/Enums/` avec le namespace `App\Enums`.

**Conséquences :**
- ✅ Organisation claire et séparée des types
- ✅ Cohérence avec la structure Laravel (Models/, Controllers/, etc.)
- ✅ Scalabilité pour ajouter de nouveaux enums
- ✅ Conventions Laravel modernes respectées

**Enums actuels :**
- `Position` : employer, superviseur, chef_superviseur, manager
- `EmployeeStatus` : active, inactive, suspended, terminated

---

## Patterns de Code

### Contrôleurs

Les contrôleurs suivent le pattern standard Laravel avec Form Requests pour la validation :

```php
// Exemple : ProfileController
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $request->user()->fill($request->validated())->save();
    return redirect()->route('profile.edit');
}
```

### Form Requests

Toute validation se fait via Form Requests :

```php
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user())],
        ];
    }
}
```

### Composants Vue

Les composants utilisent `<script setup>` avec TypeScript :

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
})
</script>
```

### Routes TypeScript

Les routes sont générées par Wayfinder et utilisées côté frontend :

```typescript
import { update } from '@/actions/App/Http/Controllers/ProfileController'

form.submit(update())
```

---

## Sécurité

### Authentification
- Sessions sécurisées avec cookies HTTP-only
- Authentification à deux facteurs (2FA) avec TOTP
- Rate limiting sur les routes d'authentification
- Vérification d'email obligatoire

### Validation
- Validation côté serveur via Form Requests
- Validation côté client pour meilleure UX
- Protection CSRF automatique via Laravel

### Autorisation
- Système de permissions à implémenter (Gates/Policies)
- Hiérarchie organisationnelle à définir

---

## Base de Données

### Modèles Principaux

1. **User** (existant)
   - Authentification et profil utilisateur
   - Champs : `id`, `name`, `username`, `email`, `password`, `password_changed_at`
   - Authentification par `username` (format : `lastname@phone.org`)

2. **Employee** (implémenté)
   - Informations sur les employés
   - Relation avec User (belongsTo)
   - Hiérarchie organisationnelle (manager/subordinates)
   - Champs : `id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `position`, `department`, `manager_id`, `salary`, `hire_date`, `status`
   - Enums : `Position` (employer, superviseur, chef_superviseur, manager), `EmployeeStatus` (active, inactive, suspended, terminated)
   - Accessors : `display_last_name` (MAJUSCULES sans accents), `normalizeLastNameForLogin()` (minuscules sans accents)

3. **Questionnaire** (à créer)
   - Templates de questionnaires
   - Versioning

4. **Question** (à créer)
   - Questions dans les questionnaires
   - Types de questions variés

5. **Response** (à créer)
   - Réponses aux questionnaires
   - Relation avec Employee et Question

6. **Report** (à créer)
   - Rapports générés
   - Templates de rapports

---

## Performance

### Optimisations Actuelles
- Eager loading pour éviter les N+1 queries
- Cache des assets via Vite
- Lazy loading des props Inertia (v2)

### Optimisations Futures
- Cache des requêtes fréquentes
- Pagination pour les grandes listes
- Lazy loading des composants Vue
- Optimisation des images

---

## Déploiement

### Environnement de Développement
- Laravel Sail (Docker)
- Hot reload avec Vite
- Pail pour les logs en temps réel

### Production (À définir)
- Serveur web (Nginx/Apache)
- Base de données (MySQL/PostgreSQL)
- Queue workers pour tâches asynchrones
- Cache (Redis/Memcached)

---

## Évolutivité

### Scalabilité Horizontale
- Stateless sessions (compatible avec load balancing)
- Queue workers pour tâches asynchrones
- Cache distribué (Redis)

### Scalabilité Verticale
- Optimisation des requêtes Eloquent
- Index de base de données appropriés
- Pagination systématique

---

*Dernière mise à jour : 25 janvier 2025*
