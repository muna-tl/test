#!/bin/bash

# Script d'installation du Système de Gestion des Rendez-vous Hospitaliers

echo "🏥 Installation du Système de Gestion des Rendez-vous Hospitaliers"
echo "=================================================================="

# Vérification des prérequis
echo "📋 Vérification des prérequis..."

if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé"
    exit 1
fi

echo "✅ Prérequis vérifiés"

# Installation des dépendances
echo "📦 Installation des dépendances..."
composer install --no-interaction --optimize-autoloader

# Configuration de l'environnement
echo "⚙️  Configuration de l'environnement..."
if [ ! -f .env.local ]; then
    echo "DATABASE_URL=\"mysql://root:EL rghioui2022@127.0.0.1:3306/rendezvous_hospital?serverVersion=8.0&charset=utf8mb4\"" > .env.local
    echo "APP_SECRET=$(openssl rand -hex 32)" >> .env.local
    echo "✅ Fichier .env.local créé"
fi

# Création de la base de données
echo "🗄️  Création de la base de données..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Application des migrations
echo "🔄 Application des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

# Création des utilisateurs de test
echo "👥 Création des utilisateurs de test..."
php bin/console app:create-user admin@hospital.com admin123 Admin System --role=admin --phone="+33123456789" --no-interaction || true
php bin/console app:create-user doctor@hospital.com doctor123 "Dr. Jean" Dupont --role=doctor --phone="+33123456788" --no-interaction || true
php bin/console app:create-user patient@hospital.com patient123 Marie Martin --role=patient --phone="+33123456787" --no-interaction || true

# Nettoyage du cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

echo ""
echo "🎉 Installation terminée avec succès !"
echo ""
echo "📋 Comptes de test créés :"
echo "   👨‍💼 Admin    : admin@hospital.com / admin123"
echo "   👨‍⚕️ Docteur  : doctor@hospital.com / doctor123"
echo "   👤 Patient  : patient@hospital.com / patient123"
echo ""
echo "🚀 Pour démarrer le serveur :"
echo "   php -S 127.0.0.1:8000 -t public"
echo ""
echo "🌐 Puis ouvrir : http://127.0.0.1:8000"

