# LUXE CAR AUTOMOBIL RESERVATION

Plateforme premium de réservation automobile — Symfony 7.4 LTS.

Parcours principal :
**Catalogue → Fiche véhicule → Réservation → Retrait/Livraison → Frais 20% → Virement bancaire → Preuve → Confirmation administrateur → Bon de réservation par e-mail.**

- Zéro `localStorage` pour les données métier — tout est stocké en MySQL.
- Paiement uniquement par virement bancaire (jamais de carte).
- IBAN de remboursement chiffré au repos (libsodium XSalsa20-Poly1305).
- Administration sécurisée (`ROLE_ADMIN`, CSRF, throttling, audit log).
- Design premium noir + doré, Poppins, Tailwind, animations respectant `prefers-reduced-motion`.

---

## Prérequis

- PHP 8.2+ (extensions : `pdo_mysql`, `intl`, `zip`, `sodium`, `mbstring`, `openssl`, `iconv`)
- MySQL 8.0+
- Composer 2.x
- Node.js 20+ (pour la build Tailwind)
- Symfony CLI (recommandé)

Optionnel : Docker + Docker Compose.

---

## Installation locale (sans Docker)

```bash
# 1) dépendances PHP
composer install

# 2) dépendances Node + build CSS
npm install
npm run build            # génère public/build/app.css

# 3) configuration
cp .env.example .env.locale
# Éditer .env.locale :
#   - DATABASE_URL
#   - MAILER_DSN
#   - LUXE_CAR_IBAN_ENCRYPTION_KEY : générer avec
#     php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# 4) base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# 5) données de dev
php bin/console doctrine:fixtures:load --no-interaction

# 6) créer un admin
php bin/console app:create-admin

# 7) démarrer le serveur
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public
```

Ouvrir :
- Public : http://localhost:8000
- Administration : http://localhost:8000/admin

Compte DEV créé par les fixtures : `admin@luxecar.local` / `admin1234!` — **à ne jamais utiliser en production**.

---

## Installation via Docker

```bash
cp .env.example .env.locale
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
docker compose exec php php bin/console app:create-admin
```

Application : http://localhost:8080

Le service `node` build automatiquement Tailwind en mode watch.

---

## Structure

```
src/
├── Command/                 app:create-admin
├── Controller/
│   ├── Admin/               8 controllers (dashboard, vehicles, reservations, ...)
│   └── Public/              home, vehicle, reservation, proof, refund, review
├── DataFixtures/
├── Entity/                  10 entities Doctrine
├── Enum/                    7 enums (status, mode, fuel, ...)
├── Form/                    6 form types
├── Repository/
├── Security/                AdminFormAuthenticator
├── Service/                 8 services métier
└── Twig/                    AppExtension

templates/
├── admin/                   dashboard + 6 sections + settings
├── components/              navbar, footer, cards, badges, flash
├── emails/                  bon de réservation HTML + texte
├── home/ vehicle/ reservation/ review/
└── base.html.twig
```

---

## Fonctionnalités

### Public
- Catalogue avec filtres (marque, carburant, tri prix/kilométrage)
- Fiche véhicule avec galerie (10 photos max) et URL slug unique (`/vehicule/peugeot-208`)
- Formulaire de réservation en une étape (mode retrait ou livraison)
- Calcul des frais de réservation **exclusivement côté serveur** (snapshot du prix)
- Page de paiement avec coordonnées bancaires + bouton "Copier IBAN"
- Upload sécurisé de preuve de virement (PDF/JPG/PNG/WEBP, 8 Mo, MIME vérifié)
- Demande de remboursement avec IBAN chiffré
- Formulaire d'avis (modération obligatoire avant publication)

### Administration
- Dashboard avec statistiques
- CRUD véhicules avec gestion photos (jusqu'à 10, choix principale, réordonnable)
- Publication / dépublication
- Bouton "Copier / Ouvrir" le lien public du véhicule
- Liste réservations filtrables (statut, recherche)
- Confirmation + envoi automatique du bon par e-mail
- Renvoyer le bon, annuler la réservation
- Preuves de virement : téléchargement sécurisé (Controller, jamais en accès direct)
- Remboursements : bouton "Révéler l'IBAN" (chiffré, action loggée)
- Modération avis (publier/masquer/refuser/éditer le texte)
- Paramètres : identité, banque, réservation, TikTok, mentions légales

### Sécurité
- CSRF partout (formulaires, actions POST admin)
- Firewall `^/admin` + `ROLE_ADMIN` (`#[IsGranted]` par contrôleur)
- Login throttling : 5 tentatives / 15 minutes
- Rate limiter formulaires publics (réservation, avis)
- Cookies `Secure` (auto), `HttpOnly`, `SameSite=lax`
- Uploads : validation MIME serveur, renommage UUID, taille bornée
- Preuves stockées **hors** de `public/` (`var/uploads/proofs/`) — servies via contrôleur admin
- IBAN chiffré libsodium — clé dans `LUXE_CAR_IBAN_ENCRYPTION_KEY`
- Journalisation admin (`admin_activity_logs`) : IP, action, entité

---

## Configuration mail

`MAILER_DSN` dans `.env.local`. Exemples :

```
MAILER_DSN=smtp://user:pass@smtp.example.com:587
MAILER_DSN=brevo+api://KEY@default
MAILER_DSN=resend+api://KEY@default
MAILER_DSN=postmark+api://KEY@default
```

`MAILER_FROM_ADDRESS` / `MAILER_FROM_NAME` doivent correspondre à une adresse validée chez le fournisseur.

---

## Permissions filesystem

```bash
mkdir -p var/uploads/proofs public/uploads/vehicles public/build
chmod -R 775 var/ public/uploads/ public/build/
# En prod, l'utilisateur du serveur web (www-data, nginx) doit posséder ces dossiers.
```

---

## Tests

```bash
php bin/phpunit
```

Couvre :
- calcul des 20% (`ReservationFeeCalculationTest`)
- génération du numéro de réservation (`ReservationNumberGeneratorTest`)
- chiffrement/déchiffrement IBAN (`RefundIbanEncryptionServiceTest`)
- libellés d'enums (`EnumsTest`)

---

## Production

```bash
APP_ENV=prod APP_DEBUG=0
composer install --no-dev --optimize-autoloader --classmap-authoritative
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
npm ci
npm run build
```

Checklist :
- HTTPS obligatoire
- `APP_SECRET` fort (32+ octets aléatoires)
- `LUXE_CAR_IBAN_ENCRYPTION_KEY` unique, **sauvegardée** — sinon impossible de déchiffrer les anciens IBAN
- `MAILER_DSN` production
- MySQL avec utilisateur dédié aux droits minimaux
- Nginx/Caddy avec en-têtes de sécurité (voir `docker/nginx/default.conf`)
- Sauvegardes : base MySQL + dossier `var/uploads/proofs/`

---

## Rotation de la clé de chiffrement IBAN

En cas de compromission suspectée :

1. Générer une nouvelle clé.
2. Écrire un script (dans `src/Command/`) qui déchiffre chaque `RefundRequest` avec l'ancienne clé et réenchiffre avec la nouvelle.
3. Basculer la variable d'env.

Ne jamais perdre l'ancienne clé avant d'avoir migré tous les enregistrements.

---

## Licence

Propriétaire — © LUXE CAR AUTOMOBIL.
