# [TASK-003] Page Questionnaires

**Statut :** 🟢 Terminé  
**Priorité :** Moyenne  
**Assigné à :** -  
**Date de création :** 2026-01-25  
**Date de complétion :** 2026-01-26

## Description

Créer une page complète de gestion des questionnaires (CRUD) réservée aux **managers et chefs superviseurs** permettant de :
- Lister tous les questionnaires disponibles
- Créer de nouveaux questionnaires
- Modifier des questionnaires existants
- Supprimer des questionnaires
- Visualiser les détails d'un questionnaire
- Assigner des questionnaires à des groupes cibles (employés ou superviseurs)
- Gérer des questions conditionnelles (affichage conditionnel basé sur les réponses)

**Note :** Le remplissage des questionnaires par les employés et superviseurs est géré dans [TASK-004](./TASK-004.md) (Page Rapport).

## Spécifications

### Modèle Questionnaire

**Table `questionnaires` :**

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `title` | string(255) | NOT NULL | Titre du questionnaire |
| `description` | text | NULLABLE | Description du questionnaire |
| `status` | enum | NOT NULL, DEFAULT 'published', INDEX | Statut du questionnaire |
| `target_type` | enum | NOT NULL, INDEX | Type de destinataires ciblés |
| `created_by` | bigint unsigned | FOREIGN KEY → users.id, NOT NULL | Créateur du questionnaire |
| `created_at` | timestamp | NULLABLE | Date de création |
| `updated_at` | timestamp | NULLABLE | Date de mise à jour |

**Enum `status` :**
- `published` - Publié (par défaut)
- `archived` - Archivé

**Enum `target_type` :**
- `employees` - Pour les employés
- `supervisors` - Pour les superviseurs

**Note :** Le ciblage par groupes spécifiques a été simplifié. Seuls les types "employees" et "supervisors" sont supportés actuellement.

### Modèle Question

**Table `questions` :**

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `questionnaire_id` | bigint unsigned | FOREIGN KEY → questionnaires.id, NOT NULL | Questionnaire parent |
| `type` | enum | NOT NULL | Type de question |
| `question` | text | NOT NULL | Texte de la question |
| `required` | boolean | NOT NULL, DEFAULT false | Question obligatoire |
| `order` | integer | NOT NULL, DEFAULT 0 | Ordre d'affichage |
| `options` | json | NULLABLE | Options pour les questions à choix multiples |
| `conditional_question_id` | bigint unsigned | FOREIGN KEY → questions.id, NULLABLE | Question parente pour condition |
| `conditional_value` | string(255) | NULLABLE | Valeur de la condition (ex: "faux", "non", etc.) |
| `created_at` | timestamp | NULLABLE | Date de création |
| `updated_at` | timestamp | NULLABLE | Date de mise à jour |

**Enum `type` :**
- `text` - Texte libre
- `textarea` - Zone de texte
- `radio` - Choix unique
- `checkbox` - Choix multiples
- `select` - Liste déroulante
- `number` - Nombre
- `date` - Date
- `email` - Email

## Relations Eloquent

```php
// Questionnaire.php
public function questions(): HasMany
{
    return $this->hasMany(Question::class)->orderBy('order');
}

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

// Question.php
public function questionnaire(): BelongsTo
{
    return $this->belongsTo(Questionnaire::class);
}

public function conditionalQuestion(): BelongsTo
{
    return $this->belongsTo(Question::class, 'conditional_question_id');
}

public function conditionalQuestions(): HasMany
{
    return $this->hasMany(Question::class, 'conditional_question_id');
}

```

## Routes

**Fichier :** `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::resource('questionnaires', QuestionnaireController::class);
});
```

Routes générées :
- `GET /questionnaires` - Liste des questionnaires
- `GET /questionnaires/create` - Formulaire de création
- `POST /questionnaires` - Créer un questionnaire
- `GET /questionnaires/{questionnaire}` - Détails d'un questionnaire
- `GET /questionnaires/{questionnaire}/edit` - Formulaire d'édition
- `PUT/PATCH /questionnaires/{questionnaire}` - Mettre à jour un questionnaire
- `DELETE /questionnaires/{questionnaire}` - Supprimer un questionnaire

## Validations

### Backend (Form Request)

**StoreQuestionnaireRequest :**
- **title** : `required|string|max:255`
- **description** : `nullable|string`
- **status** : `required|in:published,archived`
- **target_type** : `required|in:employees,supervisors`
- **questions** : `nullable|array`
- **questions.*.type** : `required|in:text,textarea,radio,checkbox,select,number,date,email`
- **questions.*.question** : `required|string`
- **questions.*.required** : `nullable|boolean`
- **questions.*.order** : `nullable|integer|min:0`
- **questions.*.options** : `nullable|array` (requis si type = radio, checkbox, select)
- **questions.*.conditional_question_index** : `nullable|integer|min:0` (pour création/mise à jour, résolu côté serveur)
- **questions.*.conditional_question_id** : `nullable` (résolu automatiquement côté serveur)
- **questions.*.conditional_value** : `nullable|string|max:255`

**UpdateQuestionnaireRequest :**
- Mêmes règles que StoreQuestionnaireRequest

## Interface utilisateur

### Page Liste (`resources/js/pages/questionnaires/Index.vue`)

- Tableau avec colonnes :
  - Titre
  - Description (tronquée)
  - Statut (badge coloré)
  - Créateur
  - Date de création
  - Actions (voir, modifier, supprimer)
- Filtres :
  - Recherche par titre
  - Filtre par statut
- Actions :
  - Bouton "Créer un questionnaire"
  - Pagination

### Page Création (`resources/js/pages/questionnaires/Create.vue`)

- Formulaire avec :
  - Champ titre (requis)
  - Champ description (textarea)
  - Sélection du statut (published ou archived)
  - Sélection du type de destinataires (employés ou superviseurs)
  - Section pour ajouter/modifier/supprimer des questions
  - Pour chaque question :
    - Type de question (select)
    - Texte de la question (requis)
    - Case à cocher "Obligatoire"
    - Options (si type = radio, checkbox, select)
      - Textarea avec retours à la ligne fonctionnels
      - Stockage séparé du texte brut pour préserver l'affichage
    - **Question conditionnelle** (optionnel, disponible pour tous les types de questions) :
      - Sélectionner une question parente (seulement select, checkbox ou radio)
      - Sélectionner la valeur conditionnelle via Select basé sur les options de la question parente
      - Si la condition est remplie, cette question s'affichera
    - Bouton pour réorganiser (drag & drop ou flèches)
    - Bouton supprimer
  - Boutons : "Enregistrer" et "Annuler"

### Page Édition (`resources/js/pages/questionnaires/Edit.vue`)

- Même structure que la page de création
- Pré-remplir avec les données existantes

### Page Détails (`resources/js/pages/questionnaires/Show.vue`)

- Affichage en lecture seule :
  - Titre
  - Description
  - Statut
  - Type de destinataires
  - Créateur et date de création
  - Liste des questions avec leurs types, options et conditions
- Actions :
  - Bouton "Modifier" (si autorisé)
  - Bouton "Supprimer" (avec confirmation, si autorisé)
  - Bouton "Retour à la liste"

## Fichiers à créer/modifier

1. **Migrations**
   - `database/migrations/YYYY_MM_DD_HHMMSS_create_questionnaires_table.php`
   - `database/migrations/YYYY_MM_DD_HHMMSS_create_questions_table.php`

2. **Modèles**
   - `app/Models/Questionnaire.php`
   - `app/Models/Question.php`
   - Enum `QuestionnaireStatus` (PHP 8.1+)
   - Enum `QuestionType` (PHP 8.1+)

3. **Factories**
   - `database/factories/QuestionnaireFactory.php`
   - `database/factories/QuestionFactory.php`

4. **Form Requests**
   - `app/Http/Requests/Questionnaire/StoreQuestionnaireRequest.php`
   - `app/Http/Requests/Questionnaire/UpdateQuestionnaireRequest.php`

5. **Contrôleur**
   - `app/Http/Controllers/QuestionnaireController.php`

6. **Policy**
   - `app/Policies/QuestionnairePolicy.php`
   - **Règles de permissions :**
     - `create()` : Seuls les managers (`Position::manager`) et chefs superviseurs (`Position::chef_superviseur`) peuvent créer
     - `update()` : Seuls les managers et chefs superviseurs peuvent modifier
     - `delete()` : Seuls les managers et chefs superviseurs peuvent supprimer
     - `viewAny()` : Tous les utilisateurs authentifiés peuvent voir la liste
     - `view()` : Tous les utilisateurs authentifiés peuvent voir les détails

7. **Pages Vue (Inertia)**
   - `resources/js/pages/questionnaires/Index.vue`
   - `resources/js/pages/questionnaires/Create.vue`
   - `resources/js/pages/questionnaires/Edit.vue`
   - `resources/js/pages/questionnaires/Show.vue`

8. **Composants Vue (optionnels)**
   - `resources/js/components/QuestionForm.vue` (composant réutilisable pour une question)
   - `resources/js/components/QuestionList.vue` (liste des questions avec drag & drop)

9. **Routes**
   - Ajouter les routes dans `routes/web.php`

10. **Tests**
    - `tests/Feature/Questionnaire/QuestionnaireTest.php` (Pest)
    - Tests CRUD complets
    - Tests de validation
    - Tests de relations
    - Tests de permissions (Policy)

## Notes techniques

### Permissions et autorisations

- **Création/Modification/Suppression** : Réservées aux utilisateurs avec `Position::manager` ou `Position::chef_superviseur`
- Vérifier la position de l'utilisateur via la relation `Employee` → `User`
- Utiliser la Policy `QuestionnairePolicy` pour gérer toutes les autorisations
- Masquer les boutons d'action dans l'interface si l'utilisateur n'a pas les permissions

### Statut des questionnaires

- **Supprimer le statut "draft"** : Seuls `published` et `archived` sont utilisés
- Par défaut, un nouveau questionnaire est créé avec le statut `published`
- Les questionnaires archivés ne sont plus accessibles pour remplissage mais restent visibles en lecture

### Ciblage des questionnaires

- **target_type** : Détermine qui peut voir/remplir le questionnaire
  - `employees` : Tous les employés
  - `supervisors` : Tous les superviseurs
- Filtrer l'affichage des questionnaires selon le `target_type` et la position de l'utilisateur
- **Note :** Le ciblage par groupes spécifiques a été simplifié pour cette version. Seuls "employees" et "supervisors" sont supportés.

### Questions conditionnelles

- **Fonctionnement** :
  - **Tous les types de questions** peuvent être conditionnels (text, textarea, number, date, email, etc.)
  - La **question parente** doit être de type `select`, `checkbox` ou `radio` (pour avoir des options)
  - Une question peut avoir une `conditional_question_id` (référence à une autre question)
  - La `conditional_value` est sélectionnée via un Select basé sur les options de la question parente
  - Si la réponse à la question parente correspond à `conditional_value`, la question conditionnelle s'affiche
  - Sinon, la question conditionnelle est masquée/validée automatiquement

- **Résolution des IDs** :
  - Lors de la création/mise à jour, utiliser `conditional_question_index` (indice dans le tableau)
  - Le backend résout automatiquement les indices en IDs après création des questions
  - Évite les violations de contrainte de clé étrangère lors de la mise à jour

- **Exemple** :
  ```
  Question 1: "Avez-vous des problèmes ?" (select: "Oui" / "Non")
  Question 2: "Décrivez le problème" (type: textarea, conditional_question_id = 1, conditional_value = "Oui")
  → Si réponse Q1 = "Oui" → Q2 s'affiche
  → Si réponse Q1 = "Non" → Q2 est masquée/validée
  ```

- **Implémentation frontend** :
  - Interface de sélection de question parente (seulement select/checkbox/radio disponibles)
  - Select pour la valeur conditionnelle basé sur les options de la question parente
  - Utiliser `v-if` ou `v-show` pour afficher/masquer les questions conditionnelles lors du remplissage
  - Écouter les changements sur les questions parentes
  - Valider automatiquement les questions conditionnelles masquées

- **Gestion des options** :
  - Textarea avec support des retours à la ligne (`white-space: pre-wrap`)
  - Stockage séparé du texte brut pour préserver l'affichage des retours à la ligne
  - Conversion automatique en tableau lors de la soumission


### Autres notes

- Utiliser les Enums PHP 8.1+ pour `status`, `target_type` et `type`
- Le champ `options` dans `questions` est de type JSON pour stocker les options des questions à choix multiples
- Textarea des options avec support des retours à la ligne (white-space: pre-wrap)
- Implémenter un système de réorganisation des questions (flèches haut/bas)
- Gérer la suppression en cascade : si un questionnaire est supprimé, ses questions doivent être supprimées
- Utiliser des transactions pour garantir la cohérence lors de la création/modification (questionnaire + questions)
- Résoudre les IDs conditionnels après création des questions pour éviter les violations de contrainte
- Le champ `created_by` doit être automatiquement rempli avec l'utilisateur authentifié
- Pour les questions de type `radio`, `checkbox`, `select`, le champ `options` doit contenir un tableau d'options
- Utiliser Wayfinder pour générer les types TypeScript des routes

## Critères d'acceptation

### Base de données et modèles
- [ ] Migrations créées et testées (questionnaires, questions)
- [ ] Modèles `Questionnaire` et `Question` créés avec toutes les relations
- [ ] Enums `QuestionnaireStatus`, `QuestionnaireTargetType` et `QuestionType` créés
- [ ] Relations conditionnelles entre questions implémentées

### Permissions
- [ ] Policy `QuestionnairePolicy` créée avec règles strictes
- [ ] Seuls managers et chefs superviseurs peuvent créer/modifier/supprimer
- [ ] Tests de permissions complets (tous les rôles testés)
- [ ] Interface masque les actions non autorisées

### Ciblage
- [x] Champ `target_type` implémenté (employees, supervisors)
- [x] Filtrage des questionnaires selon le `target_type` et la position de l'utilisateur

### Questions conditionnelles
- [x] Champs `conditional_question_id` et `conditional_value` ajoutés
- [x] Questions conditionnelles disponibles pour tous les types de questions
- [x] Interface de sélection de question parente (seulement select/checkbox/radio)
- [x] Valeur conditionnelle via Select basé sur les options de la question parente
- [x] Résolution correcte des IDs conditionnels lors de création/mise à jour
- [x] Gestion des contraintes de clé étrangère
- [ ] Logique d'affichage conditionnel implémentée en frontend lors du remplissage
- [ ] Validation automatique des questions conditionnelles masquées
- [ ] Tests des conditions avec différents types de questions


### Interface utilisateur
- [x] Page Index avec liste, filtres et pagination
- [x] Page Create avec formulaire complet (titre, description, statut, target_type, questions)
- [x] Page Edit avec pré-remplissage des données
- [x] Page Show en lecture seule avec toutes les informations
- [x] Système de réorganisation des questions (flèches haut/bas)
- [x] Gestion des options pour les questions à choix multiples avec retours à la ligne
- [x] Interface pour définir les questions conditionnelles (tous types, Select pour valeur)

### Validation et tests
- [x] Form Requests avec validation complète (incluant conditions et target_type)
- [ ] Tests unitaires et fonctionnels passants
- [ ] Tests de permissions (Policy) pour tous les rôles
- [ ] Tests des questions conditionnelles
- [ ] Tests du ciblage (employees, supervisors)

### Code quality
- [x] Code formaté avec Pint
- [x] Documentation mise à jour (ARCHITECTURE.md, PROGRESS.md, CHANGELOG.md)
- [x] Types TypeScript générés avec Wayfinder
- [ ] Factories et Seeders fonctionnels

## Dépendances

- Modèle `User` (existant)
- Modèle `Employee` avec enum `Position` (existant)
- Système d'authentification (existant)

## Liens

- Issue/PR : -
- Documentation : `docs/ARCHITECTURE.md`
- Fichier de tâche : `docs/tasks/TASK-003.md`
- Tâche liée : [TASK-004](./TASK-004.md) (Page Rapport - Remplissage des questionnaires)

---

## Notes de mise à jour

### 2026-01-26
- ✅ Simplification du ciblage : suppression du type "groups", conservation uniquement de "employees" et "supervisors"
- ✅ Questions conditionnelles disponibles pour tous les types de questions (pas seulement select/checkbox/radio)
- ✅ Valeur conditionnelle remplacée par un Select basé sur les options de la question parente
- ✅ Correction du textarea des options avec support des retours à la ligne (white-space: pre-wrap)
- ✅ Correction de la violation de contrainte de clé étrangère lors de la mise à jour (utilisation de conditional_question_index)
- ✅ Résolution correcte des IDs conditionnels après création des questions

---

*Dernière mise à jour : 26 janvier 2026*

