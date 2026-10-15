<?php
require __DIR__ . '/../api/db.php';
session_start();
if (isset($_GET['out'])) { session_destroy(); header('Location: index.php'); exit; }
if (isset($_POST['pw'])) { if (hash_equals(ADMIN_PASSWORD, $_POST['pw'])) $_SESSION['ok'] = 1; else $err = 'Mot de passe incorrect'; }
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin AND DEGGO</title>
<style>body{font-family:system-ui;background:#fbf3e6;color:#3a2416;margin:0;padding:20px}h1,h2{color:#5a2e14}table{border-collapse:collapse;width:100%;background:#fff;margin-bottom:30px}td,th{border:1px solid #e3d2b8;padding:8px;font-size:.9rem;vertical-align:top}th{background:#5a2e14;color:#fff}input,select,button{padding:6px;border-radius:6px;border:1px solid #b8692b}button{background:#5a2e14;color:#fff;cursor:pointer}.wrap{overflow:auto}</style></head><body>
<?php if (empty($_SESSION['ok'])): ?>
<h1>Espace admin – AND DEGGO</h1><form method="post"><input type="password" name="pw" placeholder="Mot de passe" required> <button>Connexion</button></form><p style="color:red"><?= $h($err ?? '') ?></p>
<?php else:
if (isset($_POST['status'])) db()->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], (int)$_POST['oid']]);
if (isset($_POST['price'])) db()->prepare("UPDATE options SET price=? WHERE id=?")->execute([max(0,(int)$_POST['price']), (int)$_POST['opt']]);
$orders = db()->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$tot = array_sum(array_column(array_filter($orders, fn($o)=>$o['status']!=='Annulée'), 'total'));
?>
<h1>Commandes <small>(<?= count($orders) ?> – CA hors annulées : <?= number_format($tot,0,',',' ') ?> FCFA)</small> <a href="?out=1">Déconnexion</a></h1>
<div class="wrap"><table><tr><th>N°</th><th>Date</th><th>Client</th><th>Détail</th><th>Total</th><th>Statut</th></tr>
<?php foreach ($orders as $o): ?><tr><td><?= $o['id'] ?></td><td><?= $h($o['created']) ?></td>
<td><?= $h($o['nom']) ?><br><a href="tel:<?= $h($o['tel']) ?>"><?= $h($o['tel']) ?></a><br><?= $h($o['mode'].' '.$o['adresse']) ?><br><?= $h($o['paiement']) ?><br><i><?= $h($o['note']) ?></i></td>
<td><?php foreach (json_decode($o['items'], true) as $i) echo $h($i['q'].' x '.$i['name'].' ('.$i['l'].')').'<br>'; ?></td>
<td><?= number_format($o['total'],0,',',' ') ?> FCFA</td>
<td><form method="post"><input type="hidden" name="oid" value="<?= $o['id'] ?>"><select name="status" onchange="this.form.submit()"><?php foreach (['Nouvelle','Confirmée','Livrée','Annulée'] as $s) echo '<option'.($s===$o['status']?' selected':'').'>'.$s.'</option>'; ?></select></form></td></tr><?php endforeach; ?></table></div>
<h2>Prix des produits</h2><div class="wrap"><table><tr><th>Produit</th><th>Format</th><th>Prix (FCFA)</th></tr>
<?php foreach (catalog() as $p) foreach ($p['options'] as $o): ?><tr><td><?= $h($p['name']) ?></td><td><?= $h($o['l']) ?></td>
<td><form method="post"><input type="hidden" name="opt" value="<?= $o['oid'] ?>"><input type="number" name="price" value="<?= $o['p'] ?>"> <button>OK</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; ?></body></html>
