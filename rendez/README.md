<<<<<<< HEAD
# Système de Gestion des Rendez-vous Hospitaliers

## Description

Application web de gestion des rendez-vous hospitaliers développée avec Symfony 7.3, permettant la gestion de trois types d'utilisateurs : Patients, Docteurs et Administrateurs.

## Fonctionnalités

### 🔐 Authentification et Sécurité
- Système d'inscription avec validation des données
- Connexion sécurisée avec protection CSRF
- Gestion des rôles utilisateurs (Patient, Docteur, Administrateur)
- Prévention du retour en arrière après connexion
- Déconnexion sécurisée avec nettoyage de session

### 👥 Gestion des Utilisateurs
- **Patient** : Peut prendre des rendez-vous et consulter son historique
- **Docteur** : Peut gérer ses patients et ses rendez-vous
- **Administrateur** : Accès complet au système

### 🎨 Interface Utilisateur
- Design moderne et responsive avec Bootstrap 5
- Couleurs professionnelles et cohérentes
- Interface intuitive et accessible
- Animations et transitions fluides

## Technologies Utilisées

- **Framework** : Symfony 7.3
- **Base de données** : MySQL 8.0
- **Frontend** : Bootstrap 5, Font Awesome, Google Fonts
- **Sécurité** : Symfony Security Bundle
- **ORM** : Doctrine

## Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0
- Git

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone [URL_DU_REPO]
   cd rendezvous
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configuration de la base de données**
   
   Créer un fichier `.env.local` à la racine du projet :
   ```env
   DATABASE_URL="mysql://root:EL rghioui2022@127.0.0.1:3306/rendezvous_hospital?serverVersion=8.0&charset=utf8mb4"
   APP_SECRET=your_secret_key_here
   ```

4. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Créer des utilisateurs de test**
   ```bash
   # Administrateur
   php bin/console app:create-user admin@hospital.com admin123 Admin System --role=admin

   # Docteur
   php bin/console app:create-user doctor@hospital.com doctor123 "Dr. Jean" Dupont --role=doctor

   # Patient
   php bin/console app:create-user patient@hospital.com patient123 Marie Martin --role=patient
   ```

6. **Démarrer le serveur**
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

## Utilisation

### Accès à l'application
Ouvrir un navigateur et aller à : `http://127.0.0.1:8000`

### Comptes de test
- **Admin** : admin@hospital.com / admin123
- **Docteur** : doctor@hospital.com / doctor123  
- **Patient** : patient@hospital.com / patient123

### Pages disponibles
- `/` - Redirection vers le tableau de bord ou la connexion
- `/login` - Page de connexion
- `/register` - Page d'inscription
- `/dashboard` - Tableau de bord après connexion
- `/logout` - Déconnexion

## Structure du Projet

```
src/
├── Controller/          # Contrôleurs
│   └── AuthController.php
├── Entity/             # Entités Doctrine
│   └── User.php
├── Form/               # Formulaires Symfony
│   ├── LoginFormType.php
│   └── RegistrationFormType.php
├── Repository/         # Repositories Doctrine
│   └── UserRepository.php
└── Command/            # Commandes console
    └── CreateUserCommand.php

templates/
├── base.html.twig      # Template de base
└── auth/               # Templates d'authentification
    ├── login.html.twig
    ├── register.html.twig
    └── dashboard.html.twig

config/
├── packages/           # Configuration des bundles
│   ├── security.yaml   # Configuration sécurité
│   └── doctrine.yaml   # Configuration base de données
└── routes.yaml         # Routes personnalisées
```

## Sécurité

### Mesures de sécurité implémentées
- Hashage des mots de passe avec Symfony Password Hasher
- Protection CSRF sur tous les formulaires
- Validation côté serveur des données
- Prévention du retour en arrière après connexion
- Gestion des sessions sécurisée
- Hiérarchie des rôles définie

### Configuration des rôles
```yaml
role_hierarchy:
    ROLE_ADMIN: [ROLE_USER, ROLE_DOCTOR, ROLE_PATIENT]
    ROLE_DOCTOR: [ROLE_USER, ROLE_PATIENT]  
    ROLE_PATIENT: [ROLE_USER]
```

## Développement en Équipe

### Workflow Git recommandé
1. Créer une branche pour chaque fonctionnalité
2. Faire des commits atomiques avec des messages clairs
3. Créer une Pull Request pour review
4. Merger après validation de l'équipe

### Standards de codage
- Suivre les standards PSR-12 pour PHP
- Utiliser des noms de variables et méthodes explicites
- Documenter les méthodes complexes
- Ajouter des tests unitaires pour les nouvelles fonctionnalités

## Commandes Utiles

```bash
# Créer un nouvel utilisateur
php bin/console app:create-user email@example.com password123 Prénom Nom --role=patient

# Vider le cache
php bin/console cache:clear

# Voir les routes disponibles
php bin/console debug:router

# Créer une nouvelle migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

## Contribuer

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add: Amazing Feature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## Support

Pour toute question ou problème, créer une issue sur GitHub ou contacter l'équipe de développement.

---

**Équipe de développement** : 3 développeurs
**Licence** : Propriétaire
**Version** : 1.0.0
=======
# rendezvous
>>>>>>> 5250d20dbb05afaf968c73b3927d1f0ab9d0273c
