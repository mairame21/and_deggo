/* ===== CONFIGURATION : à modifier ===== */
const CONFIG = {
  whatsapp: "22177",          // numéro du GIE, format international sans +
  phoneDisplay: "+221 77 284 09 58",
  address: "Ngallele extension, Saint-Louis, Sénégal",
  deliveryFee: 1000                  // FCFA, 0 si gratuit
};

let PRODUCTS = [
  { id: 1, name: "Sankal", cat: "Sankal", icon: "🌾", desc: "Préparé avec des céréales locales.", options: [{ l: "500 g", p: 1500 }, { l: "1 kg", p: 2800 }] },
  { id: 2, name: "Arraw", cat: "Arraw", icon: "🥣", desc: "Un classique du terroir, fait main.", options: [{ l: "500 g", p: 1500 }, { l: "1 kg", p: 2800 }] },
  { id: 3, name: "Thiakry", cat: "Thiakry", icon: "🍚", desc: "Le dessert traditionnel, prêt à déguster.", options: [{ l: "Pot 500 ml", p: 1000 }, { l: "Pot 1 L", p: 1800 }] },
  { id: 4, name: "Thiere", cat: "Thiere", icon: "🍲", desc: "Céréale roulée à la vapeur, savoureuse.", options: [{ l: "500 g", p: 1500 }, { l: "1 kg", p: 2800 }] }
];

/* ===== OUTILS ===== */
const $ = s => document.querySelector(s);
const fmt = n => n.toLocaleString("fr-FR") + " FCFA";
let cart = [];
try { cart = JSON.parse(localStorage.getItem("anddeggo_cart")) || []; } catch (e) {}
const save = () => { try { localStorage.setItem("anddeggo_cart", JSON.stringify(cart)); } catch (e) {} };

/* ===== PRODUITS ===== */
let filter = "Tous";
function renderFilters() {
  const cats = ["Tous", ...PRODUCTS.map(p => p.cat)];
  $("#filters").innerHTML = cats.map(c => `<button class="${c === filter ? "on" : ""}" data-c="${c}">${c}</button>`).join("");
  $("#filters").querySelectorAll("button").forEach(b => b.onclick = () => { filter = b.dataset.c; renderFilters(); renderProducts(); });
}
function renderProducts() {
  const list = PRODUCTS.filter(p => filter === "Tous" || p.cat === filter);
  $("#grid").innerHTML = list.map(p => `
    <div class="card prod">
      <div class="ico">${p.icon}</div>
      <h3>${p.name}</h3>
      <p>${p.desc}</p>
      <select id="opt${p.id}">${p.options.map((o, i) => `<option value="${i}">${o.l} – ${fmt(o.p)}</option>`).join("")}</select>
      <div class="row"><button class="btn" data-add="${p.id}">Ajouter</button></div>
    </div>`).join("");
  document.querySelectorAll("[data-add]").forEach(b => b.onclick = () => add(+b.dataset.add));
}

/* ===== PANIER ===== */
function add(id) {
  const p = PRODUCTS.find(x => x.id === id);
  const i = +$("#opt" + id).value;
  const o = p.options[i];
  const key = id + "-" + i;
  const ex = cart.find(c => c.key === key);
  if (ex) ex.q++; else cart.push({ key, name: p.name, l: o.l, p: o.p, q: 1 });
  update();
  openDrawer();
}
function change(key, d) {
  const c = cart.find(x => x.key === key);
  if (!c) return;
  c.q += d;
  if (c.q <= 0) cart = cart.filter(x => x.key !== key);
  update();
}
function linesHTML() {
  if (!cart.length) return '<p class="empty">Votre panier est vide.</p>';
  return cart.map(c => `
    <div class="line">
      <span>${c.name} (${c.l})</span>
      <span class="qty"><button data-m="${c.key}">−</button><span>${c.q}</span><button data-p="${c.key}">+</button></span>
      <b>${fmt(c.p * c.q)}</b>
    </div>`).join("");
}
function totals() {
  const sub = cart.reduce((s, c) => s + c.p * c.q, 0);
  const fee = $("#mode").value === "livraison" && sub > 0 ? CONFIG.deliveryFee : 0;
  return { sub, fee, total: sub + fee };
}
function update() {
  save();
  const html = linesHTML();
  $("#drawerItems").innerHTML = html;
  $("#cartItems").innerHTML = html;
  document.querySelectorAll("[data-m]").forEach(b => b.onclick = () => change(b.dataset.m, -1));
  document.querySelectorAll("[data-p]").forEach(b => b.onclick = () => change(b.dataset.p, 1));
  const t = totals();
  $("#subtotal").textContent = fmt(t.sub);
  $("#deliveryFee").textContent = fmt(t.fee);
  $("#total").textContent = fmt(t.total);
  $("#cartCount").textContent = cart.reduce((s, c) => s + c.q, 0);
}
const openDrawer = () => { $("#drawer").classList.add("open"); $("#overlay").classList.add("on"); };
const closeDrawer = () => { $("#drawer").classList.remove("open"); $("#overlay").classList.remove("on"); };

/* ===== COMMANDE WHATSAPP ===== */
function wa(text) { window.open(`https://wa.me/${CONFIG.whatsapp}?text=${encodeURIComponent(text)}`, "_blank"); }

$("#orderForm").addEventListener("submit", async e => {
  e.preventDefault();
  if (!cart.length) { alert("Votre panier est vide."); return; }
  const form = e.target, f = new FormData(form), t = totals();
  let num = "";
  try {
    const r = await fetch("api/order.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({
      nom: f.get("nom"), tel: f.get("tel"), mode: f.get("mode"), adresse: f.get("adresse"), paiement: f.get("paiement"), note: f.get("note"),
      items: cart.map(c => { const [id, i] = c.key.split("-"); return { id: +id, i: +i, q: c.q }; }) }) });
    const d = await r.json();
    if (!d.ok) { alert(d.error || "Erreur"); return; }
    num = `N° de commande : ${d.id}\n`; t.sub = d.subtotal; t.fee = d.fee; t.total = d.total;
  } catch (err) {}
  const lines = cart.map(c => `- ${c.q} x ${c.name} (${c.l}) : ${fmt(c.p * c.q)}`).join("\n");
  wa(`Nouvelle commande AND DEGGO\n${num}\n${lines}\n\nSous-total : ${fmt(t.sub)}\nLivraison : ${fmt(t.fee)}\nTOTAL : ${fmt(t.total)}\n\nNom : ${f.get("nom")}\nTéléphone : ${f.get("tel")}\nMode : ${f.get("mode")}\nAdresse : ${f.get("adresse") || "-"}\nPaiement : ${f.get("paiement")}\nRemarque : ${f.get("note") || "-"}`);
  cart = []; update(); form.reset();
});

$("#contactForm").addEventListener("submit", e => {
  e.preventDefault();
  const f = new FormData(e.target);
  wa(`Message de ${f.get("nom")} :\n${f.get("msg")}`);
  e.target.reset();
});

/* ===== INTERFACE ===== */
$("#mode").onchange = () => { $("#adresse").style.display = $("#mode").value === "livraison" ? "" : "none"; update(); };
$("#openCart").onclick = openDrawer;
$("#closeCart").onclick = closeDrawer;
$("#overlay").onclick = closeDrawer;
$("#goOrder").onclick = closeDrawer;
$("#burger").onclick = () => $("#nav").classList.toggle("open");
$("#nav").onclick = () => $("#nav").classList.remove("open");

$("#reviews").innerHTML = REVIEWS.map(r => `<div class="card"><p>« ${r.t} »</p><p><b>${r.n}</b></p></div>`).join("");
$("#cAddress").textContent = CONFIG.address;
$("#cPhone").textContent = CONFIG.phoneDisplay;
$("#waFloat").href = `https://wa.me/${CONFIG.whatsapp}`;
$("#year").textContent = new Date().getFullYear();

renderFilters();
renderProducts();
update();
fetch("api/products.php").then(r => r.json()).then(d => {
  if (d.products && d.products.length) { PRODUCTS = d.products; CONFIG.deliveryFee = d.fee; renderFilters(); renderProducts(); update(); }
}).catch(() => {});
