# Prix d'Excellence Droit-Justice-Paix — Cour Suprême du Bénin

![Symfony](https://img.shields.io/badge/Symfony-7-000000?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![License](https://img.shields.io/badge/statut-projet%20acad%C3%A9mique-lightgrey)

Application web de gestion de bout en bout du **Prix d'Excellence Droit-Justice-Paix**, développée pour la **Cour Suprême du Bénin**. Elle numérise l'intégralité du processus du concours : dépôt de candidature, vérification administrative, évaluation anonyme par un jury, gestion du plagiat, et présélection des finalistes — depuis le dépôt du dossier jusqu'à la délibération.

Ce projet a été réalisé dans le cadre d'un mémoire de fin de cycle licence 2 en **Informatique de Gestion** à l'**ENEAM** (École Nationale d'Économie Appliquée et de Management), **Université d'Abomey-Calavi**, Bénin.

## Sommaire

- [Contexte du projet](#contexte-du-projet)
- [Stack technique](#stack-technique)
- [Fonctionnalités détaillées](#fonctionnalités-détaillées)
- [Rôles et accès](#rôles-et-accès)
- [Processus métier complet](#processus-métier-complet)
- [Modèle de données](#modèle-de-données)
- [Installation locale (XAMPP)](#installation-locale-xampp)
- [Configuration](#configuration)
- [Structure du projet](#structure-du-projet)
- [Sécurité et confidentialité](#sécurité-et-confidentialité)
- [Limites connues et pistes d'amélioration](#limites-connues-et-pistes-damélioration)
- [Auteurs](#auteurs)
- [Remerciements](#remerciements)

## Contexte du projet

Le Prix d'Excellence Droit-Justice-Paix est un concours organisé par la Cour Suprême du Bénin, destiné à récompenser des travaux et contributions remarquables dans les domaines du droit, de la justice et de la paix. Historiquement géré de façon manuelle (dépôts physiques, feuilles de notation papier, calculs manuels), le processus présentait plusieurs limites : risque d'erreurs de calcul, absence de traçabilité, difficulté à garantir l'anonymat des candidats pendant l'évaluation, et lenteur du traitement administratif.

Cette application a été conçue pour répondre à ces limites en proposant :
- un dépôt et un suivi de candidature entièrement dématérialisés,
- une évaluation anonymisée et automatisée par le jury,
- des règles de calcul strictes et non modifiables manuellement,
- une traçabilité complète de chaque étape du processus.

## Stack technique

| Composant | Technologie | Détails |
|---|---|---|
| Framework backend | Symfony 7 | Architecture MVC, routing par attributs PHP |
| ORM | Doctrine ORM | Entités avec identifiants UUID v7 |
| Moteur de templates | Twig | Turbo Frames pour certaines interactions dynamiques |
| Frontend | Bootstrap 5.3, Bootstrap Icons | Interface responsive, sans framework JS lourd |
| Base de données | MySQL | Via XAMPP en environnement de développement local |
| Authentification | Authenticator personnalisé (`CustomAuthenticator`) | Gestion de session manuelle plutôt que le système natif Symfony |
| Emails | SMTP Gmail | Notifications aux candidats et jurys |
| Gestion de version | Git / GitHub | Dépôt : `auguliennedjoi-collab/prix-excellence-v2` |

## Fonctionnalités détaillées

### Espace candidat (public)

- **Dépôt de candidature** avec upload de pièces jointes (CV, contribution, résumé, pièce d'identité, diplôme)
- **Suivi de dossier** (`/suivi`) via un code unique au format `PEX-XXXXXXXX`, affichant :
  - le statut administratif (soumis, incomplet, rejeté, validé), avec message explicite en cas de rejet ou de dossier incomplet
  - une frise de progression (dépôt → vérification → jury → délibération) reflétant l'avancement réel du dossier

### Espace administrateur

- **Tableau de bord** avec statistiques globales (total, en attente, validés, rejetés)
- **Gestion des candidatures** : recherche, filtrage par statut, validation ou rejet administratif
- **Gestion des utilisateurs** : création, modification, suppression des comptes (admin, jury, président du jury, vérificateur), avec gestion des rôles
- **Gestion des critères d'évaluation** :
  - structure hiérarchique (critères parents et sous-critères)
  - recalcul automatique de la note maximale d'un critère parent à chaque ajout, modification ou suppression d'un sous-critère
- **Gestion du plagiat** :
  - liste des 7 candidats présélectionnés, triés par note finale
  - saisie du taux de plagiat constaté par candidat
  - élimination manuelle des candidats jugés suspects
  - **remplacement automatique** : dès qu'un candidat est éliminé, le système repêche automatiquement le candidat suivant dans le classement général pour maintenir un pool stable de 5 finalistes
- **Paramétrage des pages publiques** (contenus dynamiques du site)

### Espace jury

- **Évaluation strictement anonyme** : seul un numéro de dossier anonyme et le niveau d'étude du candidat sont visibles ; aucune information d'identité n'est communiquée au jury
- **Notation en deux phases séquentielles** :
  1. **Phase écrite** — chaque jury note individuellement les critères écrits (structure hiérarchique reprise du back-office). La note écrite est plafonnée automatiquement selon la note maximale de chaque critère.
  2. **Phase orale** — débloquée uniquement lorsque **tous les jurys** ont terminé la phase écrite pour **tous les candidats validés** de l'édition. Chaque jury note ensuite l'oral individuellement.
- **Calcul automatique de la note finale** :
  - Note écrite générale = moyenne des notes écrites de tous les jurys
  - Note orale générale = moyenne des notes orales de tous les jurys
  - Note finale = 75 % × note écrite générale + 25 % × note orale générale
- **Interface organisée en deux sections** pour rester lisible même avec un grand nombre de candidats :
  - **« À évaluer »** : dossiers nécessitant une action du jury connecté
  - **« Déjà évalués »** (section repliable) : dossiers déjà notés, avec la possibilité de modifier la note à tout moment

## Rôles et accès

| Rôle | Description | Accès principal |
|---|---|---|
| `ROLE_ADMIN` | Contrôle total de l'application | `/admin/*` |
| `ROLE_JURY` | Évaluation anonyme des candidatures | `/jury/*` |
| `ROLE_PRESIDENT_JURY` | Hérite automatiquement de `ROLE_JURY` (via `role_hierarchy` dans `security.yaml`) | `/jury/*` — réservé pour des fonctions futures spécifiques au président |
| `ROLE_VERIFICATEUR` | Vérification préalable des dossiers avant validation administrative | Selon configuration |

## Processus métier complet

```
┌─────────────────────────┐
│   Dépôt de candidature   │
└────────────┬─────────────┘
             │
             ▼
┌─────────────────────────────────┐
│  Vérification administrative     │
│  (Admin : valider / rejeter)     │
└──────┬───────────────────┬──────┘
       │ Rejeté            │ Validé
       ▼                   ▼
   [Fin du         ┌─────────────────────────────┐
    processus]     │  Phase écrite (par jury)     │
                    │  Chaque jury note les        │
                    │  critères écrits              │
                    └──────────────┬────────────────┘
                                   │
                     (débloqué quand TOUS les jurys ont
                      terminé l'écrit pour TOUS les
                      candidats validés)
                                   ▼
                    ┌─────────────────────────────┐
                    │  Phase orale (par jury)      │
                    │  Chaque jury note l'oral      │
                    └──────────────┬────────────────┘
                                   │
                    Note finale = 75% écrit général
                                + 25% oral général
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │  Présélection automatique     │
                    │  Top 7 par note finale        │
                    └──────────────┬────────────────┘
                                   │
                                   ▼
                    ┌─────────────────────────────┐
                    │  Gestion du plagiat (Admin)   │
                    │  Saisie du taux, élimination   │
                    │  manuelle des cas suspects     │
                    └──────────────┬────────────────┘
                                   │
                   Remplacement automatique par le
                   candidat suivant du classement
                   jusqu'à stabilisation
                                   ▼
                    ┌─────────────────────────────┐
                    │      5 finalistes retenus      │
                    └─────────────────────────────┘
```

### Statuts de candidature

**Statut de la demande** (`App\Enum\StatutDemande`)

| Valeur | Signification |
|---|---|
| `BROUILLON` | Candidature non encore soumise |
| `SOUMIS` | En attente de vérification administrative |
| `INCOMPLET` | Pièces manquantes ou non conformes |
| `VALIDE` | Validée administrativement, transmise au jury |
| `REJETE` | Rejetée, non recevable |

**Statut de traitement** (`App\Enum\StatutTraitement`)

| Valeur | Signification |
|---|---|
| `EN_ATTENTE` | En attente d'examen par le jury |
| `EN_COURS_ETUDE` | En cours de notation par le jury |
| `PRESELECTIONNE` | Dans le pool des finalistes (avant/après contrôle plagiat) |
| `LAUREAT` | Lauréat officiel du prix |
| `NON_RETENU` | Non retenu après classement |
| `ELIMINE_PLAGIAT` | Éliminé pour plagiat constaté |

## Modèle de données

Entités principales (identifiants UUID v7, sauf `Critere` qui utilise un identifiant auto-incrémenté) :

- **`Candidat`** — informations personnelles du candidat
- **`Candidature`** — dossier de candidature : statuts, notes (écrite générale, orale générale, finale), taux de plagiat, code de suivi
- **`Critere`** — critère d'évaluation, avec relation parent/enfants et note maximale recalculée automatiquement
- **`Evaluation`** — évaluation d'un candidat par un jury donné (contrainte d'unicité jury + candidature), contenant note écrite, note orale, notes par critère
- **`NoteCritere`** — note attribuée à un critère précis dans le cadre d'une évaluation
- **`User`** — comptes utilisateurs (admin, jury, vérificateur), rôles stockés en JSON
- **`Document`** — pièces jointes liées à une candidature
- **`Edition`** — édition du concours (permet de gérer plusieurs éditions dans le temps)
- **`ParametrePage`** — contenus dynamiques des pages publiques

## Installation locale (XAMPP)

### Prérequis

- XAMPP (Apache + MySQL + PHP 8.2 ou supérieur)
- Composer
- Extension PHP `intl` activée
- Git

### Étapes

```bash
# 1. Cloner le projet dans le dossier htdocs de XAMPP
cd C:\xampp\htdocs
git clone https://github.com/auguliennedjoi-collab/prix-excellence-v2.git
cd prix-excellence-v2

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement local
copy .env .env.local
# Renseigner dans .env.local :
#   - DATABASE_URL (connexion MySQL)
#   - MAILER_DSN (SMTP Gmail)
#   - TRUSTED_HOSTS (inclure 127.0.0.1 et l'IP du réseau local si besoin)

# 4. Créer la base de données et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Vider le cache
php bin/console cache:clear
```

Accéder ensuite à l'application via :

```
http://localhost/prix-excellence-v2/public/
```

> **Important** : le `DocumentRoot` d'Apache doit pointer vers le dossier `public/` du projet. Si XAMPP sert par défaut la racine du projet, un fichier `.htaccess` à la racine redirige automatiquement vers `public/`.

## Configuration

Quelques points de configuration à vérifier après installation :

- **`config/packages/security.yaml`** : contient la hiérarchie des rôles (`role_hierarchy`), notamment `ROLE_PRESIDENT_JURY` qui hérite de `ROLE_JURY`
- **Nombre de jurys** : la phase orale ne se débloque que lorsque tous les comptes ayant `ROLE_JURY` ont terminé l'écrit ; s'assurer que les comptes jurys sont correctement créés avant de démarrer une évaluation
- **SMTP** : un mot de passe d'application Gmail est nécessaire pour l'envoi d'emails ; ne jamais le committer dans le dépôt Git

## Structure du projet

```
prix-excellence-v2/
├── src/
│   ├── Controller/
│   │   ├── AdminController.php      # Back-office : candidats, critères, plagiat, utilisateurs
│   │   ├── JuryController.php       # Évaluation écrite et orale
│   │   ├── CandidatureController.php
│   │   └── SecurityController.php   # Authentification manuelle par session
│   ├── Entity/
│   │   ├── Candidature.php
│   │   ├── Candidat.php
│   │   ├── Critere.php
│   │   ├── Evaluation.php
│   │   ├── NoteCritere.php
│   │   ├── Document.php
│   │   ├── Edition.php
│   │   └── User.php
│   ├── Enum/
│   │   ├── StatutDemande.php
│   │   └── StatutTraitement.php
│   ├── Repository/
│   └── Security/
│       └── CustomAuthenticator.php
├── templates/
│   ├── admin/                       # Interfaces d'administration
│   ├── jury/                        # Interfaces d'évaluation du jury
│   └── candidature/                 # Formulaire de dépôt et page de suivi public
├── migrations/
└── public/
```

## Sécurité et confidentialité

- L'**anonymat des candidats** est garanti pendant toute la phase d'évaluation : le jury ne voit qu'un numéro de dossier et le niveau d'étude du candidat.
- Les **notes** ne peuvent pas être modifiées après coup en dehors de l'interface prévue à cet effet (pas d'accès direct en base pour les jurys).
- Les **secrets** (mots de passe SMTP, identifiants de base de données) sont stockés dans `.env.local`, exclu du dépôt Git.

## Limites connues et pistes d'amélioration

- La gestion multi-éditions est prévue au niveau du modèle de données, mais l'interface actuelle suppose généralement une édition active à la fois.
- Le rôle `ROLE_PRESIDENT_JURY` hérite des permissions du jury mais n'a pas encore de fonctionnalités qui lui sont propres au-delà de la notation standard — un usage dédié pourra être défini dans une version ultérieure.
- L'envoi de notifications par email pourrait être étendu (rappels automatiques, accusés de réception).

## Auteurs

Projet conçu et développé par :

- **Augulienne DJOÏ**
- **Andil AMINOU**

Dans le cadre d'un mémoire de fin de cycle de licence 2 en **Informatique de Gestion**, ENEAM — Université d'Abomey-Calavi, Bénin.

## Remerciements

À la Cour Suprême du Bénin pour la confiance accordée dans le cadre de ce projet académique, et à l'ensemble du corps enseignant de l'ENEAM pour l'encadrement apporté tout au long de sa réalisation.