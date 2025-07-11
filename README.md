# WooCommerce Multi-Currency per Country

Ce plugin WordPress permet de définir des prix personnalisés pour chaque pays et de forcer la devise affichée en fonction de la géolocalisation de l'utilisateur. Il est conçu pour fonctionner avec WooCommerce et les produits variables.

## 🧩 Fonctionnalités

- Définition d'un prix spécifique par pays pour chaque variation de produit.
- Détection automatique du pays de l'utilisateur (via IP).
- Affichage de la devise et du symbole personnalisé selon le pays.
- Interface d’administration pour configurer les associations pays / devise / symbole.
- Supporte les blocs WooCommerce (y compris checkout et cart).
- Compatible avec WooCommerce Subscriptions.
- Prévu pour une utilisation multi-site ou multi-pays.

## ⚙️ Installation

1. Télécharge le plugin ou clone ce dépôt :
   ```bash
   git clone https://github.com/votre-utilisateur/woo-multi-currency.git
Copie le dossier woo-multi-currency dans wp-content/plugins/

Active le plugin depuis le back-office WordPress

🚀 Utilisation
Va dans Réglages > Multi-Currency pour :

définir la devise par pays

définir le symbole personnalisé (ex. MAD, €, AED, etc.)

Dans l’édition d’un produit variable :

Renseigne les champs personnalisés pour les pays supportés (par exemple : prix Maroc, prix France…)

L’utilisateur verra automatiquement les prix dans la devise de son pays, avec les prix personnalisés s’ils existent.

🔄 Mises à jour
Si tu déploies ce plugin sur plusieurs sites, tu peux gérer les mises à jour manuellement en :

tirant les dernières versions depuis GitHub

ou en intégrant un système de mise à jour automatique (GitHub Updater ou WP Pusher)

📦 Dépendances
WordPress ≥ 5.0

WooCommerce ≥ 5.0

📄 Licence
MIT – libre d’utilisation et de modification.

Développé avec ❤️ par Ahmed Hilali