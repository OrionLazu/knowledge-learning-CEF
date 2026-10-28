# Knowledge Learning - plateforme e-learning

Plateforme e-learning et e-commerce de la société **Knowledge**, réalisée avec **Symfony 7.4** et **MySQL 8** dans le cadre du devoir CEF « Créer la plateforme e-learning Knowledge Learning ».

Les clients achètent un **cursus complet** ou une **leçon**, suivent les leçons (fiche + vidéo), les valident et obtiennent la **certification Knowledge Learning** d'un thème quand toutes ses leçons sont validées.

- 🌐 **Application en ligne :** https://knowledge-learning.alwaysdata.net (achats tests Stripe, voir [Version en ligne](#version-en-ligne))
- 🗄️ **Modèle physique de données :** [docs/mpd-knowledge-learning.png](docs/mpd-knowledge-learning.png)
- 📚 **Documentation du code (phpDocumentor) :** [docs/api/index.html](docs/api/index.html)

## Fonctionnalités

| Route | Page | Accès |
|---|---|---|
| `/` | Catalogue : thèmes, cursus et prix | Tous |
| `/course/{id}` | Détail d'un cursus, achat du cursus ou d'une leçon | Tous (achat : compte activé) |
| `/register` | Inscription, envoi de l'e-mail d'activation | Visiteur |
| `/login` | Connexion par e-mail et mot de passe | Visiteur |
| `/lesson/{id}` | Leçon (titre, vidéo, texte) et bouton « Valider la leçon » | Client ayant acheté la leçon |
| `/certifications` | Récapitulatif des certifications obtenues | Connecté |
| `/purchase/history` | Historique des achats | Connecté |
| `/admin` | Back-office : utilisateurs, thèmes, cursus, leçons, commandes, accès, certifications | Administrateur |

- Un compte créé reçoit automatiquement le rôle **client** (`ROLE_CLIENT`) et reste **inactif** jusqu'au clic sur le lien reçu par e-mail. Un compte inactif peut se connecter mais **ne peut pas acheter**.
- Acheter une leçon ou un cursus donne le **droit d'accès** aux pages des leçons concernées (table `lesson_access`).
- Valider toutes les leçons d'un cursus **valide le cursus** automatiquement ; valider toutes les leçons d'un thème délivre la **certification**.
- Paiement par **Stripe Checkout en mode test** (bac à sable) : aucune carte réelle ne peut être débitée.

## Version en ligne

L'application est hébergée sur **alwaysdata** (PHP 8.4, MariaDB 11.8) : **https://knowledge-learning.alwaysdata.net**

- Les e-mails d'activation sont réellement envoyés (pensez à regarder dans les **courriers indésirables**).
- Les paiements passent par Stripe en **environnement de test** : carte `4242 4242 4242 4242`, date future, CVC quelconque. Aucune carte réelle n'est débitée.
- Les comptes de démonstration ci-dessous existent aussi en ligne.

## Prérequis

- PHP **8.2** ou plus avec les extensions `pdo_mysql`, `intl`, `mbstring`, `ctype`, `iconv`, `zip` (XAMPP convient en activant `intl` et `zip` dans le `php.ini`)
- [Composer](https://getcomposer.org/)
- MySQL **8**
- Un compte [Stripe](https://dashboard.stripe.com/register) (mode test, gratuit)
- [Mailpit](https://mailpit.axllent.org/) pour recevoir les e-mails en local (ou tout autre serveur SMTP de test)

## Installation

```bash
git clone https://github.com/OrionLazu/knowledge-learning-CEF.git
cd knowledge-learning-CEF
composer install
```

### 1. Base de données et compte MySQL dédié

La base n'est accessible que par un compte MySQL créé spécialement pour l'application (`knowledge_admin`), qui n'a de droits que sur les bases du projet. Remplacer `CHANGE_ME` par un mot de passe dans [database/create-database-user.sql](database/create-database-user.sql), puis l'exécuter une fois avec le compte root :

```bash
mysql -u root -p < database/create-database-user.sql
```

### 2. Fichiers d'environnement locaux (jamais versionnés)

`.env.local` :

```dotenv
DATABASE_URL="mysql://knowledge_admin:MOT_DE_PASSE@127.0.0.1:3306/knowledge_learning?serverVersion=8.0.32&charset=utf8mb4"
# Clé secrète de TEST Stripe : https://dashboard.stripe.com/test/apikeys (seule une clé sk_test_ est acceptée)
STRIPE_SECRET_KEY=sk_test_...
```

`.env.test.local` (base des tests `knowledge_learning_test`, créée par le script SQL) :

```dotenv
DATABASE_URL="mysql://knowledge_admin:MOT_DE_PASSE@127.0.0.1:3306/knowledge_learning?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Tables et données de démonstration

```bash
composer db:init
```

Cette commande crée les tables (migration Doctrine) et charge le catalogue de la version bêta (4 thèmes, 6 cursus, 12 leçons) ainsi que deux comptes.

## Lancement

1. Démarrer Mailpit (SMTP sur le port 1025, boîte de réception sur http://localhost:8025) :

   ```bash
   mailpit
   ```

2. Démarrer l'application sur **http://127.0.0.1:8001** :

   ```bash
   composer start
   ```

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@knowledge-learning.test` | `Admin!Knowledge2026` |
| Client (déjà activé) | `client@knowledge-learning.test` | `Client!Knowledge2026` |

Ces comptes n'existent que dans les données de démonstration ([src/DataFixtures/AppFixtures.php](src/DataFixtures/AppFixtures.php)).

## Parcours de test

1. **Créer un compte** sur `/register`. Le mot de passe doit contenir 12 à 64 caractères, avec au moins une minuscule, une majuscule, un chiffre et un caractère spécial.
2. **Activer le compte** : ouvrir l'e-mail « Activez votre compte » dans Mailpit et cliquer sur le lien. Le compte est activé et connecté.
3. **Acheter** : ouvrir un cursus, cliquer sur « Acheter la leçon » ou « Acheter le cursus complet ». Sur la page Stripe (« Environnement de test »), choisir **Carte** et payer avec la carte de test **`4242 4242 4242 4242`**, une date future (ex. `12/30`) et n'importe quel CVC (ex. `123`).
4. **Suivre une leçon** : après le paiement, ouvrir la leçon, puis cliquer sur **Valider la leçon**.
5. **Valider un cursus** : valider toutes ses leçons. **Certification** : valider toutes les leçons d'un thème (par exemple « Jardinage » ou « Informatique », qui n'ont qu'un cursus), puis ouvrir **Mes certifications**.

Autres cartes de test : `4000 0000 0000 9995` (paiement refusé), `4000 0025 0000 3155` (authentification 3D Secure).

## Tests

```bash
composer test
```

76 tests PHPUnit (unitaires et fonctionnels) sur une base MySQL dédiée, remise à zéro au lancement ; chaque test s'exécute dans une transaction annulée à la fin (DAMA DoctrineTestBundle). Stripe est simulé : aucun appel réseau.

| Fichier | Ce qui est testé |
|---|---|
| `tests/Controller/RegistrationAndLoginTest.php` | Inscription, mot de passe trop faible, e-mail déjà utilisé, CSRF, activation par e-mail, lien falsifié, connexion |
| `tests/Controller/PurchaseAndLessonTest.php` | Achat d'une leçon et d'un cursus, CSRF, compte non activé, accès aux leçons, validation jusqu'à la certification, back-office |
| `tests/Service/RegistrationServiceTest.php` | Rôle client automatique, mot de passe haché, e-mail d'activation |
| `tests/Service/PurchaseServiceTest.php` | Achat complet, droits d'accès, paiement non confirmé, commande d'un autre client, idempotence |
| `tests/Service/ProgressServiceTest.php` | Validation des leçons, des cursus, certification |
| `tests/Repository/*` | Composants d'accès aux données : tests fonctionnels et de sécurité (injection SQL, isolement des données entre clients, contraintes d'unicité) |
| `tests/Validator/StrongPasswordTest.php` | Règles de format des mots de passe |
| `tests/Security/LessonVoterTest.php` | Règle d'accès aux pages des leçons |
| `tests/EventListener/AuditListenerTest.php` | Remplissage de `created_at`, `updated_at`, `created_by`, `updated_by` |

## Documentation du code

Le code est commenté en anglais (PHPDoc). La documentation HTML est générée avec **phpDocumentor** dans [docs/api](docs/api/index.html) :

```bash
curl -L -o tools/phpDocumentor.phar https://github.com/phpDocumentor/phpDocumentor/releases/download/v3.8.1/phpDocumentor.phar
composer docs
```

## Architecture

Architecture **MVC** en 3 couches, orientée objet :

```text
src/
├── Controller/        Contrôleurs (couche présentation) ; Admin/ : back-office EasyAdmin
├── Entity/            Entités Doctrine (modèle) ; Contract/ et Trait/ : audit et produits achetables
├── Repository/        Composants d'accès aux données (une classe par table)
├── Service/           Logique métier : inscription, achat, paiement Stripe, progression
├── Security/          Activation par e-mail, Voter d'accès aux leçons
├── EventListener/     Remplissage automatique des colonnes d'audit
├── Validator/         Règles de format des mots de passe
├── Form/              Formulaire d'inscription
├── Enum/ Model/ Exception/ Twig/
templates/             Vues Twig (couche présentation)
migrations/            Schéma de la base (Doctrine Migrations)
database/              Script de création du compte MySQL dédié
tests/                 Tests PHPUnit
docs/                  Modèle physique de données, documentation du code
```

## Sécurité

- **Mots de passe hachés** (algorithme `auto` de Symfony : bcrypt/argon2id), jamais stockés en clair, et **formatés** avant d'être acceptés (`App\Validator\StrongPassword`).
- **Protection CSRF** par le composant Symfony sur tous les formulaires et actions : inscription, connexion, déconnexion, achat, validation d'une leçon, renvoi de l'e-mail d'activation, back-office.
- **Rôles** `ROLE_CLIENT` (attribué automatiquement à l'inscription) et `ROLE_ADMIN` ; `/admin` protégé par `access_control` et `#[IsGranted('ROLE_ADMIN')]`.
- Accès aux leçons contrôlé par un **Voter** (`LESSON_VIEW`) : seul un client ayant acheté la leçon (ou un administrateur) peut l'ouvrir.
- **Lien d'activation signé** (HMAC) et expirant au bout d'une heure ; un lien falsifié est refusé.
- Connexion limitée à **5 tentatives par minute** ; renvoi de l'e-mail d'activation limité à 3 par quart d'heure.
- Le paiement est **revérifié auprès de l'API Stripe** au retour : l'URL de retour seule ne débloque rien. Seule une clé Stripe de test est acceptée.
- Requêtes Doctrine **paramétrées** (aucune concaténation SQL) ; chaque requête sur des données personnelles est filtrée sur l'utilisateur connecté.
- Base de données accessible uniquement par le **compte MySQL dédié** `knowledge_admin` ; les secrets restent dans `.env.local`, ignoré par Git.

## Technologies

Symfony 7.4 LTS · Doctrine ORM 3 · MySQL 8 · Twig · Bootstrap 5.3 · EasyAdmin 5 · Stripe PHP SDK · SymfonyCasts VerifyEmail · PHPUnit 11 · DAMA DoctrineTestBundle · phpDocumentor 3
