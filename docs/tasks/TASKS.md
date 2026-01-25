# Tâches RapportFlow

Liste des tâches à réaliser pour le développement de l'application.

---

## [TASK-001] Créer le modèle Employee

**Statut :** 🔴 À faire  
**Priorité :** Haute  
**Assigné à :** -  
**Date de création :** 2025-01-25

### Description

Créer le modèle `Employee` avec toutes les fonctionnalités nécessaires pour la gestion des employés dans l'application.

### Spécifications

#### Champs de la table `employees`

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `user_id` | bigint unsigned | FOREIGN KEY → users.id, NULLABLE | Lien vers le compte utilisateur |
| `employee_id` | string(50) | UNIQUE, INDEX | Identifiant unique de l'employé (ex: EMP001) |
| `first_name` | string(255) | NOT NULL | Prénom |
| `last_name` | string(255) | NOT NULL | Nom de famille (stocké tel quel, normalisé pour affichage) |
| `email` | string(255) | UNIQUE, NULLABLE | Email de l'employé |
| `phone` | string(8) | UNIQUE, INDEX, NULLABLE | Téléphone (format Malien - 8 chiffres) |
| `position` | enum | NOT NULL, INDEX | Position dans l'organisation |
| `department` | string(255) | NULLABLE, INDEX | Département |
| `manager_id` | bigint unsigned | FOREIGN KEY → employees.id, NULLABLE, INDEX | Manager direct (auto-référence) |
| `salary` | decimal(10,2) | NULLABLE | Salaire |
| `hire_date` | date | NULLABLE | Date d'embauche |
| `status` | enum | NOT NULL, DEFAULT 'active', INDEX | Statut de l'employé |
| `created_at` | timestamp | NULLABLE | Date de création |
| `updated_at` | timestamp | NULLABLE | Date de mise à jour |

**Note sur `last_name` :**
- Le champ stocke la valeur originale (ex: "Traoré")
- Pour l'affichage : utiliser un accessor/mutator pour normaliser en MAJUSCULES sans accents (ex: "TRAORE")
- Pour le login : normaliser en minuscules sans accents (ex: "traore")

#### Enum `position`

Valeurs possibles :
- `employer` - Employé
- `superviseur` - Superviseur
- `chef_superviseur` - Chef superviseur
- `manager` - Manager

#### Enum `status`

Valeurs possibles :
- `active` - Actif
- `inactive` - Inactif
- `suspended` - Suspendu
- `terminated` - Terminé

#### Indexes à créer

- `idx_employee_id` sur `employee_id` (UNIQUE)
- `idx_phone` sur `phone` (UNIQUE)
- `idx_department` sur `department`
- `idx_status` sur `status`
- `idx_position` sur `position`
- `idx_manager_id` sur `manager_id`

### Validations

#### Backend (Form Request)

- **employee_id** : `required|string|max:50|unique:employees,employee_id`
- **first_name** : `required|string|max:255`
- **last_name** : `required|string|max:255`
- **email** : `nullable|email|max:255|unique:employees,email`
- **phone** : `nullable|string|size:8|regex:/^[0-9]{8}$/|unique:employees,phone` (format Malien - 8 chiffres uniquement)
- **position** : `required|in:employer,superviseur,chef_superviseur,manager`
- **department** : `nullable|string|max:255`
- **manager_id** : `nullable|exists:employees,id`
- **salary** : `nullable|numeric|min:0|max:99999999.99`
- **hire_date** : `nullable|date|before_or_equal:today`
- **status** : `required|in:active,inactive,suspended,terminated`
- **user_id** : `nullable|exists:users,id|unique:employees,user_id`

#### Frontend (TypeScript)

- Validation du téléphone : exactement 8 chiffres
- Format d'affichage du téléphone (optionnel) : `XX XX XX XX`

### Relations Eloquent

```php
// Employee.php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function manager(): BelongsTo
{
    return $this->belongsTo(Employee::class, 'manager_id');
}

public function subordinates(): HasMany
{
    return $this->hasMany(Employee::class, 'manager_id');
}
```

### Fichiers à créer/modifier

1. **Migration**
   - `database/migrations/YYYY_MM_DD_HHMMSS_create_employees_table.php`

2. **Modèle**
   - `app/Models/Employee.php`
   - Enum `Position` (PHP 8.1+)
   - Enum `EmployeeStatus` (PHP 8.1+)
   - Relations Eloquent
   - Casts appropriés

3. **Factory**
   - `database/factories/EmployeeFactory.php`
   - États personnalisés si nécessaire

4. **Seeder**
   - Mettre à jour `database/seeders/DatabaseSeeder.php` ou créer `EmployeeSeeder.php`

5. **Form Request**
   - `app/Http/Requests/Employee/StoreEmployeeRequest.php`
   - `app/Http/Requests/Employee/UpdateEmployeeRequest.php`

6. **Tests**
   - `tests/Feature/Employee/EmployeeTest.php` (Pest)
   - Tests de validation
   - Tests de relations
   - Tests de contraintes (unique, foreign keys)

### Notes techniques

- Utiliser les Enums PHP 8.1+ pour `position` et `status`
- Le champ `employee_id` doit être unique et peut être généré automatiquement (ex: EMP001, EMP002)
- Le téléphone doit être validé strictement : exactement 8 chiffres (format Malien)
- La relation `manager_id` permet de créer une hiérarchie organisationnelle
- Le champ `user_id` est nullable car un employé peut exister sans compte utilisateur (cas d'employés non-connectés)
- Prévoir un système de génération automatique d'`employee_id` si nécessaire
- **Normalisation du `last_name`** :
  - Le champ stocke la valeur originale (ex: "Traoré")
  - Pour l'affichage : créer un accessor `getDisplayLastNameAttribute()` qui retourne `strtoupper(Str::ascii($this->last_name))` (ex: "TRAORE")
  - Pour le login (username) : utiliser `strtolower(Str::ascii($last_name))` (ex: "traore")

### Critères d'acceptation

- [ ] Migration créée et testée
- [ ] Modèle `Employee` créé avec toutes les relations
- [ ] Enums `Position` et `EmployeeStatus` créés
- [ ] Form Requests avec validation complète
- [ ] Factory et Seeder fonctionnels
- [ ] Tests unitaires et fonctionnels passants
- [ ] Validation du téléphone (8 chiffres) fonctionnelle
- [ ] Indexes créés et vérifiés
- [ ] Relations Eloquent testées
- [ ] Accessor pour affichage du `last_name` (MAJUSCULES sans accents)
- [ ] Tests de normalisation du `last_name`
- [ ] Code formaté avec Pint
- [ ] Documentation mise à jour (ARCHITECTURE.md)

### Dépendances

- Modèle `User` (existant)

### Liens

- Issue/PR : -
- Documentation : `docs/ARCHITECTURE.md`

---

## [TASK-002] Système d'authentification personnalisé avec username et mot de passe par défaut

**Statut :** 🔴 À faire  
**Priorité :** Haute  
**Assigné à :** -  
**Date de création :** 2025-01-25  
**Dépend de :** TASK-001 (modèle Employee)

### Description

Implémenter un système d'authentification personnalisé où :
- L'identifiant de connexion est généré automatiquement : `last_name@phone.org`
- Le mot de passe par défaut est généré automatiquement : `ML+phone` (ex: `ML12345678`)
- L'email n'est **PAS** utilisé pour l'authentification (seulement pour stockage)
- À la première connexion, l'utilisateur doit choisir de garder le mot de passe par défaut ou le changer

### Spécifications

#### Normalisation du last_name

Le `last_name` doit être normalisé différemment selon l'usage :

**Pour l'affichage (dans Employee) :**
- Convertir en **MAJUSCULES**
- Supprimer les accents et caractères spéciaux
- Exemple : `"Traoré"` → `"TRAORE"`

**Pour le login (username) :**
- Convertir en **minuscules**
- Supprimer les accents et caractères spéciaux
- Exemple : `"Traoré"` → `"traore"`

**Méthode de normalisation :**
```php
// Utiliser Str::ascii() pour supprimer accents
// Puis strtoupper() pour affichage ou strtolower() pour login
$normalized = Str::ascii($last_name); // "Traoré" → "Traore"
```

#### Génération automatique des identifiants

**Format du username :**
```
normalized_last_name = strtolower(Str::ascii(last_name))
username = normalized_last_name . "@" . phone . ".org"
```

**Exemples :**
- `last_name = "Doe"`, `phone = "12345678"` → `username = "doe@12345678.org"`
- `last_name = "Traoré"`, `phone = "87654321"` → `username = "traore@87654321.org"`
- `last_name = "N'Diaye"`, `phone = "11223344"` → `username = "ndiaye@11223344.org"`

**Format du mot de passe par défaut :**
```
password = "ML" . phone
```

**Exemples :**
- `phone = "12345678"` → `password = "ML12345678"`
- `phone = "87654321"` → `password = "ML87654321"`

#### Modifications de la table `users`

| Champ | Modification | Description |
|-------|-------------|-------------|
| `username` | **AJOUTER** (string, UNIQUE, NOT NULL) | Identifiant de connexion généré automatiquement |
| `email` | **MODIFIER** (nullable) | Plus utilisé pour l'auth, seulement pour stockage |
| `password_changed_at` | **AJOUTER** (timestamp, nullable) | Date de changement de mot de passe (null = première connexion) |

#### Flux d'inscription

```
1. Formulaire d'inscription (Employee)
   - Champs : first_name, last_name, phone, position, department, etc.
   - PAS de champ password dans le formulaire
   
2. Génération automatique :
   - normalized_last_name = strtolower(Str::ascii(last_name))
   - username = normalized_last_name . "@" . phone . ".org"
   - password = "ML" . phone
   
3. Création User :
   - username: "traore@87654321.org" (si last_name = "Traoré", phone = "87654321")
   - password: "ML87654321" (hashé automatiquement)
   - email: null (ou optionnel)
   - password_changed_at: null
   
4. Création Employee :
   - last_name: "Traoré" (stocké tel quel, original)
   - Affichage: "TRAORE" (via accessor/mutator)
   
5. Redirection vers dashboard
```

#### Flux de première connexion

```
1. Utilisateur se connecte avec :
   - Login: "traore@87654321.org" (normalisé, minuscules, sans accents)
   - Password: "ML87654321"
   
2. Vérification : password_changed_at === null ?
   
3. Si OUI → Redirection vers /first-login
   - Option 1: [ ] Garder le mot de passe par défaut (ML12345678)
   - Option 2: [ ] Choisir un nouveau mot de passe
   
4. Après validation :
   - Si "Garder" → password_changed_at = now()
   - Si "Changer" → Nouveau password hashé + password_changed_at = now()
   
5. Accès au dashboard
```

### Modifications nécessaires

#### 1. Migration User

**Fichier :** `database/migrations/YYYY_MM_DD_HHMMSS_add_username_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('username')->unique()->after('id');
    $table->timestamp('password_changed_at')->nullable()->after('password');
    $table->string('email')->nullable()->change(); // Rendre email nullable
});
```

#### 2. Configuration Fortify

**Fichier :** `config/fortify.php`

```php
'username' => 'username', // Au lieu de 'email'
'lowercase_usernames' => true, // Garder true pour normaliser
```

#### 3. Modèle User

**Fichier :** `app/Models/User.php`

- Ajouter `'username'` dans `$fillable`
- Ajouter `'password_changed_at' => 'datetime'` dans `casts()`
- Ajouter méthode helper : `hasChangedPassword(): bool`

#### 4. Action CreateNewUser

**Fichier :** `app/Actions/Fortify/CreateNewUser.php`

- Modifier pour générer automatiquement `username` et `password` à partir des données Employee
- Ne plus demander de mot de passe dans le formulaire
- Créer User ET Employee dans une transaction
- Normaliser le `last_name` pour le username (minuscules, sans accents)

```php
// Logique de génération
use Illuminate\Support\Str;

$normalizedLastName = strtolower(Str::ascii($input['last_name'])); // "Traoré" → "traore"
$username = $normalizedLastName . '@' . $input['phone'] . '.org';
$password = 'ML' . $input['phone'];
```

#### 5. Middleware "Force Password Change"

**Fichier :** `app/Http/Middleware/RequirePasswordChange.php`

- Vérifier si `password_changed_at === null`
- Rediriger vers `/first-login` si nécessaire
- Exclure la route `/first-login` et les routes de changement de mot de passe

#### 6. Page "First Login"

**Fichier :** `resources/js/pages/auth/FirstLogin.vue`

- Interface avec deux options :
  - [ ] Garder le mot de passe par défaut
  - [ ] Choisir un nouveau mot de passe (avec formulaire)
- Validation et soumission

#### 7. Contrôleur First Login

**Fichier :** `app/Http/Controllers/Auth/FirstLoginController.php`

- Méthode `show()` : Afficher la page
- Méthode `update()` : Traiter le choix (garder ou changer)

#### 8. Formulaire d'inscription

**Fichier :** `resources/js/pages/auth/Register.vue`

- Retirer le champ `password` et `password_confirmation`
- Ajouter les champs Employee : `first_name`, `last_name`, `phone`, `position`, `department`, etc.
- Le formulaire ne demande plus de mot de passe

#### 9. Page de connexion

**Fichier :** `resources/js/pages/auth/Login.vue`

- Changer le label "Email" en "Username" ou "Identifiant"
- Mettre à jour les placeholders et messages d'aide

### Validations

#### Backend (CreateNewUser)

- **first_name** : `required|string|max:255`
- **last_name** : `required|string|max:255`
- **phone** : `required|string|size:8|regex:/^[0-9]{8}$/`
- **position** : `required|in:employer,superviseur,chef_superviseur,manager`
- **department** : `nullable|string|max:255`
- **email** : `nullable|email` (optionnel, pas pour l'auth)

#### Génération username

- Normaliser le `last_name` : `strtolower(Str::ascii($last_name))` pour supprimer accents et caractères spéciaux
- Vérifier l'unicité : `Rule::unique('users', 'username')`
- Gérer les collisions (ajouter un suffixe si nécessaire, ex: `traore2@87654321.org`)
- Le `last_name` original est conservé dans Employee, seule la normalisation est utilisée pour le username

### Sécurité

- Le mot de passe par défaut `ML+phone` est faible, d'où l'obligation de changement
- Forcer le changement à la première connexion
- Logger les tentatives de connexion avec mot de passe par défaut
- Optionnel : Expirer le mot de passe par défaut après X jours

### Fichiers à créer/modifier

1. **Migration**
   - `database/migrations/YYYY_MM_DD_HHMMSS_add_username_to_users_table.php`

2. **Modèle User**
   - Ajouter `username` dans `$fillable`
   - Ajouter cast `password_changed_at`
   - Méthode helper `hasChangedPassword()`

3. **Modèle Employee**
   - Accessor pour `last_name` : `getDisplayLastNameAttribute()` → MAJUSCULES sans accents
   - Méthode helper : `normalizeLastNameForLogin()` → minuscules sans accents

4. **Configuration**
   - `config/fortify.php` : Changer `username` de `'email'` à `'username'`

5. **Action**
   - `app/Actions/Fortify/CreateNewUser.php` : Génération automatique username/password

6. **Middleware**
   - `app/Http/Middleware/RequirePasswordChange.php`

7. **Contrôleur**
   - `app/Http/Controllers/Auth/FirstLoginController.php`

8. **Pages Vue**
   - `resources/js/pages/auth/FirstLogin.vue`
   - `resources/js/pages/auth/Register.vue` (modifier)
   - `resources/js/pages/auth/Login.vue` (modifier)

9. **Routes**
   - Ajouter route `/first-login` (GET, POST)
   - Ajouter middleware sur routes protégées

10. **Tests**
   - `tests/Feature/Auth/RegistrationWithEmployeeTest.php`
   - `tests/Feature/Auth/FirstLoginTest.php`
   - `tests/Feature/Auth/UsernameAuthenticationTest.php`
   - Tests de normalisation du `last_name` (accents, caractères spéciaux)

### Notes techniques

- **Transaction** : Créer User et Employee dans une transaction pour garantir la cohérence
- **Génération username** : Gérer les collisions (ex: deux "Traoré" avec même téléphone)
- **Normalisation last_name** :
  - **Pour affichage** : Accessor dans Employee pour afficher en MAJUSCULES sans accents (`strtoupper(Str::ascii($value))`)
  - **Pour login** : Normaliser en minuscules sans accents (`strtolower(Str::ascii($value))`)
  - Le champ `last_name` dans Employee stocke la valeur originale (ex: "Traoré")
- **Unicité** : Vérifier l'unicité du username avant création
- **Migration** : Pour les utilisateurs existants, générer un username à partir de leur email ou créer une migration de données
- **Helper** : Créer une méthode helper `normalizeLastNameForDisplay()` et `normalizeLastNameForLogin()` dans le modèle Employee

### Exemples concrets

**Inscription :**
```
Input:
  first_name: "Amadou"
  last_name: "Traoré"
  phone: "87654321"
  position: "employer"
  department: "IT"

Normalisation:
  last_name pour login: "traore" (minuscules, sans accents)
  last_name pour affichage: "TRAORE" (majuscules, sans accents)

Génération:
  username: "traore@87654321.org"
  password: "ML87654321"

User créé:
  username: "traore@87654321.org"
  password: "$2y$10$..." (hash de "ML87654321")
  email: null
  password_changed_at: null

Employee créé:
  last_name: "Traoré" (valeur originale stockée)
  Affichage: "TRAORE" (via accessor)
```

**Première connexion :**
```
Login: traore@87654321.org (normalisé, minuscules, sans accents)
Password: ML87654321
  ↓
Redirection: /first-login
  ↓
Choix:
  [✓] Garder le mot de passe par défaut
  [ ] Choisir un nouveau mot de passe
  ↓
password_changed_at: 2025-01-25 14:30:00
  ↓
Accès: /dashboard
```

**Affichage dans l'interface :**
```
Nom affiché: "TRAORE" (majuscules, sans accents)
Nom stocké en DB: "Traoré" (valeur originale)
```

### Critères d'acceptation

- [ ] Migration créée et testée (username, password_changed_at, email nullable)
- [ ] Configuration Fortify modifiée pour utiliser `username`
- [ ] Modèle User mis à jour avec `username` et `password_changed_at`
- [ ] `CreateNewUser` génère automatiquement username et password
- [ ] Formulaire d'inscription ne demande plus de mot de passe
- [ ] Page de connexion utilise "username" au lieu d"email"
- [ ] Middleware `RequirePasswordChange` fonctionnel
- [ ] Page "First Login" créée et fonctionnelle
- [ ] Contrôleur `FirstLoginController` implémenté
- [ ] Tests complets (inscription, première connexion, authentification)
- [ ] Gestion des collisions de username
- [ ] Normalisation du `last_name` pour affichage (MAJUSCULES sans accents)
- [ ] Normalisation du `last_name` pour login (minuscules sans accents)
- [ ] Accessor dans Employee pour l'affichage du `last_name`
- [ ] Tests de normalisation (accents, caractères spéciaux)
- [ ] Code formaté avec Pint
- [ ] Documentation mise à jour (ARCHITECTURE.md)

### Dépendances

- TASK-001 : Modèle Employee (doit être créé en premier)
- Migration `password_changed_at` (déjà créée selon les fichiers)

### Liens

- Issue/PR : -
- Documentation : `docs/ARCHITECTURE.md`
- Tâche liée : TASK-001

---

*Dernière mise à jour : 25 janvier 2025*
