/* Confirmation — watches the order state machine through policy issuance, ERP sync and notifications */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui;
  var STEPS = [
    { id: "PAYMENT_SUCCESS", label: "Payment successful", sub: "Captured by payment gateway" },
    { id: "POLICY_REQUESTED", label: "Policy requested", sub: "Sent to insurer via adapter" },
    { id: "INSURANCE_PROCESSING", label: "Insurer processing", sub: "Underwriting & document checks" },
    { id: "POLICY_ISSUED", label: "Policy issued", sub: "Policy number received" },
    { id: "ERP", label: "ERP synchronisation", sub: "Customer, invoice, payment, policy" },
    { id: "NOTIFIED", label: "Customer notified", sub: "Email & SMS sent" },
  ];
  var reached = {}, erpState = null;

  function renderTimeline(activeId) {
    document.getElementById("timeline").innerHTML = STEPS.map(function (s) {
      var cls = reached[s.id] ? "done" : s.id === activeId ? "active" : "";
      var icon = reached[s.id] ? "fa-check" : s.id === activeId ? "fa-spinner-third" : "fa-circle-small";
      var sub = s.sub;
      if (s.id === "ERP" && erpState === "failed") { cls = "warn"; icon = "fa-triangle-exclamation"; sub = "ERP unavailable — queued for automatic retry. Your policy is NOT affected."; }
      if (s.id === "ERP" && erpState === "synced") sub = "Synced to ERP";
      var at = reached[s.id] ? " · " + new Date(reached[s.id]).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";
      return '<li class="' + cls + '"><span class="dot"><i class="far ' + icon + '"></i></span><b>' + s.label + "</b><small>" + sub + at + "</small></li>";
    }).join("");
  }

  function renderPolicy(order) {
    var pol = order.policyId && S.policy(order.policyId);
    var box = document.getElementById("policyBox");
    if (!pol) { box.innerHTML = '<div class="d-card"><h4 style="color:var(--ink);font-size:16px">Your policy</h4><p class="d-muted mb-0">Your policy details will appear here once issued.</p></div>'; return; }
    var p = S.provider(pol.providerId);
    box.innerHTML = '<div class="d-policy-card"><img src="images/brand/delta-glyph-white.svg" alt="" /><small>Policy number</small><b style="font-size:22px">' + pol.number + '</b><small>Insurer</small><b>' + p.name + " — " + pol.planName + '</b><div class="row"><div class="col-6"><small>Start</small><b>' + U.fmtDate(pol.startDate) + '</b></div><div class="col-6"><small>End</small><b>' + U.fmtDate(pol.endDate) + '</b></div></div><small>Vehicle</small><b style="margin:0">' + pol.vehicle + "</b></div>" +
      '<div class="d-flex gap-2 mt-3 flex-wrap"><a class="d-btn d-btn--primary d-btn--sm" href="policy-document.html?policy=' + pol.id + '" target="_blank"><i class="far fa-file-pdf"></i> Policy schedule</a><a class="d-btn d-btn--ghost d-btn--sm" href="portal.html#/policies/' + pol.id + '"><i class="far fa-user"></i> View in my account</a></div>';
  }

  function renderOrder(order) {
    var pays = S.get().payments.filter(function (p) { return p.orderId === order.id; });
    document.getElementById("orderBox").innerHTML = '<h4 style="color:var(--ink);font-size:16px;margin-bottom:12px">Order ' + order.id + "</h4>" +
      '<div class="d-kv"><span>Vehicle</span><b>' + order.vehicle + '</b></div><div class="d-kv"><span>Total paid</span><b>' + U.money(order.total) + "</b></div>" +
      pays.map(function (p) { return '<div class="d-kv"><span>Payment #' + p.attempt + " · " + p.cardBrand + " •••• " + p.last4 + "</span><b style=\"color:" + (p.status === "captured" ? "var(--ok)" : "var(--bad)") + '">' + p.status + "</b></div>"; }).join("") +
      '<div class="d-kv"><span>Commission (broker)</span><b>' + order.commissionRate + "%</b></div>";
  }

  document.addEventListener("DOMContentLoaded", function () {
    var order = S.order(U.qs("order") || (S.get().current || {}).orderId || "");
    if (!order) { document.getElementById("timeline").innerHTML = '<li>No order found. <a class="d-link" href="motor-quote.html">Start a quote</a></li>'; return; }
    renderOrder(order);
    (order.history || []).forEach(function (h) { if (STEPS.some(function (s) { return s.id === h.state; })) reached[h.state] = h.at; });

    if (order.state === "POLICY_ISSUED") {
      erpState = order.erp && order.erp.status;
      if (erpState) reached.ERP = erpState === "synced" ? order.erp.at : null;
      reached.NOTIFIED = order.updatedAt;
      done(order);
      return;
    }
    if (order.state !== "PAYMENT_SUCCESS") {
      document.getElementById("heroTitle").textContent = "Payment pending";
      document.getElementById("heroText").innerHTML = 'This order is not paid yet. <a class="d-link" href="payment.html?order=' + order.id + '">Go to payment</a>';
      renderTimeline(null); renderPolicy(order); return;
    }
    renderTimeline("POLICY_REQUESTED");
    renderPolicy(order);
    var nextOf = { POLICY_REQUESTED: "INSURANCE_PROCESSING", INSURANCE_PROCESSING: "POLICY_ISSUED", POLICY_ISSUED: "ERP" };
    S.issuePolicy(order.id, function (state, res) {
      if (state === "ERP_SYNCED" || state === "ERP_FAILED") { erpState = state === "ERP_SYNCED" ? "synced" : "failed"; if (erpState === "synced") reached.ERP = new Date().toISOString(); renderTimeline("NOTIFIED"); if (erpState === "failed") ui.toast("ERP sync failed — job queued for retry. Policy remains valid.", "err"); return; }
      if (state === "NOTIFIED") { reached.NOTIFIED = new Date().toISOString(); done(S.order(order.id)); ui.toast("Policy sent to " + order.email, "ok"); return; }
      reached[state] = new Date().toISOString();
      renderTimeline(nextOf[state]);
      if (state === "POLICY_ISSUED") { renderPolicy(S.order(order.id)); document.getElementById("heroTitle").textContent = "Your policy has been issued"; }
    });
  });

  function done(order) {
    renderTimeline(null);
    renderPolicy(order);
    renderOrder(order);
    var ic = document.getElementById("heroIc");
    ic.className = "ic"; ic.innerHTML = '<i class="fal fa-circle-check"></i>';
    document.getElementById("heroTitle").textContent = "You’re insured!";
    document.getElementById("heroText").innerHTML = "Your motor policy is active from <b style='color:var(--ink)'>" + U.fmtDate(S.policy(order.policyId).startDate) + "</b>. We have emailed your policy documents to " + U.esc(order.email) + ".";
  }
})();
