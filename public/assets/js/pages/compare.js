/* Side-by-side comparison of up to 3 quotes */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui;
  document.addEventListener("DOMContentLoaded", function () {
    var ids = (U.qs("ids") || "").split(",").filter(Boolean);
    if (!ids.length) { try { ids = JSON.parse(sessionStorage.getItem("delta_compare") || "[]"); } catch (e) {} }
    var qs = ids.map(S.quote).filter(Boolean);
    var t = document.getElementById("ctable");
    if (U.qs("qr")) document.getElementById("backToResults").href = "quote-results.html?qr=" + encodeURIComponent(U.qs("qr"));
    if (qs.length < 2) { t.innerHTML = '<tr><td style="padding:40px;text-align:center" colspan="4">Select at least two offers on the results page to compare. <a class="d-link" href="quote-results.html">Back to offers</a></td></tr>'; return; }
    var min = Math.min.apply(null, qs.map(function (q) { return q.total; }));
    function yn(v, txt) { return v ? '<span class="y"><i class="far fa-check"></i> ' + (txt || "Included") + "</span>" : '<span class="n"><i class="far fa-xmark"></i> Not included</span>'; }
    var rows = [
      ["Insurer", function (q) { return '<div class="d-flex align-items-center gap-2">' + ui.mono(q.providerId, "sm") + S.provider(q.providerId).name + "</div>"; }],
      ["Cover type", function (q) { return q.insuranceType === "comprehensive" ? "Comprehensive" : "Third party"; }],
      ["Repair", function (q) { return q.repairType ? (q.repairType === "agency" ? "Agency" : "Garage") : "—"; }],
      ["Sum insured", function (q) { return q.sumInsured ? U.money0(q.sumInsured) : "—"; }],
      ["Deductible / excess", function (q) { return q.insuranceType === "comprehensive" ? U.money0(q.deductible) : "—"; }],
      ["Roadside assistance", function (q) { return yn(q.benefits.roadside); }],
      ["Rent-a-car", function (q) { return yn(q.benefits.rentACar); }],
      ["Oman cover", function (q) { return yn(q.benefits.oman); }],
      ["GCC cover", function (q) { return yn(q.benefits.gcc); }],
      ["Personal accident", function (q) { return yn(q.benefits.pa); }],
      ["Windscreen", function (q) { return q.benefits.windscreen ? yn(true, "Up to " + U.money0(q.benefits.windscreen)) : yn(false); }],
      ["Add-ons requested", function (q) { return (q.addons || []).map(function (a) { return a.k + " (+" + U.money0(a.v) + ")"; }).join("<br/>") || "—"; }],
      ["Premium", function (q) { return U.money(q.premium + q.addonTotal); }],
      ["VAT (5%)", function (q) { return U.money(q.vat); }],
      ["Valid until", function (q) { return U.fmtDate(q.validUntil); }],
    ];
    var head = "<thead><tr><th></th>" + qs.map(function (q) {
      return '<th class="' + (q.total === min ? "best" : "") + '"><div style="color:var(--ink);font-size:16px;font-weight:600">' + q.planName + '</div><div style="font-family:var(--font-head);font-size:30px;color:var(--ink);letter-spacing:-.04em;margin:8px 0 4px">' + U.money0(q.total) + '</div><small class="d-muted-2">incl. VAT</small>' + (q.total === min ? '<div class="mt-10"><span class="d-badge d-badge--online">Lowest price</span></div>' : "") + "</th>";
    }).join("") + "</tr></thead>";
    var body = "<tbody>" + rows.map(function (r) { return "<tr><td>" + r[0] + "</td>" + qs.map(function (q) { return '<td class="' + (q.total === min ? "best " : "") + (r[0] === "Premium" ? "hl" : "") + '">' + r[1](q) + "</td>"; }).join("") + "</tr>"; }).join("") +
      "<tr><td></td>" + qs.map(function (q) { return '<td class="' + (q.total === min ? "best" : "") + '"><button class="d-btn d-btn--primary d-btn--sm" data-select="' + q.id + '">Select this offer</button></td>'; }).join("") + "</tr></tbody>";
    t.innerHTML = head + body;
    t.addEventListener("click", function (e) {
      var b = e.target.closest("[data-select]"); if (!b) return;
      var o = S.selectQuote(b.getAttribute("data-select"));
      location.href = "checkout.html?order=" + encodeURIComponent(o.id);
    });
  });
})();
