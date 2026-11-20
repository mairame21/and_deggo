<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
function fail($m) { echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Méthode invalide');
$d = json_decode(file_get_contents('php://input'), true);
if (!$d) fail('Données invalides');
$nom = trim($d['nom'] ?? ''); $tel = trim($d['tel'] ?? '');
if ($nom === '' || !preg_match('/^[0-9 +]{9,16}$/', $tel)) fail('Nom ou téléphone invalide');
$mode = ($d['mode'] ?? '') === 'retrait' ? 'retrait' : 'livraison';
$adresse = trim($d['adresse'] ?? '');
if ($mode === 'livraison' && $adresse === '') fail('Adresse de livraison requise');
$cat = catalog(); $items = []; $sub = 0;
foreach (($d['items'] ?? []) as $it) {
  foreach ($cat as $p) if ($p['id'] === (int)$it['id'] && isset($p['options'][(int)$it['i']])) {
    if (!$p['inStock']) fail($p['name'] . ' est en rupture de stock');
    $o = $p['options'][(int)$it['i']]; $q = max(1, min(99, (int)$it['q']));
    $items[] = ['name'=>$p['name'],'l'=>$o['l'],'p'=>$o['p'],'q'=>$q]; $sub += $o['p'] * $q;
  }
}
if (!$items) fail('Panier vide');
$fee = ($mode === 'livraison') ? DELIVERY_FEE : 0;
$st = db()->prepare("INSERT INTO orders(created,nom,tel,mode,adresse,paiement,note,items,subtotal,fee,total) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
$st->execute([date('Y-m-d H:i'), $nom, $tel, $mode, $adresse, substr($d['paiement'] ?? '', 0, 40), substr($d['note'] ?? '', 0, 300), json_encode($items, JSON_UNESCAPED_UNICODE), $sub, $fee, $sub + $fee]);
echo json_encode(['ok'=>true,'id'=>(int)db()->lastInsertId(),'subtotal'=>$sub,'fee'=>$fee,'total'=>$sub+$fee]);
