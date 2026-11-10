/* Quote results — offers stored by the quote engine (QuoteEngine job). Polls the server until every
   insurer has answered; an insurer error is shown on its own line and never hides other offers. */
(function () {
  "use strict";
  var D = window.DELTA, U = D.util, ui = D.ui;
  var M = window.MOTOR || {};
  var state = M.state, quotes = [], compare = [], timer = null, polls = 0;
  var NEED = { roadside: /road\s*side|roadside|breakdown|assistance/i, rentACar: /rent|replacement|hire car/i, gcc: /\bgcc\b/i, pa: /personal accident|\bP\.?A\.?\b/i };

  function logo(id, size) {
    var q = quotes.filter(function (x) { return x.insurer === id; })[0];
    var ins = (state.insurers || []).filter(function (x) { return x.code === id; })[0] || { code: id, name: q ? q.insurerName : id, logo: q ? q.logo : "" };
    return '<span class="ins-logo ' + (size ? "ins-logo--" + size : "") + '" title="' + U.esc(ins.name) + '"><img src="' + U.esc(ins.logo) + '" alt="' + U.esc(ins.name) + '" data-pid="' + U.esc(ins.code) + '" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span>';
  }
  function included(q) { return q.covers.filter(function (c) { return c.type === "mandatory"; }); }
  function optional(q) { return q.covers.filter(function (c) { return c.type === "optional"; }); }
  function has(q, k) { return q.covers.some(function (c) { return NEED[k].test(c.name); }); }

  function filters() {
    var get = function (k) { return Array.prototype.map.call(document.querySelectorAll('[data-f="' + k + '"]:checked'), function (i) { return i.value; }); };
    return { type: get("type"), repair: get("repair"), need: get("need"), prov: get("prov"), max: Number(document.getElementById("maxPrice").value), sort: document.getElementById("sortBy").value };
  }

  function waiting() { return (state.insurers || []).some(function (i) { return i.status === "pending"; }); }

  function render() {
    var f = filters();
    var list = quotes.filter(function (q) {
      if (f.type.indexOf(q.insuranceType) < 0) return false;
      if (q.insuranceType === "comprehensive" && q.repairType && f.repair.indexOf(q.repairType) < 0) return false;
      if (f.prov.indexOf(q.insurer) < 0) return false;
      if (q.total > f.max) return false;
      for (var i = 0; i < f.need.length; i++) if (!has(q, f.need[i])) return false;
      return true;
    });
    list.sort(function (a, b) {
      if (f.sort === "deductible") return (a.deductible === null) - (b.deductible === null) || (a.deductible || 0) - (b.deductible || 0) || a.total - b.total;
      if (f.sort === "benefits") return included(b).length - included(a).length || a.total - b.total;
      return a.total - b.total;
    });
    var cheapest = quotes.slice().sort(function (a, b) { return a.total - b.total; })[0];

    document.getElementById("resCount").textContent = list.length + " of " + quotes.length + " offers";
    var box = document.getElementById("offers");
    if (!quotes.length) {
      box.innerHTML = waiting()
        ? '<div class="d-empty"><div class="d-spinner" style="margin:0 auto"></div><p class="mt-15">Waiting for the insurers to reply…</p></div>'
        : '<div class="d-empty"><i class="fal fa-circle-info" style="font-size:36px;color:var(--steel)"></i><h3 class="d-h3 mt-15">No online offers this time</h3><p class="mt-10">The insurers could not price your request online — see the message above. Our advisors can still help you get covered.</p><div class="d-flex gap-2 justify-content-center flex-wrap"><a class="d-btn d-btn--primary d-btn--sm" href="' + M.contactUrl + '">Speak to an advisor</a><a class="d-btn d-btn--ghost d-btn--sm" href="' + M.quoteUrl + '?edit=1">Edit details</a></div></div>';
      return;
    }
    if (!list.length) { box.innerHTML = '<div class="d-empty"><i class="fal fa-filter-circle-xmark" style="font-size:36px;color:var(--steel)"></i><p class="mt-15">No offers match your filters.</p><button class="d-btn d-btn--ghost d-btn--sm" id="resetF">Reset filters</button></div>'; document.getElementById("resetF").onclick = resetFilters; return; }
    box.innerHTML = list.map(function (q) {
      var ribbon = cheapest && q.id === cheapest.id && quotes.length > 1 ? '<span class="ribbon alt">Lowest price</span>' : "";
      var checked = compare.indexOf(q.id) > -1 ? "checked" : "";
      var ben = included(q).map(function (c) { return '<span class="y"><i class="far fa-check"></i>' + U.esc(c.name) + "</span>"; }).join("") +
        optional(q).map(function (c) { return '<span class="n"><i class="far fa-plus"></i>' + U.esc(c.name) + " · optional +" + U.money0(c.premium) + "</span>"; }).join("");
      var lines = included(q).map(function (c) { return '<div class="d-kv"><span>' + U.esc(c.name) + "</span><b>" + U.money(c.premium) + "</b></div>"; }).join("");
      return '<article class="d-offer">' + ribbon +
        '<div><div class="d-offer__prov">' + logo(q.insurer, "lg") + "<div><b>" + U.esc(q.planName) + "</b><small>" + U.esc(q.insurerName) + "</small></div></div>" +
        '<div class="d-offer__tags"><span class="d-tag">' + (q.insuranceType === "comprehensive" ? "Comprehensive" : "Third party") + "</span>" + (q.repairType ? '<span class="d-tag">' + (q.repairType === "agency" ? "Agency repair" : "Garage repair") + "</span>" : "") + (q.insuranceType === "comprehensive" && q.deductible !== null ? '<span class="d-tag">Deductible ' + U.money0(q.deductible) + "</span>" : "") + "</div></div>" +
        '<div class="d-benefits">' + (ben || '<span class="y"><i class="far fa-check"></i>' + (q.insuranceType === "comprehensive" ? "Comprehensive cover" : "Third-party liability") + "</span>") + "</div>" +
        '<div class="d-offer__price"><span class="amt">' + U.money0(q.total) + '</span><small>incl. VAT · per year</small><button class="d-btn d-btn--primary d-btn--sm" data-select="' + q.id + '">Select offer</button><a class="d-btn d-btn--ghost d-btn--sm" href="' + U.esc(q.detailsUrl) + '">View details</a><label class="d-check"><input type="checkbox" data-compare="' + q.id + '" ' + checked + " /> Compare</label></div>" +
        '<details><summary>Price breakdown &amp; details <i class="far fa-angle-down"></i></summary><div class="body"><div>' + (lines || '<div class="d-kv"><span>Base premium</span><b>' + U.money(q.premium) + "</b></div>") + '<div class="d-kv"><span>VAT 5%</span><b>' + U.money(q.vat) + '</b></div><div class="d-kv total"><span>Total</span><b>' + U.money(q.total) + "</b></div></div>" +
        '<div><div class="d-kv"><span>Sum insured</span><b>' + (q.sumInsured ? U.money0(q.sumInsured) : "—") + '</b></div><div class="d-kv"><span>Valid until</span><b>' + U.esc(q.validUntil) + '</b></div><div class="d-kv"><span>Insurer reference</span><b style="font-size:12px">' + U.esc(q.ref || "—") + "</b></div></div>" +
        '<div class="d-muted-2" style="font-size:13px;line-height:1.6">' + (q.indicative ? "Indicative price — the insurer confirms the final price and excess before payment. " : "") + "Subject to the insurer’s policy wording, exclusions and verification of your documents. Quote ID " + q.id + ".</div></div></details></article>";
    }).join("");
  }

  function resetFilters() {
    document.querySelectorAll("[data-f]").forEach(function (i) { i.checked = i.getAttribute("data-f") !== "need"; });
    var mp = document.getElementById("maxPrice"); mp.value = mp.max; document.getElementById("maxPriceLbl").textContent = U.money0(mp.max);
    render();
  }

  function updateCompareBar() {
    var bar = document.getElementById("compareBar");
    bar.classList.toggle("show", compare.length > 0);
    document.getElementById("compareTxt").textContent = compare.length + " of 3 selected";
    document.getElementById("compareGo").classList.toggle("is-disabled", compare.length < 2);
    try { sessionStorage.setItem("delta_compare_" + state.reference, JSON.stringify(compare)); } catch (e) {}
  }

  function renderHead() {
    var withOffers = (state.insurers || []).filter(function (i) { return i.status === "success" && i.offers > 0; });
    document.getElementById("resTitle").textContent = !quotes.length
      ? (waiting() ? "Requesting your offers…" : "No online offers for your " + state.make + " " + state.model)
      : quotes.length + (quotes.length === 1 ? " offer" : " offers") + " for your " + state.make + " " + state.model;
    var si = quotes.filter(function (q) { return q.sumInsured; }).map(function (q) { return q.sumInsured; });
    document.getElementById("sumbar").innerHTML =
      '<div class="it"><small>Vehicle</small><b>' + U.esc(state.vehicle) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Insured value</small><b>' + (si.length ? U.money0(Math.max.apply(null, si)) : "—") + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Driver</small><b>' + U.esc(state.driver) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Starts</small><b>' + U.esc(state.starts) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Request</small><b>' + U.esc(state.reference) + "</b></div>";
    document.getElementById("provstat").innerHTML = (state.insurers || []).map(function (p) {
      if (p.status === "pending") return "<span>" + logo(p.code, "sm") + U.esc(p.name) + ' · <span class="d-mini-spin"></span> requesting…</span>';
      if (p.status === "success") return "<span>" + logo(p.code, "sm") + U.esc(p.name) + " · " + p.offers + (p.offers === 1 ? " offer · " : " offers · ") + (p.ms / 1000).toFixed(1) + "s</span>";
      return '<span class="bad">' + logo(p.code, "sm") + U.esc(p.name) + " · " + U.esc(p.message) + (p.errorCode && /^\d+$/.test(p.errorCode) ? ' <span class="code">' + U.esc(p.errorCode) + "</span>" : "") + (quotes.length ? " — showing offers from other insurers" : "") + "</span>";
    }).join("");
    var prov = document.getElementById("provFilter");
    var checked = {};
    prov.querySelectorAll("input").forEach(function (i) { checked[i.value] = i.checked; });
    prov.innerHTML = withOffers.map(function (p) { return '<label class="d-check"><input type="checkbox" data-f="prov" value="' + U.esc(p.code) + '" ' + (checked[p.code] === false ? "" : "checked") + " /> " + U.esc(p.name) + "</label>"; }).join("");
    prov.querySelectorAll("input").forEach(function (i) { i.addEventListener("change", render); });
    document.getElementById("validity").textContent = quotes.length ? "Offers valid until " + quotes[0].validUntil : "";

    var mp = document.getElementById("maxPrice"), atMax = !mp.max || Number(mp.value) >= Number(mp.max);
    var mx = Math.ceil(Math.max.apply(null, quotes.map(function (q) { return q.total; }).concat([1000])) / 100) * 100;
    mp.min = 0; mp.max = mx; mp.step = 50;
    if (atMax) mp.value = mx;
    document.getElementById("maxPriceLbl").textContent = U.money0(mp.value);
  }

  var typeDefaultApplied = false;
  /* Start with the customer's cover type; if no insurer offered it (e.g. only Third Party), show all types. */
  function defaultTypeFilter() {
    if (typeDefaultApplied || !quotes.length) return;
    var wanted = state.cover.type, hasWanted = quotes.some(function (q) { return q.insuranceType === wanted; });
    if (!hasWanted && state.done) {
      document.querySelectorAll('[data-f="type"]').forEach(function (i) { i.checked = true; });
      ui.toast("No " + (wanted === "comprehensive" ? "comprehensive" : "third-party") + " offer was available online — showing the offers we received.");
    }
    if (hasWanted || state.done) typeDefaultApplied = true;
  }

  function apply(s) {
    state = s;
    quotes = s.quotes || [];
    defaultTypeFilter();
    compare = compare.filter(function (id) { return quotes.some(function (q) { return q.id === id; }); });
    renderHead();
    render();
    updateCompareBar();
  }

  function poll() {
    if (state.done || polls > 90) return;
    timer = setTimeout(function () {
      polls++;
      fetch(state.statusUrl, { headers: { Accept: "application/json" }, credentials: "same-origin" })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (s) { apply(s); poll(); })
        .catch(function () { poll(); });
    }, polls < 5 ? 1500 : 3000);
  }

  function select(url, btn) {
    btn.innerHTML = '<span class="d-mini-spin"></span> Reserving…';
    var fm = document.createElement("form");
    fm.method = "post"; fm.action = url;
    fm.innerHTML = '<input type="hidden" name="_token" value="' + U.esc(M.csrf) + '" />';
    document.body.appendChild(fm);
    fm.submit();
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!state) return;
    try { compare = JSON.parse(sessionStorage.getItem("delta_compare_" + state.reference) || "[]"); } catch (e) { compare = []; }

    // defaults from the customer's choices
    document.querySelectorAll('[data-f="type"]').forEach(function (i) { i.checked = i.value === state.cover.type; });
    if (state.cover.repair) document.querySelectorAll('[data-f="repair"]').forEach(function (i) { i.checked = i.value === state.cover.repair; });
    var mp = document.getElementById("maxPrice");
    mp.addEventListener("input", function () { document.getElementById("maxPriceLbl").textContent = U.money0(mp.value); render(); });
    document.querySelectorAll("[data-f], #sortBy").forEach(function (i) { i.addEventListener("change", render); });

    document.getElementById("offers").addEventListener("click", function (e) {
      var s = e.target.closest("[data-select]");
      if (!s) return;
      var q = quotes.filter(function (x) { return String(x.id) === s.getAttribute("data-select"); })[0];
      if (q) select(q.selectUrl, s);
    });
    document.getElementById("offers").addEventListener("change", function (e) {
      var c = e.target.closest("[data-compare]");
      if (!c) return;
      var id = Number(c.getAttribute("data-compare"));
      if (c.checked) { if (compare.length >= 3) { c.checked = false; ui.toast("You can compare up to 3 offers."); return; } compare.push(id); }
      else compare = compare.filter(function (x) { return x !== id; });
      updateCompareBar();
    });
    document.getElementById("compareClear").onclick = function () { compare = []; updateCompareBar(); render(); };
    document.getElementById("compareGo").onclick = function () { if (compare.length >= 2) location.href = state.compareUrl + "?ids=" + compare.join(","); };

    apply(state);
    if (M.toast) ui.toast(M.toast, "err");
    poll();
  });
})();
