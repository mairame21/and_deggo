AND DEGGO - site web avec back-end PHP
- Hebergement : tout hebergeur PHP 7.4+ avec SQLite (extension pdo_sqlite). Envoyer tout le dossier par FTP.
- Avant de publier : changer ADMIN_PASSWORD et DELIVERY_FEE dans api/config.php.
- Espace admin : votre-site/admin/  (commandes, statuts, prix des produits).
- La base se cree automatiquement dans data/ au premier chargement. Le dossier data/ doit etre accessible en ecriture.
- Test local : php -S localhost:8000 dans le dossier, puis http://localhost:8000
- Sans PHP, le site fonctionne quand meme (commande par WhatsApp uniquement).
- Stock : dans l'espace admin, section "Stock des produits" pour marquer un produit en rupture de stock.
