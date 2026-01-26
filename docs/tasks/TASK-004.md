# [TASK-004] Page Rapport - Remplissage des Questionnaires

**Statut :** 🔴 À faire  
**Priorité :** Moyenne  
**Assigné à :** -  
**Date de création :** 2026-01-25  
**Dépend de :** TASK-003 (CRUD des questionnaires)

## Description

Créer une page "Rapport" permettant aux employés et superviseurs de :
- Voir les questionnaires qui leur sont assignés (selon leur position et les groupes)
- Remplir les questionnaires sous forme de tableau
- Utiliser deux modes de remplissage :
  1. **Mode manuel ligne par ligne** : Remplissage cellule par cellule
  2. **Mode copier-coller** : Coller des données au format spécial pour remplir plusieurs lignes en une fois

## Routes

**Fichier :** `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/{questionnaire}', [RapportController::class, 'show'])->name('rapports.show');
});
```

## Interface utilisateur

### Navigation

- **Lien dans le navbar** : La page "Rapport" sera accessible via un lien dans la barre de navigation principale
- Le lien sera visible pour tous les utilisateurs authentifiés (employés et superviseurs)
- Le lien pointe vers `/rapports` (route `rapports.index`)

### Page Liste Rapports (`resources/js/pages/rapports/Index.vue`)

- Liste des questionnaires disponibles pour l'utilisateur connecté
- Filtrage selon :
  - `target_type` (employees, supervisors, groups)
  - Position de l'utilisateur (employé ou superviseur)
  - Groupes auxquels l'utilisateur appartient
- Affichage :
  - Titre du questionnaire
  - Description
  - Statut (published uniquement)
  - Date de création
  - Bouton "Remplir" pour accéder au formulaire

### Page Remplissage (`resources/js/pages/rapports/Show.vue`)

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

1. **Contrôleur**
   - `app/Http/Controllers/RapportController.php`

2. **Pages Vue (Inertia)**
   - `resources/js/pages/rapports/Index.vue` (liste des questionnaires disponibles)
   - `resources/js/pages/rapports/Show.vue` (page de remplissage avec tableau)

3. **Composants Vue**
   - `resources/js/components/RapportPasteHelper.vue` (composant pour afficher les instructions de format et gérer le collage)
   - `resources/js/components/RapportTable.vue` (composant tableau réutilisable pour afficher et éditer les réponses)
   - `resources/js/components/RapportRow.vue` (composant pour une ligne du tableau)

4. **Navigation**
   - Ajouter le lien "Rapport" dans le composant navbar/sidebar
   - Le lien doit être visible pour tous les utilisateurs authentifiés

5. **Routes**
   - Ajouter les routes dans `routes/web.php`

6. **Tests**
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
  
- **Pour les groupes** (`target_type = groups`) :
  - Afficher uniquement si l'utilisateur appartient à un des groupes associés
  - Vérifier via la relation `User` → `Employee` → `Group`

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

### Performance

- Charger les questionnaires et leurs questions en une seule requête avec eager loading
- Utiliser la pagination pour les grandes listes de questionnaires
- Optimiser le parsing pour les grandes quantités de données collées

## Critères d'acceptation

### Filtrage et permissions
- [ ] Filtrage des questionnaires selon `target_type` et position de l'utilisateur
- [ ] Filtrage par groupes si `target_type = groups`
- [ ] Seuls les questionnaires `published` sont affichés
- [ ] Tests de filtrage pour tous les cas (employees, supervisors, groups)

### Interface utilisateur
- [ ] Lien "Rapport" ajouté dans le navbar/sidebar
- [ ] Page Index avec liste des questionnaires disponibles
- [ ] Page Show avec tableau de remplissage
- [ ] Section d'instructions avec guide de format copier-coller
- [ ] Zone de collage fonctionnelle
- [ ] Tableau avec colonnes dynamiques (questions)
- [ ] Lignes dynamiques (ajout/suppression)
- [ ] Cellules éditables selon type de question
- [ ] Indicateurs visuels pour questions obligatoires

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
- [ ] Tests de remplissage manuel
- [ ] Tests de remplissage par copier-coller
- [ ] Tests de validation des réponses
- [ ] Tests de questions conditionnelles
- [ ] Tests de filtrage

### Code quality
- [ ] Code formaté avec Pint
- [ ] Documentation mise à jour (ARCHITECTURE.md)
- [ ] Types TypeScript générés avec Wayfinder

## Dépendances

- TASK-003 : CRUD des questionnaires (doit être complété en premier)
- Modèle `User` (existant)
- Modèle `Employee` avec enum `Position` (existant)
- Système d'authentification (existant)
- Table `groups` (si utilisée pour le ciblage)

## Liens

- Issue/PR : -
- Documentation : `docs/ARCHITECTURE.md`
- Fichier de tâche : `docs/tasks/TASK-004.md`
- Tâche liée : TASK-003

---

*Dernière mise à jour : 25 janvier 2026*
