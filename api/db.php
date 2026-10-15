<?php
require __DIR__ . '/config.php';
function db() {
  static $p;
  if ($p) return $p;
  $p = new PDO('sqlite:' . DB_FILE);
  $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $p->exec("CREATE TABLE IF NOT EXISTS products(id INTEGER PRIMARY KEY, name TEXT, cat TEXT, icon TEXT, descr TEXT)");
  $p->exec("CREATE TABLE IF NOT EXISTS options(id INTEGER PRIMARY KEY, product_id INTEGER, label TEXT, price INTEGER)");
  $p->exec("CREATE TABLE IF NOT EXISTS orders(id INTEGER PRIMARY KEY, created TEXT, nom TEXT, tel TEXT, mode TEXT, adresse TEXT, paiement TEXT, note TEXT, items TEXT, subtotal INTEGER, fee INTEGER, total INTEGER, status TEXT DEFAULT 'Nouvelle')");
  if (!$p->query("SELECT COUNT(*) FROM products")->fetchColumn()) {
    $seed = [
      ['Sankal','Sankal','🌾','Préparé avec des céréales locales.',[['500 g',1500],['1 kg',2800]]],
      ['Arraw','Arraw','🥣','Un classique du terroir, fait main.',[['500 g',1500],['1 kg',2800]]],
      ['Thiakry','Thiakry','🍚','Le dessert traditionnel, prêt à déguster.',[['Pot 500 ml',1000],['Pot 1 L',1800]]],
      ['Thiere','Thiere','🍲','Céréale roulée à la vapeur, savoureuse.',[['500 g',1500],['1 kg',2800]]],
    ];
    foreach ($seed as $s) {
      $p->prepare("INSERT INTO products(name,cat,icon,descr) VALUES(?,?,?,?)")->execute([$s[0],$s[1],$s[2],$s[3]]);
      $pid = $p->lastInsertId();
      foreach ($s[4] as $o) $p->prepare("INSERT INTO options(product_id,label,price) VALUES(?,?,?)")->execute([$pid,$o[0],$o[1]]);
    }
  }
  return $p;
}
function catalog() {
  $out = [];
  foreach (db()->query("SELECT * FROM products ORDER BY id") as $r) {
    $st = db()->prepare("SELECT id,label,price FROM options WHERE product_id=? ORDER BY id");
    $st->execute([$r['id']]);
    $out[] = ['id'=>(int)$r['id'],'name'=>$r['name'],'cat'=>$r['cat'],'icon'=>$r['icon'],'desc'=>$r['descr'],
      'options'=>array_map(fn($o)=>['oid'=>(int)$o['id'],'l'=>$o['label'],'p'=>(int)$o['price']], $st->fetchAll(PDO::FETCH_ASSOC))];
  }
  return $out;
}
