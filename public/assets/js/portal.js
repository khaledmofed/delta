/* Customer Portal */
(function (w, d) {
  "use strict";
  var D = w.DELTA, S = D.store, U = D.util, A = D.app;
  var me;

  function mine(k) { var s = S.get(); return s[k].filter(function (x) { return x.customerId === me.id; }); }
  function continueLink(o) {
    if (o.state === "PAYMENT_PENDING" || o.state === "PAYMENT_FAILED") return '<a class="btn-d sm acc" href="payment.html?order=' + o.id + '">Complete payment</a>';
    if (o.state === "QUOTE_SELECTED" || o.state === "DOCUMENTS_PENDING" || o.state === "CUSTOMER_DETAILS_PENDING") return '<a class="btn-d sm acc" href="checkout.html?order=' + o.id + '">Continue application</a>';
    if (o.policyId) return '<a class="btn-d sm" href="#/policies/' + o.policyId + '">View policy</a>';
    return "";
  }
  function daysLeft(p) { return Math.ceil((new Date(p.endDate) - Date.now()) / 86400000); }

  function policyCard(p) {
    var pr = S.provider(p.providerId), dl = daysLeft(p);
    return '<div class="pol-card"><img src="images/brand/delta-glyph-white.svg" alt="" /><div class="row-flex" style="justify-content:space-between"><small>Motor · ' + pr.name + "</small>" + (dl <= 30 ? '<span class="pill warn">Renews in ' + dl + " days</span>" : '<span class="pill ok">Active</span>') + "</div>" +
      '<b style="font-size:19px;display:block;margin:8px 0 2px">' + p.vehicle + "</b><div>" + p.planName + '</div><div class="row-flex mt-2" style="justify-content:space-between"><div><small>Policy no.</small><b>' + p.number + '</b></div><div><small>Expires</small><b>' + U.fmtDate(p.endDate) + "</b></div></div>" +
      '<div class="row-flex mt-2"><a class="btn-d sm" href="#/policies/' + p.id + '">Details</a><a class="btn-d sm ghost" style="color:#fff;border-color:rgba(255,255,255,.3)" target="_blank" href="policy-document.html?policy=' + p.id + '"><i class="far fa-file-pdf"></i> Schedule</a></div></div>';
  }

  var V = {
    dashboard: function () {
      var pols = mine("policies"), orders = mine("orders"), qrs = mine("quoteRequests");
      var open = orders.filter(function (o) { return ["POLICY_ISSUED", "QUOTE_EXPIRED", "CANCELLED", "REFUNDED"].indexOf(o.state) < 0; });
      var next = pols.slice().sort(function (a, b) { return new Date(a.endDate) - new Date(b.endDate); })[0];
      var notes = S.get().notifications.filter(function (n) { return n.to === me.email; }).slice(0, 6);
      return '<div class="card-d welcome"><img class="mk" src="images/brand/delta-glyph-white.svg" alt="" /><small style="opacity:.8">Welcome back</small><h2 class="disp">' + U.esc(me.name) + '</h2><p style="max-width:560px;margin:8px 0 14px">Manage your Delta policies, track applications and download documents. Need a different insurance? <a href="services.html" style="color:#fff;text-decoration:underline">Explore our services</a>.</p><div class="row-flex"><a class="btn-d sm" href="motor-quote.html"><i class="fal fa-car-side"></i> New motor quote</a><a class="btn-d sm ghost" style="color:#fff;border-color:rgba(255,255,255,.3)" href="contact.html"><i class="fal fa-headset"></i> Talk to an advisor</a></div></div>' +
        '<div class="grid-d g-4 mt-3">' +
        kpi("Active policies", pols.length, "fa-file-certificate", "Motor") + kpi("Open applications", open.length, "fa-bag-shopping", open.length ? "Action may be needed" : "All complete") +
        kpi("Quote requests", qrs.length, "fa-file-invoice", "Last: " + (qrs[0] ? U.fmtDate(qrs[0].createdAt) : "—")) + kpi("Next renewal", next ? daysLeft(next) + " days" : "—", "fa-calendar", next ? U.fmtDate(next.endDate) : "") + "</div>" +
        (open.length ? '<div class="card-d mt-3"><div class="hd"><h3>Action needed</h3></div>' + A.table([{ t: "Order", v: function (o) { return '<b>' + o.id + '</b><span class="sub">' + o.vehicle + "</span>"; } }, { t: "Insurer", v: function (o) { return A.prov(o.providerId); } }, { t: "Status", v: function (o) { return A.pill(o.state); } }, { t: "Total", num: 1, v: function (o) { return U.money(o.total); } }, { t: "", v: continueLink }], open) + "</div>" : "") +
        '<div class="grid-d g-21 mt-3"><div class="card-d"><div class="hd"><h3>My policies</h3><a href="#/policies">View all</a></div><div class="grid-d g-2">' + (pols.slice(0, 2).map(policyCard).join("") || '<div class="empty-d"><i class="fal fa-file-certificate"></i>No policies yet. <a href="motor-quote.html">Get a motor quote</a></div>') + "</div></div>" +
        '<div class="card-d"><div class="hd"><h3>Recent notifications</h3></div>' + (notes.map(function (n) { return '<div class="row-flex" style="padding:9px 0;border-bottom:1px solid var(--line)"><i class="fal ' + (n.channel === "sms" ? "fa-message-sms" : "fa-envelope") + '" style="color:var(--teal)"></i><div style="flex:1"><b style="font-size:13.5px">' + A.label(n.template) + '</b><span class="muted" style="display:block;font-size:12px">' + n.ref + " · " + U.ago(n.at) + "</span></div></div>"; }).join("") || '<p class="muted">No notifications.</p>') + "</div></div>" +
        '<div class="card-d mt-3"><div class="hd"><h3>Other insurance from Delta</h3><small>Advisor-assisted</small></div><div class="row-flex">' + S.products().filter(function (p) { return p.id !== "motor"; }).map(function (p) { return '<a class="btn-d" href="' + p.page + '#request"><i class="fal ' + p.icon + '"></i>' + p.name + "</a>"; }).join("") + "</div></div>";
    },
    quotes: function () {
      var qrs = mine("quoteRequests");
      return head("My quotes", "Quote requests and the offers you received.") + A.table([
        { t: "Request", v: function (q) { return "<b>" + q.id + '</b><span class="sub">' + U.fmtDate(q.createdAt, true) + "</span>"; } },
        { t: "Vehicle", v: function (q) { return q.vehicle.year + " " + q.vehicle.make + " " + q.vehicle.model; } },
        { t: "Cover", v: function (q) { return q.cover.type === "comprehensive" ? "Comprehensive" : "Third party"; } },
        { t: "Offers", v: function (q) { return (q.quoteIds || []).length; } },
        { t: "From", num: 1, v: function (q) { var l = S.quotesFor(q.id).map(function (x) { return x.total; }); return l.length ? U.money0(Math.min.apply(null, l)) : "—"; } },
        { t: "Status", v: function (q) { return A.pill(q.status); } },
        { t: "", v: function (q) { return q.status === "QUOTE_EXPIRED" ? '<a class="btn-d sm" href="motor-quote.html">Requote</a>' : '<a class="btn-d sm" href="quote-results.html?qr=' + q.id + '">View offers</a>'; } },
      ], qrs, { empty: 'No quotes yet. <a href="motor-quote.html">Get a motor quote</a>' });
    },
    orders: function (id) {
      if (id) setTimeout(function () { openOrder(id); }, 0);
      return head("My orders", "Applications and their status. Click a row for the full timeline.") + A.table([
        { t: "Order", v: function (o) { return "<b>" + o.id + '</b><span class="sub">' + U.fmtDate(o.createdAt) + "</span>"; } },
        { t: "Vehicle", v: function (o) { return o.vehicle; } }, { t: "Insurer", v: function (o) { return A.prov(o.providerId); } },
        { t: "Status", v: function (o) { return A.pill(o.state); } }, { t: "Total", num: 1, v: function (o) { return U.money(o.total); } }, { t: "", v: continueLink },
      ], mine("orders"), { click: function (o) { return o.id; } });
    },
    policies: function (id) {
      if (id) return policyDetail(id);
      var pols = mine("policies");
      return head("My policies", "Your active insurance policies.") + (pols.length ? '<div class="grid-d g-3">' + pols.map(policyCard).join("") + "</div>" : '<div class="card-d empty-d"><i class="fal fa-file-certificate"></i>No policies yet.</div>');
    },
    vehicles: function () {
      return head("My vehicles", "Vehicles linked to your account.") + A.table([
        { t: "Vehicle", v: function (v) { return "<b>" + v.year + " " + v.make + " " + v.model + "</b>"; } }, { t: "Plate", v: function (v) { return (v.emirate || "") + " " + (v.plateCode || "") + " " + (v.plate || ""); } },
        { t: "Chassis", v: function (v) { return '<span class="mono">' + (v.chassis || "—") + "</span>"; } }, { t: "Value", num: 1, v: function (v) { return U.money0(v.value); } },
        { t: "Insured", v: function (v) { var p = mine("policies").filter(function (x) { return x.vehicleId === v.id; })[0]; return p ? A.pill("active", "Insured · " + S.provider(p.providerId).short) : A.pill("not_started", "Not insured"); } },
      ], mine("vehicles"));
    },
    documents: function () {
      return head("Documents", "Stored securely. Links are time-limited in production.") + A.table([
        { t: "Document", v: function (x) { return '<b><i class="fal ' + (x.type === "policy" ? "fa-file-certificate" : "fa-file-lines") + '" style="color:var(--teal);margin-right:6px"></i>' + x.label + '</b><span class="sub">' + x.fileName + "</span>"; } },
        { t: "Order", v: function (x) { return x.orderId; } }, { t: "Uploaded", v: function (x) { return U.fmtDate(x.uploadedAt); } }, { t: "Status", v: function (x) { return A.pill(x.status); } },
        { t: "", v: function (x) { var o = S.order(x.orderId); return x.type === "policy" && o && o.policyId ? '<a class="btn-d sm" target="_blank" href="policy-document.html?policy=' + o.policyId + '">Open</a>' : '<button class="btn-d sm" data-toast="Secure download link generated (expires in 5 minutes)">Download</button>'; } },
      ], mine("documents"));
    },
    payments: function () {
      var ids = mine("orders").map(function (o) { return o.id; });
      var pays = S.get().payments.filter(function (p) { return ids.indexOf(p.orderId) > -1; });
      return head("Payments", "Every payment attempt is recorded separately.") + A.table([
        { t: "Payment", v: function (p) { return "<b>" + p.id + '</b><span class="sub">Attempt #' + p.attempt + "</span>"; } }, { t: "Order", v: function (p) { return p.orderId; } },
        { t: "Card", v: function (p) { return p.cardBrand + " •••• " + p.last4; } }, { t: "Date", v: function (p) { return U.fmtDate(p.at, true); } },
        { t: "Status", v: function (p) { return A.pill(p.status) + (p.reason ? '<span class="sub">' + p.reason + "</span>" : ""); } }, { t: "Amount", num: 1, v: function (p) { return U.money(p.amount); } },
      ], pays);
    },
    profile: function () {
      return head("Profile", "Keep your details up to date.") + '<div class="grid-d g-2"><div class="card-d"><div class="hd"><h3>Personal details</h3></div><div class="kvx">' +
        kv("Full name", me.name) + kv("Email", me.email) + kv("Mobile", me.mobile) + kv("Emirates ID", me.emiratesId) + kv("Nationality", me.nationality) + kv("Emirate", me.emirate) + kv("Date of birth", U.fmtDate(me.dob)) + kv("Customer ID", me.id) +
        '</div></div><div class="card-d"><div class="hd"><h3>Security &amp; preferences</h3></div><div class="grid-d"><label class="row-flex"><span class="tgl"><input type="checkbox" checked /><i></i></span> Two-step verification (SMS code)</label><label class="row-flex"><span class="tgl"><input type="checkbox" checked /><i></i></span> Renewal reminders by email</label><label class="row-flex"><span class="tgl"><input type="checkbox" checked /><i></i></span> Policy updates by SMS / WhatsApp</label><label class="row-flex"><span class="tgl"><input type="checkbox" /><i></i></span> Offers for other Delta insurance products</label></div><div class="row-flex mt-3"><button class="btn-d" data-toast="Password reset link sent (demo)">Change password</button><button class="btn-d danger" id="logoutBtn">Sign out</button></div></div></div>';
    },
  };
  var TITLES = { dashboard: "Dashboard", quotes: "My Quotes", orders: "My Orders", policies: "My Policies", vehicles: "My Vehicles", documents: "Documents", payments: "Payments", profile: "Profile" };

  function head(t, p) { return '<div class="sec-h"><div><h2>' + t + "</h2><p>" + p + "</p></div></div>"; }
  function kpi(t, v, ic, s) { return '<div class="card-d kpi"><i class="fal ' + ic + ' ic"></i><small>' + t + "</small><b>" + v + "</b><span>" + (s || "") + "</span></div>"; }
  function kv(k, v) { return "<div><small>" + k + "</small><b>" + U.esc(v || "—") + "</b></div>"; }

  function policyDetail(id) {
    var p = S.policy(id);
    if (!p || p.customerId !== me.id) return '<div class="card-d empty-d">Policy not found.</div>';
    var o = S.order(p.orderId), q = S.quote(o.quoteId) || { benefits: {} }, pr = S.provider(p.providerId);
    var b = q.benefits || {};
    function yn(x) { return x ? '<i class="fas fa-check" style="color:var(--ok)"></i> Included' : '<span class="muted">Not included</span>'; }
    return '<a href="#/policies" class="muted"><i class="far fa-arrow-left"></i> All policies</a><div class="grid-d g-21 mt-2"><div class="grid-d">' +
      '<div class="card-d"><div class="hd"><div class="who">' + A.mono(p.providerId, 1) + '<div><h3 style="font-size:18px">' + p.planName + '</h3><small>' + pr.name + " · " + p.number + "</small></div></div>" + A.pill("active") + '</div><div class="kvx">' +
      kv("Vehicle", p.vehicle) + kv("Cover", p.insuranceType === "comprehensive" ? "Comprehensive" : "Third party") + kv("Repair", p.repairType ? (p.repairType === "agency" ? "Agency" : "Garage") : "—") + kv("Deductible", p.insuranceType === "comprehensive" ? U.money0(q.deductible) : "—") + kv("Start", U.fmtDate(p.startDate)) + kv("End", U.fmtDate(p.endDate)) + kv("Premium incl. VAT", U.money(p.total)) + kv("Issued", U.fmtDate(p.issuedAt, true)) + "</div></div>" +
      '<div class="card-d"><div class="hd"><h3>Benefits</h3></div><div class="kvx"><div><small>Roadside assistance</small><b>' + yn(b.roadside) + '</b></div><div><small>Rent-a-car</small><b>' + yn(b.rentACar) + '</b></div><div><small>Oman cover</small><b>' + yn(b.oman) + '</b></div><div><small>GCC cover</small><b>' + yn(b.gcc) + '</b></div><div><small>Personal accident</small><b>' + yn(b.pa) + '</b></div><div><small>Windscreen</small><b>' + (b.windscreen ? U.money0(b.windscreen) : "—") + "</b></div></div></div>" +
      '<div class="card-d"><div class="hd"><h3>Application timeline</h3><small>' + o.id + "</small></div>" + A.stateMachine(o) + "</div></div>" +
      '<div class="grid-d" style="align-content:start"><div class="card-d"><div class="hd"><h3>Actions</h3></div><div class="grid-d"><a class="btn-d pri" target="_blank" href="policy-document.html?policy=' + p.id + '"><i class="far fa-file-pdf"></i> Download policy schedule</a><button class="btn-d" data-toast="Claim request sent to your Delta advisor (demo)"><i class="far fa-car-burst"></i> Report a claim</button><button class="btn-d" data-toast="Renewal quote will be requested 30 days before expiry (demo)"><i class="far fa-rotate"></i> Renew policy</button><button class="btn-d" data-toast="Your advisor will contact you (demo)"><i class="far fa-headset"></i> Request a change</button></div></div>' +
      '<div class="note-d"><i class="fal fa-circle-info"></i><div>Your policy is issued by ' + pr.name + ". Delta Insurance Services is your broker and will help you with claims and renewals.</div></div></div></div>";
  }

  function openOrder(id) {
    var o = S.order(id); if (!o) return;
    var pays = S.get().payments.filter(function (p) { return p.orderId === id; });
    A.drawer('<div class="hd"><h3>' + o.id + "</h3>" + A.pill(o.state) + '<button class="btn-d sm ghost" style="margin-left:auto" data-close><i class="far fa-xmark"></i></button></div><div class="bd">' +
      '<div class="card-d"><div class="kvx">' + kv("Vehicle", o.vehicle) + kv("Insurer", S.provider(o.providerId).name) + kv("Total", U.money(o.total)) + kv("Created", U.fmtDate(o.createdAt, true)) + '</div><div class="row-flex mt-3">' + continueLink(o) + "</div></div>" +
      '<div class="card-d"><div class="hd"><h3>Status timeline</h3></div>' + A.stateMachine(o) + "</div>" +
      (pays.length ? '<div class="card-d"><div class="hd"><h3>Payment attempts</h3></div>' + pays.map(function (p) { return '<div class="row-flex" style="justify-content:space-between;padding:6px 0"><span>#' + p.attempt + " · " + p.cardBrand + " •••• " + p.last4 + " · " + U.fmtDate(p.at, true) + "</span>" + A.pill(p.status) + "</div>"; }).join("") + "</div>" : "") + "</div>");
  }

  function gate() {
    d.getElementById("view").innerHTML = '<div class="card-d empty-d" style="max-width:520px;margin:40px auto"><i class="fal fa-lock"></i><h3>Sign in to your account</h3><p>View your quotes, orders and policies.</p><div class="row-flex" style="justify-content:center"><a class="btn-d pri" href="login.html">Sign in</a><button class="btn-d" id="demoLogin">Demo: continue as Omar</button></div></div>';
    d.getElementById("demoLogin").onclick = function () { S.login("omar@example.com"); location.reload(); };
  }

  d.addEventListener("DOMContentLoaded", function () {
    me = S.me();
    if (!me) { gate(); return; }
    d.getElementById("meBox").innerHTML = '<span class="av">' + me.name.split(" ").map(function (x) { return x[0]; }).slice(0, 2).join("") + '</span><div><b>' + U.esc(me.name) + '</b><span style="color:#7fa3bd">' + me.email + "</span></div>";
    var openCnt = mine("orders").filter(function (o) { return ["PAYMENT_PENDING", "PAYMENT_FAILED", "DOCUMENTS_PENDING", "QUOTE_SELECTED"].indexOf(o.state) > -1; }).length;
    if (openCnt) { var c = d.querySelector('[data-cnt="orders"]'); c.textContent = openCnt; c.classList.remove("d-none"); c.classList.add("alert"); }
    A.router(V, TITLES, "dashboard");
    d.getElementById("view").addEventListener("click", function (e) {
      var r = e.target.closest("tr[data-row]"); if (r && !e.target.closest("a,button")) openOrder(r.getAttribute("data-row"));
    });
    d.addEventListener("click", function (e) {
      var t = e.target.closest("[data-toast]"); if (t) A.toast(t.getAttribute("data-toast"));
      if (e.target.closest("#logoutBtn")) { S.logout(); location.href = "index.html"; }
    });
  });
})(window, document);
