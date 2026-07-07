
# Prix d'Excellence V2

Application web de gestion des candidatures pour le **Prix d'Excellence Droit-Justice-Paix** de la Cour Suprême du Bénin.

Ce projet permet la gestion complète du processus de sélection : dépôt des candidatures, vérification des dossiers, évaluation par un jury et suivi administratif, de manière centralisée et sécurisée.

## 📌 Contexte

Ce projet a été développé dans le cadre d'un mémoire de fin de formation en **Licence 2 Informatique de Gestion** à l'ENEAM (École Nationale d'Économie Appliquée et de Management), Université d'Abomey-Calavi, en collaboration avec la Cour Suprême du Bénin.

## 🚀 Fonctionnalités

- **Gestion multi-rôles** : Admin, Candidat, Jury, Responsable/Vérificateur
- **Dépôt de candidatures** en ligne avec suivi de dossier (`/suivi`)
- **Évaluation anonyme** : les jurys notent les candidats via des codes générés automatiquement, sans connaître leur identité
- **Système de critères dynamique** : grille d'évaluation hiérarchisée (critères et sous-critères), configurable sans toucher au code
- **Calcul automatique de la note finale** combinant note écrite, note orale et taux de plagiat
- **Tableau de bord administrateur** avec filtres, recherche et pagination
- **Notifications par email** (SMTP Gmail) pour informer les candidats de l'avancement de leur dossier
- **Pages de contenu dynamiques** (à propos, règlement, etc.) éditables depuis l'administration
- **Messages flash / toasts** pour une meilleure expérience utilisateur

## 🛠️ Stack technique

- **Framework** : Symfony 7
- **Langage** : PHP
- **Base de données** : MySQL (via Doctrine ORM)
- **Frontend** : Twig, Bootstrap
- **Environnement local** : XAMPP
- **Gestion de version** : Git / GitHub

## 📦 Prérequis

- PHP 8.2+
- Composer
- MySQL / MariaDB
- Symfony CLI (recommandé)
- Extensions PHP requises : `intl`, `pdo_mysql`, `mbstring`, etc.

## ⚙️ Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/auguliennedjoi-collab/prix-excellence-v2.git
   cd prix-excellence-v2
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer les variables d'environnement**

   Copier le fichier `.env` en `.env.local` et adapter les valeurs :
   ```env
   DATABASE_URL="mysql://root:@127.0.0.1:3306/prix_excellence_v2"
   MAILER_DSN=gmail://VOTRE_EMAIL:VOTRE_MOT_DE_PASSE_APPLICATION@default
   ```

4. **Créer la base de données et exécuter les migrations**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **(Optionnel) Charger des données de test**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. **Lancer le serveur local**
   ```bash
   symfony server:start
   ```
   ou via XAMPP en plaçant le projet dans `htdocs`.

## 🔄 Déploiement

Après chaque `git push`, sur le serveur :
```bash
git pull origin main
php bin/console cache:clear
```

## 👥 Rôles utilisateurs

| Rôle | Description |
|------|-------------|
| **Admin** | Gestion globale : utilisateurs, paramètres, contenu |
| **Candidat** | Dépôt et suivi de candidature |
| **Jury** | Évaluation anonyme des candidatures selon la grille de critères |
| **Responsable** | Vérification et validation des dossiers |

## 👤 Auteurs

- **Non** — Développement, conception
- **Andil** — Collaboration au développement

Projet réalisé dans le cadre d'un stage et d'un mémoire à la Cour Suprême du Bénin.

## 📄 Licence

Projet académique — usage réservé à la Cour Suprême du Bénin et à l'ENEAM.
