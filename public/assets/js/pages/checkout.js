/* Checkout — confirm policyholder contact, review captured vehicle & documents, declarations */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui;
  var order, qr, quote;

  function orderAside(o, q) {
    var p = S.provider(o.providerId);
    var addons = (q.addons || []).map(function (a) { return '<div class="d-kv"><span>+ ' + a.k + "</span><b>" + U.money(a.v) + "</b></div>"; }).join("");
    return '<div class="d-card d-card--glow"><div class="d-flex gap-3 align-items-center mb-15">' + ui.mono(o.providerId, "lg") + '<div><b style="color:var(--ink);font-size:17px;display:block">' + q.planName + '</b><small class="d-muted-2">' + p.name + "</small></div></div>" +
      '<div class="d-kv"><span>Vehicle</span><b>' + o.vehicle + '</b></div><div class="d-kv"><span>Cover</span><b>' + (q.insuranceType === "comprehensive" ? "Comprehensive" : "Third party") + (q.repairType ? " · " + (q.repairType === "agency" ? "Agency" : "Garage") : "") + "</b></div>" +
      (q.insuranceType === "comprehensive" ? '<div class="d-kv"><span>Deductible</span><b>' + U.money0(q.deductible) + "</b></div>" : "") +
      '<div class="d-divider" style="margin:14px 0"></div><div class="d-kv"><span>Premium</span><b>' + U.money(q.premium) + "</b></div>" + addons +
      '<div class="d-kv"><span>VAT 5%</span><b>' + U.money(q.vat) + '</b></div><div class="d-kv total"><span>Total to pay</span><b>' + U.money(q.total) + "</b></div>" +
      '<p class="d-muted-2 mt-10 mb-0" style="font-size:12.5px">Order ' + o.id + " · Quote " + q.id + " · valid until " + U.fmtDate(q.validUntil) + "</p></div>" +
      '<div class="d-note mt-3"><i class="fal fa-shield-check"></i><div>Your policy is issued by ' + p.name + ". Delta Insurance Services acts as your broker.</div></div>";
  }

  function captured(qr) {
    var c = qr.customer, v = qr.vehicle;
    function kv(k, x) { return '<div class="d-kv"><span>' + k + "</span><b>" + U.esc(x || "—") + "</b></div>"; }
    var docsHtml = (qr.documents || []).map(function (d) { return '<div class="d-kv"><span><i class="fas fa-circle-check" style="color:#00894f"></i> ' + U.esc(d.label) + "</span><b>" + U.esc(d.fileName) + "</b></div>"; }).join("") || '<p class="d-muted mb-0">No documents on file.</p>';
    return '<div class="d-card"><h5>Vehicle</h5>' + kv("Vehicle", v.year + " " + v.make + " " + v.model) + kv("Chassis", v.chassis) + kv("Plate", [v.regEmirate, v.plateCode, v.plate].join(" ")) + kv("Traffic file", v.tcfNumber) + kv("Emirates ID", c.emiratesId) + kv("Licence", c.licenseNo) + "</div>" +
      '<div class="d-card"><h5>Documents</h5>' + docsHtml + "</div>";
  }

  document.addEventListener("DOMContentLoaded", function () {
    var id = U.qs("order") || (S.get().current || {}).orderId;
    order = id && S.order(id);
    var form = document.getElementById("checkoutForm");
    if (!order) { form.innerHTML = '<div class="d-empty"><h3 class="d-h3">No offer selected</h3><p class="mt-10">Please choose an offer first.</p><a class="d-btn d-btn--primary" href="quote-results.html">View offers</a></div>'; return; }
    qr = S.quoteRequest(order.quoteRequestId);
    quote = S.quote(order.quoteId);
    document.getElementById("orderAside").innerHTML = orderAside(order, quote);
    document.getElementById("capturedBox").innerHTML = captured(qr);
    form.elements.name.value = qr.customer.name || "";
    form.elements.email.value = qr.customer.email || "";
    form.elements.mobile.value = String(qr.customer.mobile || "").replace("+971 ", "");
    form.elements.policyStart.value = qr.cover.policyStart || "";

    form.addEventListener("input", function (e) { e.target.classList.remove("is-invalid"); });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var ok = true;
      form.querySelectorAll("input[required], select[required]").forEach(function (el) {
        var bad = el.type === "checkbox" ? !el.checked : !String(el.value).trim();
        if (el.name === "emiratesId" && !bad) bad = !/^784-?\d{4}-?\d{7}-?\d$/.test(el.value.trim());
        if (el.name === "chassis" && !bad) bad = el.value.trim().length !== 17;
        el.classList.toggle("is-invalid", bad);
        if (bad) ok = false;
      });
      document.getElementById("declErr").style.display = ["d1", "d2", "d3"].every(function (n) { return form.elements[n].checked; }) ? "none" : "block";
      if (!ok) { ui.toast("Please complete the highlighted fields.", "err"); return; }
      var f = form.elements, v = qr.vehicle;
      S.completeCheckout(order.id, {
        name: f.name.value, email: f.email.value, mobile: "+971 " + f.mobile.value, emiratesId: qr.customer.emiratesId,
        plateEmirate: v.regEmirate, plateCode: v.plateCode, plate: v.plate, chassis: v.chassis,
        policyStart: f.policyStart.value, documents: qr.documents || [],
      });
      location.href = "payment.html?order=" + encodeURIComponent(order.id);
    });
  });
})();
