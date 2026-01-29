# [TASK-005] Dashboard adapté aux rôles

**Statut :** 🔴 À faire  
**Priorité :** Moyenne  
**Assigné à :** -  
**Date de création :** 2026-01-27  
**Dépend de :** TASK-004 (Page Rapport)

## Description

Remplacer le Dashboard actuel (placeholders génériques) par une page d’accueil adaptée au rôle de l’utilisateur (`employer`, `superviseur`, `chef_superviseur`, `manager`). Chaque rôle voit des statistiques et des actions pertinentes pour son activité.

### Contexte actuel

- **Dashboard** : 3 cartes et une zone en placeholder, pas de contenu métier.
- **Données partagées** (HandleInertiaRequests) : `auth.user` avec `employee` (`id`, `position`, `department`).
- **Rôles** : `employer`, `superviseur`, `chef_superviseur`, `manager` (enum `Position`).

## Contenu par rôle

### 1. Employé (`Position::Employer`)

| Bloc | Contenu |
|------|--------|
| **Rapports à faire** | Nombre de questionnaires disponibles (ciblant les employés) non encore remplis récemment ; lien « Faire un rapport » |
| **Mes rapports** | Dernier rapport soumis (questionnaire, date) ; lien « Voir mes rapports » |
| **À corriger** | Nombre (et liste courte) de rapports en « renvoyé pour correction » ; lien vers la page de correction |
| **Actions rapides** | Bouton « Faire un rapport » → `/rapports/create` |

Objectif : voir d’un coup d’œil ce qu’il doit faire (remplir, corriger) et accéder à ses rapports.

### 2. Superviseur (`Position::Superviseur`)

| Bloc | Contenu |
|------|--------|
| **Mes rapports** | Idem employé (dernier rapport, lien « Mes rapports ») |
| **À corriger** | Idem employé (rapports renvoyés pour correction) |
| **Mon équipe** | Nombre d’employés supervisés (via `supervisedEmployees`) |
| **Rapports de l’équipe** | Nombre de rapports soumis par l’équipe sur la période (ex. semaine / mois) ; lien « Analyser les rapports » |
| **Actions rapides** | « Faire un rapport », « Corriger un rapport », « Analyser les rapports » |

Objectif : voir son activité personnelle + l’activité de son équipe et les tâches de revue.

### 3. Chef superviseur (`Position::ChefSuperviseur`)

| Bloc | Contenu |
|------|--------|
| **Vue équipe / département** | Indicateurs agrégés (nombre de superviseurs, d’employés, par département si pertinent) |
| **Questionnaires** | Nombre de questionnaires publiés ; lien vers la liste des questionnaires |
| **Rapports** | Nombre total de rapports soumis (ou par questionnaire / période) ; lien « Analyser les rapports » |
| **Rapports à corriger** | Nombre de rapports renvoyés pour correction (éventuellement par équipe) ; lien vers l’analyse avec filtre |
| **Export** | Lien ou rappel vers l’export Excel des rapports |
| **Employés** | Lien rapide vers la page Employees |

Objectif : pilotage de l’activité questionnaires/rapports et accès rapide à la gestion des personnes.

### 4. Manager (`Position::Manager`)

| Bloc | Contenu |
|------|--------|
| **Vue globale** | Même logique que chef superviseur, à l’échelle de toute l’organisation |
| **KPIs** | Nombre d’employés, questionnaires actifs, rapports soumis sur la période, rapports en attente de correction |
| **Activité récente** | Derniers rapports soumis (qui, quel questionnaire, date) ou liste des derniers événements |
| **Questionnaires** | Liste des questionnaires récents ou « à la une » ; lien vers la gestion des questionnaires |
| **Employés** | Lien vers la liste / gestion des employés |
| **Rapports** | Lien « Analyser tous les rapports » + option export Excel |

Objectif : vision synthétique de l’activité et des points d’attention (corrections, volume de rapports).

## Spécifications techniques

### Backend

1. **Route dashboard**  
   Remplacer le closure actuel dans `routes/web.php` par un contrôleur dédié (ex. `DashboardController@index`).

2. **DashboardController**  
   - Récupérer `auth()->user()` et `$user->employee` (position, department).  
   - Selon la position, exécuter les requêtes nécessaires (counts, derniers rapports, équipe, etc.).  
   - Passer à Inertia des props dédiées au dashboard.

3. **Props Inertia suggérées**  
   - `stats` : objet avec `myReportsCount`, `pendingCorrectionsCount`, `teamReportsCount`, `questionnairesCount`, `employeesCount`, etc., selon le rôle.  
   - `recentReports` : liste limitée (5–10) avec questionnaire, date, auteur si pertinent.  
   - `pendingCorrections` : liste ou count des réponses en `returned_for_correction` pour l’utilisateur ou son équipe.  
   - `canAccessQuestionnaires`, `canAccessEmployees`, `canExportReports` : booléens dérivés de la position.

4. **Performance**  
   - Utiliser des requêtes agrégées (`count()`, `with()`, sous-requêtes) pour éviter le N+1.  
   - Ne charger que les champs nécessaires pour les listes (ex. `select('id', 'title', 'submitted_at')`).

### Frontend (Dashboard.vue)

1. **Données**  
   - Utiliser `usePage().props.auth.user.employee.position` (et éventuellement `department`).  
   - Consommer les props passées par le contrôleur (`stats`, `recentReports`, `pendingCorrections`, etc.).

2. **Affichage conditionnel**  
   - Afficher des sections différentes selon le rôle :  
     `v-if="position === 'employer'"`,  
     `v-if="['superviseur','chef_superviseur','manager'].includes(position)"`, etc.

3. **Composants**  
   - Réutiliser les composants UI existants (Card, Badge, Button) et les routes déjà définies (rapports, questionnaires, employees) pour les liens.

4. **Mise en page**  
   - Grille de cartes pour les statistiques.  
   - Liste ou tableau pour « récent » / « à corriger ».

### Récapitulatif par rôle

| Rôle | Focus principal sur le Dashboard |
|------|----------------------------------|
| **Employer** | Mes rapports, à corriger, action « Faire un rapport » |
| **Superviseur** | Idem + effectif et activité de l’équipe, lien « Analyser » |
| **Chef superviseur** | Vue équipe/département, questionnaires, rapports, corrections, lien Employés |
| **Manager** | Vue globale, KPIs, activité récente, liens Questionnaires, Employés, Analyse + Export |

## Fichiers à créer ou modifier

### Backend

- `app/Http/Controllers/DashboardController.php` (créer)
- `routes/web.php` (modifier la route `dashboard`)

### Frontend

- `resources/js/pages/Dashboard.vue` (refonte selon les blocs ci-dessus)

### Documentation

- `docs/ARCHITECTURE.md` (mettre à jour la section Dashboard si nécessaire)

## Critères d’acceptation

- [ ] Route dashboard pointe vers un contrôleur dédié.
- [ ] DashboardController calcule les stats et listes selon la position (employer, superviseur, chef_superviseur, manager).
- [ ] Pas de N+1 : requêtes agrégées ou eager loading approprié.
- [ ] Dashboard.vue affiche des sections conditionnelles par rôle.
- [ ] Employé : blocs « Rapports à faire », « Mes rapports », « À corriger », action « Faire un rapport ».
- [ ] Superviseur : blocs employé + « Mon équipe », « Rapports de l’équipe », lien « Analyser ».
- [ ] Chef superviseur : indicateurs équipe/département, questionnaires, rapports, corrections, lien Employés.
- [ ] Manager : KPIs globaux, activité récente, liens Questionnaires, Employés, Analyse, Export.
- [ ] Liens et boutons pointent vers les routes existantes (rapports, questionnaires, employees).
- [ ] Code formaté (Pint / ESLint) et documentation mise à jour.

## Dépendances

- TASK-004 (Page Rapport) : routes et données des rapports
- Modèles : User, Employee, Questionnaire, QuestionnaireResponse
- HandleInertiaRequests : partage de `auth.user.employee` (id, position, department)

## Liens

- Fichier détaillé : `docs/tasks/TASK-005.md`
- Documentation : `docs/ARCHITECTURE.md`
- Tâche liée : TASK-004 (Page Rapport)
