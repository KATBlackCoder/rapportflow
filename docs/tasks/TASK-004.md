# [TASK-004] Page Rapport - Remplissage des Questionnaires

**Statut :** 🔴 À faire  
**Priorité :** Moyenne  
**Assigné à :** -  
**Date de création :** 2026-01-25  
**Dépend de :** TASK-003 (CRUD des questionnaires)

## Description

Créer une page "Rapport" avec une liste déroulante permettant différents accès selon le rôle de l'utilisateur :

### Options disponibles selon le rôle

**Pour les employés** (`Position::employer`) :
- **Faire un rapport** : Liste des questionnaires disponibles → remplissage
- **Regarder ses rapports** : Voir ses rapports déjà soumis
- **Corriger un rapport** : Corriger les rapports renvoyés pour correction

**Pour les superviseurs** (`Position::superviseur`) :
- **Faire un rapport** : Liste des questionnaires disponibles → remplissage
- **Regarder ses rapports** : Voir ses rapports déjà soumis
- **Corriger un rapport** : Corriger les rapports renvoyés pour correction
- **Analyser rapport** : Consulter les rapports envoyés par son groupe (avec possibilité de renvoyer pour correction)

**Pour les managers et chefs superviseurs** (`Position::manager`, `Position::chef_superviseur`) :
- **Analyser rapport** : Consulter tous les rapports envoyés (avec possibilité de renvoyer pour correction et export Excel)

### Fonctionnalités principales

- Remplir les questionnaires sous forme de tableau
- Utiliser deux modes de remplissage :
  1. **Mode manuel ligne par ligne** : Remplissage cellule par cellule
  2. **Mode copier-coller** : Coller des données au format spécial pour remplir plusieurs lignes en une fois
- Sauvegarder les réponses soumises
- Consulter et analyser les rapports soumis
- Renvoyer des rapports pour correction avec raison
- Exporter les données en Excel (managers et chefs superviseurs uniquement)

## Spécifications

### Modèle QuestionnaireResponse

**Table `questionnaire_responses` :**

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `questionnaire_id` | bigint unsigned | FOREIGN KEY → questionnaires.id, NOT NULL | Questionnaire rempli |
| `question_id` | bigint unsigned | FOREIGN KEY → questions.id, NOT NULL | Question répondue |
| `respondent_id` | bigint unsigned | FOREIGN KEY → users.id, NOT NULL | Utilisateur qui a rempli |
| `row_identifier` | string(255) | NULLABLE, INDEX | Identifiant de la ligne (ex: numéro téléphone, date, etc.) |
| `response` | json | NOT NULL | Réponse stockée (peut être texte, nombre, tableau pour checkbox, etc.) |
| `status` | enum | NOT NULL, DEFAULT 'submitted', INDEX | Statut de la réponse |
| `submitted_at` | timestamp | NULLABLE | Date de soumission |
| `reviewed_by` | bigint unsigned | FOREIGN KEY → users.id, NULLABLE | Utilisateur qui a revu le rapport |
| `reviewed_at` | timestamp | NULLABLE | Date de révision |
| `correction_reason` | text | NULLABLE | Raison du renvoi pour correction |
| `created_at` | timestamp | NULLABLE | Date de création |
| `updated_at` | timestamp | NULLABLE | Date de mise à jour |

**Enum `status` :**
- `submitted` - Soumis (par défaut lors de la création)
- `returned_for_correction` - Renvoyé pour correction

**Note :** Le champ `row_identifier` permet d'identifier une ligne dans le tableau (ex: numéro de téléphone d'un client, date de vente, etc.). Cela permet de regrouper les réponses d'une même ligne.

### Relations Eloquent

```php
// QuestionnaireResponse.php
public function questionnaire(): BelongsTo
{
    return $this->belongsTo(Questionnaire::class);
}

public function question(): BelongsTo
{
    return $this->belongsTo(Question::class);
}

public function respondent(): BelongsTo
{
    return $this->belongsTo(User::class, 'respondent_id');
}

public function reviewer(): BelongsTo
{
    return $this->belongsTo(User::class, 'reviewed_by');
}

// Questionnaire.php
public function responses(): HasMany
{
    return $this->hasMany(QuestionnaireResponse::class);
}

// Question.php
public function responses(): HasMany
{
    return $this->hasMany(QuestionnaireResponse::class);
}
```

## Routes

**Fichier :** `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    // Page principale avec liste déroulante
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    
    // Faire un rapport
    Route::get('/rapports/create', [RapportController::class, 'create'])->name('rapports.create');
    Route::get('/rapports/create/{questionnaire}', [RapportController::class, 'show'])->name('rapports.show');
    Route::post('/rapports', [RapportController::class, 'store'])->name('rapports.store');
    
    // Regarder ses rapports (lecture seule)
    Route::get('/rapports/my-reports', [RapportController::class, 'myReports'])->name('rapports.my-reports');
    Route::get('/rapports/my-reports/{response}', [RapportController::class, 'showMyReport'])->name('rapports.show-my-report');
    
    // Corriger un rapport (seule section modifiable)
    Route::get('/rapports/corrections', [RapportController::class, 'corrections'])->name('rapports.corrections');
    Route::get('/rapports/corrections/{response}', [RapportController::class, 'showCorrection'])->name('rapports.show-correction');
    Route::put('/rapports/corrections/{response}', [RapportController::class, 'updateCorrection'])->name('rapports.update-correction');
    
    // Analyser rapport
    Route::get('/rapports/analysis', [RapportController::class, 'analysis'])->name('rapports.analysis');
    Route::get('/rapports/analysis/{response}', [RapportController::class, 'showAnalysis'])->name('rapports.show-analysis');
    Route::post('/rapports/analysis/{response}/return', [RapportController::class, 'returnForCorrection'])->name('rapports.return-for-correction');
    Route::get('/rapports/analysis/export', [RapportController::class, 'export'])->name('rapports.export');
});
```

## Validations

### Backend (Form Request)

**StoreRapportRequest :**
- **questionnaire_id** : `required|exists:questionnaires,id`
- **responses** : `required|array`
- **responses.*.question_id** : `required|exists:questions,id`
- **responses.*.row_identifier** : `nullable|string|max:255`
- **responses.*.response** : `required` (type dépend du type de question)
- Validation des réponses selon le type de question :
  - `text`, `textarea`, `email` : string
  - `number` : numeric
  - `date` : date
  - `select`, `radio` : doit correspondre à une option valide
  - `checkbox`, `selectmulti` : array de valeurs valides

**ReturnForCorrectionRequest :**
- **correction_reason** : `required|string|max:1000`
- **response_ids** : `required|array|exists:questionnaire_responses,id`

## Interface utilisateur

### Navigation

- **Lien dans le navbar** : La page "Rapport" sera accessible via un lien dans la barre de navigation principale
- Le lien sera visible pour tous les utilisateurs authentifiés
- Le lien pointe vers `/rapports` (route `rapports.index`)

### Page Principale (`resources/js/pages/rapports/Index.vue`)

**Liste déroulante avec options selon le rôle :**

- **Liste déroulante** (Select/Dropdown) avec les options disponibles selon le rôle :
  - **Pour employés** :
    1. Faire un rapport
    2. Regarder ses rapports
    3. Corriger un rapport
  - **Pour superviseurs** :
    1. Faire un rapport
    2. Regarder ses rapports
    3. Corriger un rapport
    4. Analyser rapport
  - **Pour managers et chefs superviseurs** :
    1. Analyser rapport

- **Affichage conditionnel** : Seules les options autorisées selon la position de l'utilisateur sont affichées

### 1. Faire un rapport (`resources/js/pages/rapports/Create.vue`)

- Liste des questionnaires disponibles pour l'utilisateur connecté
- Filtrage selon :
  - `target_type` (employees, supervisors)
  - Position de l'utilisateur (employé ou superviseur)
- Affichage :
  - Titre du questionnaire
  - Description
  - Statut (published uniquement)
  - Date de création
  - Bouton "Remplir" pour accéder au formulaire de remplissage

### 2. Regarder ses rapports (`resources/js/pages/rapports/MyReports.vue`)

**⚠️ Lecture seule - Aucune modification ou suppression possible**

- Liste des rapports soumis par l'utilisateur connecté
- Affichage :
  - Titre du questionnaire
  - Date de soumission
  - Statut (submitted, returned_for_correction)
  - Nombre de lignes remplies
  - Actions :
    - **Voir les détails** (affichage en lecture seule uniquement)
    - Si `returned_for_correction` : Lien vers "Corriger un rapport" (redirection vers la section correction)
- Filtres :
  - Par questionnaire
  - Par statut
  - Par date de soumission

**Page de détails** (`resources/js/pages/rapports/ShowMyReport.vue`) :
- Affichage en **lecture seule** du rapport
- Tableau avec toutes les réponses (non éditable)
- Aucun bouton de modification ou suppression
- Si le statut est `returned_for_correction` : Bouton "Aller corriger" qui redirige vers la section "Corriger un rapport"

### 3. Corriger un rapport (`resources/js/pages/rapports/Corrections.vue`)

**⚠️ Seule section où la modification est possible - Uniquement pour les rapports renvoyés pour correction**

- Liste des rapports renvoyés pour correction (`status = returned_for_correction`)
- Affichage :
  - Titre du questionnaire
  - Date de soumission initiale
  - Date de renvoi pour correction
  - Raison de la correction (`correction_reason`)
  - Bouton "Corriger" pour accéder au formulaire de correction

**Page de correction** (`resources/js/pages/rapports/ShowCorrection.vue`) :
- **Seule interface où la modification est autorisée**
- Même interface que la page de remplissage (tableau éditable)
- Pré-remplissage avec les données existantes (modifiables)
- Affichage de la raison de correction (raison du renvoi)
- Possibilité de modifier toutes les réponses
- Bouton "Soumettre la correction" pour renvoyer (statut redevient `submitted`)
- **Note importante** : La modification n'est possible QUE dans cette section, pas dans "Regarder ses rapports"

### 4. Analyser rapport (`resources/js/pages/rapports/Analysis.vue`)

**Pour superviseurs :**
- Liste des rapports soumis par les membres de son groupe
- Filtrage :
  - Par questionnaire
  - Par membre du groupe
  - Par statut
  - Par date de soumission
- Affichage :
  - Titre du questionnaire
  - Nom du répondant (membre du groupe)
  - Date de soumission
  - Statut
  - Nombre de lignes remplies
  - Actions :
    - Voir les détails
    - Renvoyer pour correction (si nécessaire)

**Pour managers et chefs superviseurs :**
- Liste de **tous** les rapports soumis dans l'application
- Filtrage avancé :
  - Par questionnaire
  - Par répondant
  - Par superviseur (groupe)
  - Par statut
  - Par date de soumission
  - Par période
- Affichage :
  - Titre du questionnaire
  - Nom du répondant
  - Superviseur (si applicable)
  - Date de soumission
  - Statut
  - Nombre de lignes remplies
  - Actions :
    - Voir les détails
    - Renvoyer pour correction (si nécessaire)
    - **Export Excel** (bouton avec options) :
      - Exporter tout
      - Exporter avec filtres appliqués

**Page d'analyse détaillée** (`resources/js/pages/rapports/ShowAnalysis.vue`) :
- Affichage du rapport en lecture seule
- Tableau avec toutes les réponses
- Possibilité de sélectionner des lignes spécifiques
- Section "Renvoi pour correction" :
  - Sélection des lignes à corriger (ou tout le rapport)
  - Champ texte pour la raison de correction (obligatoire)
  - Bouton "Renvoyer pour correction"
- Pour managers/chefs superviseurs : Bouton "Exporter en Excel" avec options de filtrage

### Page Remplissage (`resources/js/pages/rapports/Show.vue` et `ShowCorrection.vue`)

**Vue tableau pour remplir les questionnaires :**

- **Structure :**
  - **Colonnes** : Chaque colonne représente une question du questionnaire
  - **Lignes** : Chaque ligne représente une entrée à remplir (ex: un client, une date, etc.)
  - **Cellules** : Contiennent les réponses aux questions

- **Section d'instructions** (affichée au-dessus du tableau) :
  - Titre du questionnaire
  - Description
  - **Guide de format copier-coller** avec :
    - Liste des colonnes disponibles (noms des questions)
    - Format d'exemple avec les noms de colonnes
    - **Valeurs possibles** pour chaque question avec leurs numéros :
      - Questions `select` ou `radio` : `1=Valeur1, 2=Valeur2, ...`
      - Questions `checkbox` ou `selectmulti` : `(1)=Valeur1, (2)=Valeur2, ...`
    - Exemple complet de ligne formatée
  - Bouton "Copier le format d'exemple" pour faciliter la saisie

- **Zone de collage** :
  - Zone de texte pour coller les données au format spécial
  - Bouton "Appliquer le collage" pour traiter les données collées
  - Affichage des erreurs de format si nécessaire

- **Tableau** :
  - En-tête fixe avec les noms des questions (colonnes)
  - Première colonne pour identifier les lignes (ex: numéro téléphone, date, etc.)
  - Cellules éditables selon le type de question
  - Indicateurs visuels pour les questions obligatoires
  - Badges pour les questions conditionnelles
  - Ajout/suppression de lignes dynamiquement

- **Fonctionnalités** :
  - **Mode manuel** : Remplissage cellule par cellule
  - **Mode copier-coller** : Coller des données au format spécial
  - Gestion des questions conditionnelles (affichage/masquage selon les réponses)
  - Validation en temps réel
  - **Soumission** : Bouton "Soumettre le rapport" (status = submitted par défaut)
  - Bouton "Retour à la liste"

#### Format de copier-coller

**Syntaxe générale :**
```
valeur1,valeur2,valeur3;valeur4,valeur5,valeur6;
```
- Les valeurs sont séparées par des **virgules** (`,`)
- Les lignes sont séparées par des **point-virgules** (`;`)
- La dernière ligne peut se terminer avec ou sans `;`

**Types de valeurs selon le type de question :**

1. **`text`, `textarea`, `number`, `date`, `email`** :
   - Valeur directe : `"Mon texte"`, `123`, `2026-01-25`
   - Les chaînes de texte peuvent être entre guillemets si elles contiennent des virgules

2. **`select`, `radio`** (choix unique) :
   - Numéro de l'option : `1`, `2`, `3`, etc.
   - Correspond au numéro de l'option dans la liste (index basé sur 1)

3. **`checkbox`, `selectmulti`** (choix multiples) :
   - Numéros entre parenthèses séparés par des virgules : `(1,2,3)`
   - Exemple : `(1,2)` pour sélectionner les options 1 et 2

**Exemple complet :**

Pour un questionnaire "Suivi Ventes Produits Agricoles" avec les questions :
- `numero_telephone` (text)
- `produit_vendu` (select: 1=Arachides, 2=Mil, 3=Sorgho, 4=Manioc)
- `quantite_vendue` (number)
- `prix_unitaire` (number)
- `moyens_paiement` (checkbox: (1)=Espèces, (2)=Mobile Money, (3)=Crédit)
- `avis_client` (select: 1=Très satisfait, 2=Satisfait, 3=Peu satisfait)

**Format d'exemple affiché :**
```
Format pour "Suivi Ventes Produits Agricoles"

Colonnes: numero_telephone, produit_vendu, quantite_vendue, prix_unitaire, moyens_paiement, avis_client

Format d'exemple: numero_telephone,produit_vendu,quantite_vendue,prix_unitaire,moyens_paiement,avis_client

Valeurs possibles:
- produit_vendu: 1=Arachides, 2=Mil, 3=Sorgho, 4=Manioc
- moyens_paiement: (1)=Espèces, (2)=Mobile Money, (3)=Crédit
- avis_client: 1=Très satisfait, 2=Satisfait, 3=Peu satisfait

Exemple complet: 77000001,1,25,15000,(1,2),2;
```

**Données collées (2 lignes) :**
```
77000001,1,25,15000,(1,2),2;77000002,2,50,12000,(2),1;
```

**Traitement du collage :**
1. Parser la chaîne collée en lignes (séparées par `;`)
2. Pour chaque ligne, parser les valeurs (séparées par `,`)
3. Valider chaque valeur selon le type de question
4. Convertir les numéros en valeurs réelles :
   - `select`/`radio` : `1` → première option de la liste
   - `checkbox`/`selectmulti` : `(1,2)` → options 1 et 2 sélectionnées
5. Remplir les cellules correspondantes dans le tableau
6. Afficher les erreurs de validation si nécessaire

## Fichiers à créer/modifier

1. **Migration**
   - `database/migrations/YYYY_MM_DD_HHMMSS_create_questionnaire_responses_table.php`

2. **Modèle**
   - `app/Models/QuestionnaireResponse.php`
   - Enum `ResponseStatus` (PHP 8.1+)

3. **Form Requests**
   - `app/Http/Requests/Rapport/StoreRapportRequest.php`
   - `app/Http/Requests/Rapport/ReturnForCorrectionRequest.php`

4. **Contrôleur**
   - `app/Http/Controllers/RapportController.php`

5. **Pages Vue (Inertia)**
   - `resources/js/pages/rapports/Index.vue` (page principale avec liste déroulante)
   - `resources/js/pages/rapports/Create.vue` (liste des questionnaires pour remplissage)
   - `resources/js/pages/rapports/Show.vue` (page de remplissage avec tableau)
   - `resources/js/pages/rapports/MyReports.vue` (mes rapports soumis - liste)
   - `resources/js/pages/rapports/ShowMyReport.vue` (détails d'un rapport - lecture seule)
   - `resources/js/pages/rapports/Corrections.vue` (liste des rapports à corriger)
   - `resources/js/pages/rapports/ShowCorrection.vue` (page de correction - seule interface modifiable)
   - `resources/js/pages/rapports/Analysis.vue` (liste des rapports à analyser)
   - `resources/js/pages/rapports/ShowAnalysis.vue` (page d'analyse détaillée)

6. **Composants Vue**
   - `resources/js/components/RapportPasteHelper.vue` (composant pour afficher les instructions de format et gérer le collage)
   - `resources/js/components/RapportTable.vue` (composant tableau réutilisable pour afficher et éditer les réponses)
   - `resources/js/components/RapportRow.vue` (composant pour une ligne du tableau)
   - `resources/js/components/RapportActionDropdown.vue` (liste déroulante avec options selon le rôle)

7. **Navigation**
   - Ajouter le lien "Rapport" dans le composant navbar/sidebar
   - Le lien doit être visible pour tous les utilisateurs authentifiés

8. **Routes**
   - Ajouter les routes dans `routes/web.php`

9. **Tests**
   - `tests/Feature/Rapport/RapportTest.php` (Pest)
   - Tests de remplissage manuel
   - Tests de remplissage par copier-coller
   - Tests de validation des réponses
   - Tests de questions conditionnelles
   - Tests de filtrage selon target_type et position

## Notes techniques

### Filtrage des questionnaires

- **Pour les employés** (`target_type = employees`) :
  - Afficher uniquement si l'utilisateur a `Position::employer`
  
- **Pour les superviseurs** (`target_type = supervisors`) :
  - Afficher uniquement si l'utilisateur a `Position::superviseur` ou supérieur

### Questions conditionnelles

- **Fonctionnement** :
  - Une question peut avoir une `conditional_question_id` (référence à une autre question)
  - Si la question parente est de type `select` (ou `radio`), définir `conditional_value` (ex: "faux", "non")
  - Si la réponse à la question parente correspond à `conditional_value`, la question conditionnelle s'affiche
  - Sinon, la question conditionnelle est masquée/validée automatiquement

- **Implémentation frontend** :
  - Utiliser `v-if` ou `v-show` pour afficher/masquer les questions conditionnelles
  - Écouter les changements sur les questions parentes
  - Valider automatiquement les questions conditionnelles masquées
  - Dans le mode copier-coller, gérer les questions conditionnelles après le parsing

### Fonctionnalité copier-coller

- **Parser de format** : Fonction JavaScript pour parser la chaîne collée
  - Séparer les lignes par `;`
  - Séparer les valeurs par `,`
  - Gérer les valeurs entre guillemets pour les textes avec virgules
  - Parser les valeurs entre parenthèses pour les checkbox/selectmulti
  
- **Validation** :
  - Vérifier que le nombre de valeurs correspond au nombre de colonnes
  - Valider chaque valeur selon le type de question
  - Vérifier que les numéros d'options existent
  - Afficher les erreurs ligne par ligne si nécessaire
  
- **Conversion** :
  - Convertir les numéros en valeurs réelles pour select/radio
  - Convertir les numéros entre parenthèses en tableaux pour checkbox/selectmulti
  - Préserver les valeurs textuelles telles quelles
  
- **Génération d'instructions** :
  - Générer automatiquement le guide de format basé sur les questions du questionnaire
  - Afficher les colonnes dans l'ordre
  - Lister toutes les options avec leurs numéros pour select/radio/checkbox
  - Fournir un exemple complet avec des valeurs réalistes

### Gestion des statuts et workflow

- **Workflow des rapports** :
  1. `submitted` : L'utilisateur soumet le rapport (statut par défaut lors de la création)
  2. `returned_for_correction` : Un superviseur/manager renvoie pour correction avec raison
  3. Après correction, le statut redevient `submitted`

- **Renvoi pour correction** :
  - Un superviseur/manager peut sélectionner des lignes spécifiques ou tout le rapport
  - Doit fournir une raison obligatoire (`correction_reason`)
  - Le statut passe à `returned_for_correction`
  - Le rapport apparaît dans "Corriger un rapport" pour le répondant
  - Après correction et nouvelle soumission, le statut redevient `submitted`

### Restrictions de modification

- **"Regarder ses rapports" (MyReports)** :
  - **Lecture seule uniquement** : Aucune modification ou suppression possible
  - Affichage des données soumises en mode consultation
  - Si le statut est `returned_for_correction`, afficher un lien vers "Corriger un rapport"
  - **Backend** : Aucune route PUT/PATCH/DELETE pour cette section

- **"Corriger un rapport" (Corrections)** :
  - **Seule section où la modification est autorisée**
  - Uniquement pour les rapports avec `status = returned_for_correction`
  - **Backend** : Vérifier que le statut est `returned_for_correction` avant d'autoriser la modification
  - Après modification et soumission, le statut redevient `submitted`

- **Sécurité** :
  - Vérifier côté backend que seuls les rapports `returned_for_correction` peuvent être modifiés
  - Vérifier que l'utilisateur est le propriétaire du rapport (`respondent_id`)
  - Empêcher toute modification via l'API pour les rapports en statut `submitted`

- **Filtrage par groupe (superviseurs)** :
  - Un superviseur ne voit que les rapports des membres de son groupe
  - Vérifier via la relation `Employee` → `manager_id` ou groupes associés

### Export Excel

- **Disponible uniquement pour managers et chefs superviseurs**
- **Options d'export** :
  - Exporter tout : Tous les rapports sans filtre
  - Exporter avec filtres : Utilise les filtres appliqués sur la page d'analyse
- **Format Excel** :
  - Colonnes : Questionnaire, Répondant, Date de soumission, Questions (colonnes dynamiques)
  - Lignes : Chaque ligne du tableau = une ligne Excel
  - Regroupement par `row_identifier` si applicable
- **Utiliser** : `Maatwebsite\Excel` ou `PhpSpreadsheet` pour générer le fichier Excel

### Performance

- Charger les questionnaires et leurs questions en une seule requête avec eager loading
- Utiliser la pagination pour les grandes listes de questionnaires et rapports
- Optimiser le parsing pour les grandes quantités de données collées
- Utiliser des transactions pour garantir la cohérence lors de la sauvegarde (plusieurs réponses par ligne)
- Indexer les colonnes `status`, `respondent_id`, `reviewed_by` pour optimiser les requêtes

## Critères d'acceptation

### Base de données et modèles
- [ ] Migration `questionnaire_responses` créée avec tous les champs (status, reviewed_by, correction_reason, etc.)
- [ ] Modèle `QuestionnaireResponse` créé avec toutes les relations
- [ ] Enum `ResponseStatus` créé
- [ ] Relations avec `Questionnaire`, `Question`, `User` (respondent et reviewer) implémentées

### Permissions et accès
- [ ] Liste déroulante avec options selon le rôle :
  - [ ] Employés : Faire un rapport, Regarder ses rapports, Corriger un rapport
  - [ ] Superviseurs : Toutes les options + Analyser rapport (limité à son groupe)
  - [ ] Managers/Chefs superviseurs : Analyser rapport uniquement (tous les rapports)
- [ ] Filtrage des questionnaires selon `target_type` et position de l'utilisateur
- [ ] Seuls les questionnaires `published` sont affichés
- [ ] Filtrage par groupe pour superviseurs (uniquement leur groupe)

### Interface utilisateur
- [ ] Lien "Rapport" ajouté dans le navbar/sidebar
- [ ] Page Index avec liste déroulante fonctionnelle
- [ ] Page Create avec liste des questionnaires disponibles
- [ ] Page Show avec tableau de remplissage
- [ ] **Page MyReports** avec liste des rapports soumis (lecture seule)
- [ ] **Page ShowMyReport** avec affichage en lecture seule (aucune modification possible)
- [ ] Page Corrections avec liste des rapports renvoyés pour correction
- [ ] **Page ShowCorrection** avec formulaire de correction pré-rempli (SEULE interface modifiable)
- [ ] Page Analysis avec liste des rapports à analyser (filtres selon rôle)
- [ ] Page ShowAnalysis avec vue détaillée et possibilité de renvoyer pour correction
- [ ] Section d'instructions avec guide de format copier-coller
- [ ] Zone de collage fonctionnelle
- [ ] Tableau avec colonnes dynamiques (questions)
- [ ] Lignes dynamiques (ajout/suppression) - uniquement dans Show et ShowCorrection
- [ ] Cellules éditables selon type de question - uniquement dans Show et ShowCorrection
- [ ] Cellules en lecture seule dans ShowMyReport et ShowAnalysis
- [ ] Indicateurs visuels pour questions obligatoires

### Sauvegarde et workflow
- [ ] Soumission de rapport (status = submitted par défaut)
- [ ] Renvoi pour correction avec raison (status = returned_for_correction)
- [ ] Affichage de la raison de correction dans la page de correction
- [ ] Workflow complet : submitted → returned_for_correction → submitted (après correction)
- [ ] **Restriction de modification** :
  - [ ] "Regarder ses rapports" : Lecture seule uniquement (aucune modification/suppression)
  - [ ] "Corriger un rapport" : Seule section où la modification est autorisée
  - [ ] Vérification backend que seuls les rapports `returned_for_correction` peuvent être modifiés

### Analyse et export
- [ ] Page d'analyse pour superviseurs (limité à leur groupe)
- [ ] Page d'analyse pour managers/chefs superviseurs (tous les rapports)
- [ ] Filtres avancés fonctionnels
- [ ] Sélection de lignes spécifiques pour renvoi
- [ ] Renvoi pour correction avec raison obligatoire
- [ ] Export Excel pour managers/chefs superviseurs :
  - [ ] Export tout
  - [ ] Export avec filtres appliqués
  - [ ] Format Excel correct avec toutes les colonnes

### Fonctionnalité copier-coller
- [ ] Parser du format (séparateurs `,` et `;`)
- [ ] Conversion des numéros en valeurs réelles (select/radio)
- [ ] Conversion des numéros entre parenthèses (checkbox/selectmulti)
- [ ] Validation des données collées
- [ ] Remplissage automatique des lignes du tableau
- [ ] Gestion des erreurs de format
- [ ] Génération automatique des instructions

### Questions conditionnelles
- [ ] Affichage/masquage des questions conditionnelles
- [ ] Gestion dans le mode manuel
- [ ] Gestion dans le mode copier-coller
- [ ] Validation automatique des questions masquées

### Validation et tests
- [ ] Form Request avec validation complète
- [ ] Tests de remplissage manuel
- [ ] Tests de remplissage par copier-coller
- [ ] Tests de validation des réponses
- [ ] Tests de soumission de rapport (status = submitted)
- [ ] Tests de renvoi pour correction (status = returned_for_correction)
- [ ] Tests de correction et nouvelle soumission (retour à submitted)
- [ ] Tests de questions conditionnelles
- [ ] Tests de filtrage selon rôle
- [ ] Tests de permissions (accès selon position)
- [ ] Tests d'export Excel

### Code quality
- [ ] Code formaté avec Pint
- [ ] Documentation mise à jour (ARCHITECTURE.md)
- [ ] Types TypeScript générés avec Wayfinder

## Dépendances

- TASK-003 : CRUD des questionnaires (doit être complété en premier)
- Modèle `User` (existant)
- Modèle `Employee` avec enum `Position` (existant)
- Système d'authentification (existant)
- Package Excel : `Maatwebsite\Excel` ou `PhpSpreadsheet` (à installer)

## Liens

- Issue/PR : -
- Documentation : `docs/ARCHITECTURE.md`
- Fichier de tâche : `docs/tasks/TASK-004.md`
- Tâche liée : TASK-003

---

---

## Notes de mise à jour

### 2026-01-26
- ✅ Ajout d'un système de liste déroulante avec options selon le rôle
- ✅ Ajout de la sauvegarde des réponses (modèle QuestionnaireResponse)
- ✅ Simplification du système de statuts : seulement `submitted` et `returned_for_correction` (pas de draft ni approved)
- ✅ Ajout de la fonctionnalité "Regarder ses rapports"
- ✅ Ajout de la fonctionnalité "Corriger un rapport"
- ✅ Ajout de la fonctionnalité "Analyser rapport" avec renvoi pour correction
- ✅ Ajout de l'export Excel pour managers et chefs superviseurs
- ✅ Gestion des permissions selon le rôle (employé, superviseur, manager/chef superviseur)

---

*Dernière mise à jour : 26 janvier 2026*
