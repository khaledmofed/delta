/* Quote results — normalised quotes from all providers, filters, compare */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui;
  var qr, quotes = [], compare = [];

  function currentQr() {
    var id = U.qs("qr");
    try { id = id || sessionStorage.getItem("delta_last_qr"); } catch (e) {}
    return (id && S.quoteRequest(id)) || null;
  }

  function benefitCount(q) { var b = q.benefits; return [b.roadside, b.rentACar, b.gcc, b.oman, b.pa].filter(Boolean).length; }

  function filters() {
    var get = function (k) { return Array.prototype.map.call(document.querySelectorAll('[data-f="' + k + '"]:checked'), function (i) { return i.value; }); };
    return { type: get("type"), repair: get("repair"), need: get("need"), prov: get("prov"), max: Number(document.getElementById("maxPrice").value), sort: document.getElementById("sortBy").value };
  }

  function render() {
    var f = filters();
    var list = quotes.filter(function (q) {
      if (f.type.indexOf(q.insuranceType) < 0) return false;
      if (q.insuranceType === "comprehensive" && f.repair.indexOf(q.repairType) < 0) return false;
      if (f.prov.indexOf(q.providerId) < 0) return false;
      if (q.total > f.max) return false;
      for (var i = 0; i < f.need.length; i++) { var k = f.need[i]; var has = q.benefits[k] || (q.addons || []).some(function (a) { return a.k.toLowerCase().indexOf(k === "rentACar" ? "rent" : k === "pa" ? "personal" : k === "gcc" ? "gcc" : "roadside") > -1; }); if (!has) return false; }
      return true;
    });
    list.sort(function (a, b) {
      if (f.sort === "deductible") return a.deductible - b.deductible || a.total - b.total;
      if (f.sort === "benefits") return benefitCount(b) - benefitCount(a) || a.total - b.total;
      return a.total - b.total;
    });
    var cheapest = quotes.slice().sort(function (a, b) { return a.total - b.total; })[0];
    var comp = quotes.filter(function (q) { return q.insuranceType === "comprehensive"; });
    var best = comp.slice().sort(function (a, b) { return (a.total / (1 + benefitCount(a) * 0.08)) - (b.total / (1 + benefitCount(b) * 0.08)); })[0];

    document.getElementById("resCount").textContent = list.length + " of " + quotes.length + " offers";
    var box = document.getElementById("offers");
    if (!list.length) { box.innerHTML = '<div class="d-empty"><i class="fal fa-filter-circle-xmark" style="font-size:36px;color:var(--steel)"></i><p class="mt-15">No offers match your filters.</p><button class="d-btn d-btn--ghost d-btn--sm" id="resetF">Reset filters</button></div>'; document.getElementById("resetF").onclick = resetFilters; return; }
    box.innerHTML = list.map(function (q) {
      var p = S.provider(q.providerId);
      var b = q.benefits;
      function ben(ok, label) { return '<span class="' + (ok ? "y" : "n") + '"><i class="far ' + (ok ? "fa-check" : "fa-xmark") + '"></i>' + label + "</span>"; }
      var ribbon = best && q.id === best.id ? '<span class="ribbon">Best value</span>' : cheapest && q.id === cheapest.id ? '<span class="ribbon alt">Lowest price</span>' : "";
      var checked = compare.indexOf(q.id) > -1 ? "checked" : "";
      var addons = (q.addons || []).map(function (a) { return '<div class="d-kv"><span>+ ' + a.k + "</span><b>" + U.money(a.v) + "</b></div>"; }).join("");
      return '<article class="d-offer ' + (best && q.id === best.id ? "is-best" : "") + '">' + ribbon +
        '<div><div class="d-offer__prov">' + ui.mono(q.providerId, "lg") + "<div><b>" + q.planName + "</b><small>" + p.name + "</small></div></div>" +
        '<div class="d-offer__tags"><span class="d-tag">' + (q.insuranceType === "comprehensive" ? "Comprehensive" : "Third party") + "</span>" + (q.repairType ? '<span class="d-tag">' + (q.repairType === "agency" ? "Agency repair" : "Garage repair") + "</span>" : "") + (q.insuranceType === "comprehensive" ? '<span class="d-tag">Deductible ' + U.money0(q.deductible) + "</span>" : "") + "</div></div>" +
        '<div class="d-benefits">' + ben(b.roadside, "Roadside assistance") + ben(b.rentACar, "Rent-a-car") + ben(b.oman, "Oman cover") + ben(b.gcc, "GCC cover") + ben(b.pa, "Personal accident") + ben(b.windscreen > 0, b.windscreen ? "Windscreen " + U.money0(b.windscreen) : "Windscreen") + "</div>" +
        '<div class="d-offer__price"><span class="amt">' + U.money0(q.total) + '</span><small>incl. VAT · per year</small><button class="d-btn d-btn--primary d-btn--sm" data-select="' + q.id + '">Select offer</button><label class="d-check"><input type="checkbox" data-compare="' + q.id + '" ' + checked + " /> Compare</label></div>" +
        '<details><summary>Price breakdown &amp; details <i class="far fa-angle-down"></i></summary><div class="body"><div><div class="d-kv"><span>Base premium</span><b>' + U.money(q.premium) + "</b></div>" + addons + '<div class="d-kv"><span>VAT 5%</span><b>' + U.money(q.vat) + '</b></div><div class="d-kv total"><span>Total</span><b>' + U.money(q.total) + "</b></div></div>" +
        '<div><div class="d-kv"><span>Sum insured</span><b>' + (q.sumInsured ? U.money0(q.sumInsured) : "—") + '</b></div><div class="d-kv"><span>Valid until</span><b>' + U.fmtDate(q.validUntil) + '</b></div><div class="d-kv"><span>Insurer reference</span><b style="font-size:12px">' + q.providerRef + "</b></div></div>" +
        '<div class="d-muted-2" style="font-size:13px;line-height:1.6">Subject to the insurer’s policy wording, exclusions and verification of your documents. Quote ID ' + q.id + ".</div></div></details></article>";
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
    try { sessionStorage.setItem("delta_compare", JSON.stringify(compare)); } catch (e) {}
  }

  document.addEventListener("DOMContentLoaded", function () {
    qr = currentQr();
    if (!qr) {
      document.querySelector(".d-results").innerHTML = '<div class="d-empty" style="grid-column:1/-1"><h3 class="d-h3">No quote request found</h3><p class="mt-10">Start a new motor insurance quote to see offers.</p><a class="d-btn d-btn--primary mt-10" href="motor-quote.html">Get a Motor Insurance Quote</a></div>';
      return;
    }
    try { sessionStorage.setItem("delta_last_qr", qr.id); } catch (e) {}
    quotes = S.quotesFor(qr.id);
    try { compare = JSON.parse(sessionStorage.getItem("delta_compare") || "[]").filter(function (id) { return quotes.some(function (q) { return q.id === id; }); }); } catch (e) { compare = []; }

    var v = qr.vehicle, c = qr.customer;
    document.getElementById("resTitle").textContent = quotes.length + " offers for your " + v.make + " " + v.model;
    document.getElementById("sumbar").innerHTML =
      '<div class="it"><small>Vehicle</small><b>' + v.year + " " + v.make + " " + v.model + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Insured value</small><b>' + U.money0(v.value) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Driver</small><b>' + U.esc(c.name) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Starts</small><b>' + U.fmtDate(qr.cover.policyStart) + '</b></div><span class="sep"></span>' +
      '<div class="it"><small>Request</small><b>' + qr.id + "</b></div>";
    var provs = S.providers().filter(function (p) { return qr.providers[p.id]; });
    document.getElementById("provstat").innerHTML = provs.map(function (p) {
      var r = qr.providers[p.id];
      return r.status === "success" ? "<span>" + ui.mono(p.id, "sm") + p.short + " · " + (r.count || quotes.filter(function (q) { return q.providerId === p.id; }).length) + " offers · " + (r.ms / 1000).toFixed(1) + "s</span>"
        : '<span class="bad">' + ui.mono(p.id, "sm") + p.short + " · no response (timeout) — shown offers from other insurers</span>";
    }).join("");
    document.getElementById("provFilter").innerHTML = provs.filter(function (p) { return qr.providers[p.id].status === "success"; }).map(function (p) { return '<label class="d-check"><input type="checkbox" data-f="prov" value="' + p.id + '" checked /> ' + p.name + "</label>"; }).join("");
    document.getElementById("validity").textContent = quotes.length ? "Offers valid until " + U.fmtDate(quotes[0].validUntil) : "";

    // defaults from customer's choices
    document.querySelectorAll('[data-f="type"]').forEach(function (i) { i.checked = i.value === qr.cover.type; });
    if (qr.cover.repair && qr.cover.repair !== "any") document.querySelectorAll('[data-f="repair"]').forEach(function (i) { i.checked = i.value === qr.cover.repair; });
    var mx = Math.ceil(Math.max.apply(null, quotes.map(function (q) { return q.total; }).concat([1000])) / 100) * 100;
    var mp = document.getElementById("maxPrice");
    mp.min = 0; mp.max = mx; mp.step = 50; mp.value = mx;
    document.getElementById("maxPriceLbl").textContent = U.money0(mx);
    mp.addEventListener("input", function () { document.getElementById("maxPriceLbl").textContent = U.money0(mp.value); render(); });
    document.querySelectorAll("[data-f], #sortBy").forEach(function (i) { i.addEventListener("change", render); });

    document.getElementById("offers").addEventListener("click", function (e) {
      var s = e.target.closest("[data-select]");
      if (s) {
        s.innerHTML = '<span class="d-mini-spin"></span> Reserving…';
        var o = S.selectQuote(s.getAttribute("data-select"));
        setTimeout(function () { location.href = "checkout.html?order=" + encodeURIComponent(o.id); }, 450);
      }
    });
    document.getElementById("offers").addEventListener("change", function (e) {
      var c = e.target.closest("[data-compare]");
      if (!c) return;
      var id = c.getAttribute("data-compare");
      if (c.checked) { if (compare.length >= 3) { c.checked = false; ui.toast("You can compare up to 3 offers."); return; } compare.push(id); }
      else compare = compare.filter(function (x) { return x !== id; });
      updateCompareBar();
    });
    document.getElementById("compareClear").onclick = function () { compare = []; updateCompareBar(); render(); };
    document.getElementById("compareGo").onclick = function () { location.href = "compare.html?qr=" + encodeURIComponent(qr.id) + "&ids=" + compare.join(","); };
    render();
    updateCompareBar();
  });
})();
