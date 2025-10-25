# 🔐 Fonctionnalité: Code QR pour les Rendez-vous

## 📋 Description

Cette fonctionnalité ajoute un système de confirmation par code QR pour les rendez-vous médicaux. Chaque rendez-vous reçoit automatiquement un code de confirmation unique qui peut être scanné via un code QR.

## ✨ Fonctionnalités Implémentées

### 1. Code de Confirmation Unique
- **Format**: `XXXX-XXXX` (8 caractères alphanumériques)
- **Unicité**: Chaque code est unique dans la base de données
- **Génération automatique**: Lors de la création d'un rendez-vous

### 2. Code QR
- **Génération**: Le code QR contient le code de confirmation
- **Affichage**: Dans le PDF téléchargeable
- **Scannable**: Peut être scanné par n'importe quelle application de lecture QR

### 3. Vérification du Code
- **Route**: `/appointment/verify/{code}`
- **Fonction**: Vérifier la validité d'un code de confirmation
- **Utilisation**: Après avoir scanné le code QR

## 🗂️ Fichiers Modifiés

### 1. Entity: `src/Entity/Appointment.php`
```php
#[ORM\Column(length: 20, unique: true)]
private ?string $confirmationCode = null;
```
- Ajout du champ `confirmationCode`
- Contrainte d'unicité en base de données

### 2. Migration: `migrations/Version20251015134817.php`
- Création de la colonne `confirmation_code`
- Génération automatique des codes pour les rendez-vous existants
- Index unique sur le champ

### 3. Controller: `src/Controller/AppointmentController.php`

#### Méthodes ajoutées:
- `generateConfirmationCode()`: Génère un code unique
- `generateQRCode()`: Crée un code QR à partir du code de confirmation
- `verifyConfirmationCode()`: Vérifie un code scanné

#### Modifications:
- `book()`: Génère le code lors de la création
- `pdf()`: Ajoute le QR code au PDF

### 4. Templates

#### `templates/appointment/pdf.html.twig`
- Affichage du code de confirmation en évidence
- Intégration du code QR
- Design amélioré avec sections distinctes

#### `templates/appointment/confirm.html.twig`
- Affichage du code de confirmation
- Instructions pour télécharger le PDF

#### `templates/appointment/verify.html.twig` (nouveau)
- Interface de vérification du code
- Affichage des détails du rendez-vous si trouvé
- Message d'erreur si code invalide

## 🚀 Utilisation

### Pour le Patient

1. **Prendre un rendez-vous**:
   - Le système génère automatiquement un code de confirmation

2. **Télécharger le PDF**:
   - Le PDF contient le code de confirmation et le QR code
   - Nom du fichier: `confirmation_rdv_XXXX-XXXX.pdf`

3. **Scanner le QR Code**:
   - Utiliser n'importe quelle application de scan QR
   - Le code redirige vers: `/appointment/verify/{code}`

### Pour le Personnel Médical

1. **Scanner le QR code du patient**
2. **Vérifier les informations affichées**:
   - Nom du patient
   - Docteur assigné
   - Date et heure
   - Statut du rendez-vous

## 🔧 Configuration Technique

### Génération du QR Code

Le système utilise des API externes pour générer les codes QR:

1. **API Principale**: Google Charts API
   ```
   https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl={code}
   ```

2. **API de Secours**: QR Server API
   ```
   https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={code}
   ```

### Options Dompdf

```php
$options->set('isRemoteEnabled', true); // Permet le chargement des images distantes
```

## 📊 Base de Données

### Table: `appointment`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| confirmation_code | VARCHAR(20) | NOT NULL, UNIQUE |

### Migration

```sql
ALTER TABLE appointment ADD confirmation_code VARCHAR(20) NOT NULL;
CREATE UNIQUE INDEX UNIQ_FE38F844A0E239DE ON appointment (confirmation_code);
```

## 🧪 Tests

### Tester la génération du code:
```bash
# Créer un nouveau rendez-vous via l'interface web
# Le code devrait être généré automatiquement
```

### Vérifier les codes existants:
```bash
php bin/console dbal:run-sql "SELECT id, confirmation_code FROM appointment"
```

### Tester la vérification:
```
Accéder à: /appointment/verify/XXXX-XXXX
```

## 🎨 Exemple de Code QR

Le QR code contient simplement le code de confirmation (ex: `A1B2-C3D4`).

Lors du scan, l'utilisateur est redirigé vers:
```
https://votre-domaine.com/appointment/verify/A1B2-C3D4
```

## ⚠️ Notes Importantes

1. **Connexion Internet**: La génération du QR code nécessite une connexion Internet
2. **Sécurité**: Le code de confirmation seul ne donne pas accès aux données sensibles
3. **Unicité**: Chaque code est unique et lié à un seul rendez-vous
4. **Format PDF**: Le PDF peut être imprimé ou partagé numériquement

## 🔄 Évolutions Futures Possibles

1. ✅ Génération de QR code locale (sans API externe)
2. ✅ Historique des scans
3. ✅ Notification lors du scan
4. ✅ Expiration des codes après le rendez-vous
5. ✅ Code QR dynamique avec plus d'informations

## 📝 Auteur

Développé pour le système de gestion de rendez-vous hospitaliers.

Date: 16 Octobre 2025
