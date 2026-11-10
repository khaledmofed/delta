/* Admin Console — operations, insurance products & providers, finance, ERP, security */
(function (w, d) {
  "use strict";
  var D = w.DELTA, S = D.store, U = D.util, A = D.app;
  var ACTOR = "Super Admin";
  var st = function () { return S.get(); };
  var filt = {};

  function head(t, p, actions) { return '<div class="sec-h"><div><h2>' + t + "</h2><p>" + p + '</p></div><div class="row-flex">' + (actions || "") + "</div></div>"; }
  function kpi(t, v, ic, s) { return '<div class="card-d kpi"><i class="fal ' + ic + ' ic"></i><small>' + t + "</small><b>" + v + "</b><span>" + (s || "") + "</span></div>"; }
  function kv(k, v) { return "<div><small>" + k + "</small><b>" + (v == null || v === "" ? "—" : v) + "</b></div>"; }
  function sum(a, f) { return a.reduce(function (x, y) { return x + (f ? f(y) : y); }, 0); }
  function customer(id) { return st().customers.filter(function (c) { return c.id === id; })[0]; }
  function bars(rows, fmt) {
    var max = Math.max.apply(null, rows.map(function (r) { return r[1]; }).concat([1]));
    return '<div class="bars">' + rows.map(function (r) { return '<div class="bar-row" title="' + U.esc(r[0]) + ": " + fmt(r[1]) + '"><span>' + r[0] + '</span><div class="track"><div class="fill" style="width:' + (r[1] / max * 100).toFixed(1) + '%"></div></div><span class="v">' + fmt(r[1]) + "</span></div>"; }).join("") + "</div>";
  }
  function selectOpts(list, cur) { return list.map(function (o) { var v = o[0], l = o[1]; return '<option value="' + v + '"' + (v === cur ? " selected" : "") + ">" + l + "</option>"; }).join(""); }
  function csv(name, rows) {
    var text = rows.map(function (r) { return r.map(function (c) { return '"' + String(c == null ? "" : c).replace(/"/g, '""') + '"'; }).join(","); }).join("\n");
    var a = d.createElement("a"); a.href = URL.createObjectURL(new Blob([text], { type: "text/csv" })); a.download = name; a.click();
  }

  /* ------------------------------ views ------------------------------ */
  var V = {};

  V.dashboard = function () {
    var s = st();
    var pols = s.policies, qrs = s.quoteRequests, orders = s.orders;
    var gwp = sum(pols, function (p) { return p.premium; });
    var comm = sum(s.commissions, function (c) { return c.amount; });
    var conv = qrs.length ? Math.round(pols.length / qrs.length * 100) : 0;
    var erpFail = s.erpSync.filter(function (e) { return e.status === "failed"; }).length;
    var payFail = orders.filter(function (o) { return o.state === "PAYMENT_FAILED"; }).length;
    var docsPending = s.documents.filter(function (x) { return x.status === "pending_review"; }).length;
    var byProv = S.providers().filter(function (p) { return p.products.length; }).map(function (p) { return [p.short, sum(pols.filter(function (x) { return x.providerId === p.id; }), function (x) { return x.premium; })]; });
    // quote requests last 14 days
    var days = [], today = new Date(); today.setHours(0, 0, 0, 0);
    for (var i = 13; i >= 0; i--) { var dd = U.addDays(today, -i); days.push({ d: dd, n: qrs.filter(function (q) { var c = new Date(q.createdAt); c.setHours(0, 0, 0, 0); return c.getTime() === dd.getTime(); }).length }); }
    var mx = Math.max.apply(null, days.map(function (x) { return x.n; }).concat([1]));
    var funnel = [["Quote requests", qrs.length], ["Quotes received", qrs.filter(function (q) { return (q.quoteIds || []).length; }).length], ["Offer selected", orders.length], ["Paid", orders.filter(function (o) { return (o.history || []).some(function (h) { return h.state === "PAYMENT_SUCCESS"; }); }).length], ["Policy issued", pols.length]];
    var attention = [];
    s.erpSync.filter(function (e) { return e.status === "failed"; }).slice(0, 3).forEach(function (e) { attention.push(["bad", "ERP sync failed — " + e.entity + " for " + e.orderId, "#/erp"]); });
    orders.filter(function (o) { return o.state === "PAYMENT_FAILED"; }).forEach(function (o) { attention.push(["bad", "Payment failed — " + o.id + " (" + o.customerName + ")", "#/orders/" + o.id]); });
    orders.filter(function (o) { return o.state === "INSURANCE_PROCESSING"; }).forEach(function (o) { attention.push(["warn", "Awaiting insurer — " + o.id, "#/orders/" + o.id]); });
    if (docsPending) attention.push(["warn", docsPending + " documents pending review", "#/documents"]);
    s.leads.filter(function (l) { return l.status === "new"; }).forEach(function (l) { attention.push(["info", "New lead — " + A.label(l.product) + " · " + l.name, "#/leads"]); });
    return head("Operations overview", "Phase 1 · Motor Insurance digital platform", '<span class="muted">Updated ' + new Date().toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" }) + "</span>") +
      '<div class="grid-d g-4">' + kpi("Gross written premium", U.money0(gwp), "fa-sack-dollar", pols.length + " policies") + kpi("Commission earned", U.money0(comm), "fa-percent", "Accrued + received") +
      kpi("Quote → policy", conv + "%", "fa-arrow-trend-up", qrs.length + " quote requests") + kpi("Needs attention", erpFail + payFail + docsPending, "fa-triangle-exclamation", erpFail + " ERP · " + payFail + " payment · " + docsPending + " docs") + "</div>" +
      '<div class="grid-d g-21 mt-3"><div class="card-d"><div class="hd"><h3>Quote requests · last 14 days</h3><small>Motor</small></div><div class="cols">' + days.map(function (x) { return '<div class="c"><span class="tt">' + U.fmtDate(x.d) + ": " + x.n + '</span><div class="b" style="height:' + (x.n / mx * 100) + '%"></div></div>'; }).join("") + '</div><div class="cols-x">' + days.map(function (x, i) { return "<span>" + (i % 2 ? "" : x.d.getDate()) + "</span>"; }).join("") + "</div></div>" +
      '<div class="card-d"><div class="hd"><h3>Needs attention</h3></div>' + (attention.slice(0, 7).map(function (a) { return '<a href="' + a[2] + '" class="row-flex" style="padding:9px 0;border-bottom:1px solid var(--line);color:var(--ink)"><span class="pill ' + a[0] + '" style="padding:3px 6px"></span><span style="flex:1;font-size:13.5px">' + a[1] + '</span><i class="far fa-angle-right muted"></i></a>'; }).join("") || '<p class="muted">All clear.</p>') + "</div></div>" +
      '<div class="grid-d g-2 mt-3"><div class="card-d"><div class="hd"><h3>Premium by insurer</h3><small>AED, excl. VAT</small></div>' + bars(byProv, U.money0) + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Conversion funnel</h3><small>Motor</small></div><div class="fun">' + funnel.map(function (f) { return '<div class="st"><span>' + f[0] + '</span><div class="t"><i style="width:' + (f[1] / (funnel[0][1] || 1) * 100) + '%"></i></div><b style="text-align:right">' + f[1] + "</b></div>"; }).join("") + "</div></div></div>" +
      '<div class="grid-d g-2 mt-3"><div class="card-d"><div class="hd"><h3>Integration health</h3><a href="#/integrations">Details</a></div>' + healthTable(true) + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Products &amp; digital capabilities</h3><a href="#/products">Manage</a></div>' + S.products().map(function (p) { var on = Object.keys(p.capabilities).filter(function (k) { return p.capabilities[k]; }).length; return '<div class="row-flex" style="padding:8px 0;border-bottom:1px solid var(--line)"><i class="fal ' + p.icon + '" style="width:20px;color:var(--teal)"></i><b style="flex:1;font-size:13.5px">' + p.name + "</b>" + (on ? A.pill("active", "Digital · " + on + "/5") : A.pill("not_started", "Informational + leads")) + "</div>"; }).join("") + "</div></div>";
  };

  function healthTable(compact) {
    var rows = S.providers().map(function (p) { return { name: p.name, id: p.id, type: "Insurer API", h: p.enabled ? p.health : "not_connected", ms: p.avgResponseMs, sr: p.successRate, env: p.environment }; });
    rows.push({ name: "ERP (Finance)", type: "ERP", h: st().erpSync.some(function (e) { return e.status === "failed"; }) ? "degraded" : "healthy", ms: 640, sr: 97.2, env: "production" });
    rows.push({ name: "Payment gateway", type: "Payments", h: "healthy", ms: 410, sr: 99.6, env: "sandbox" });
    rows.push({ name: "Email / SMS", type: "Notifications", h: "healthy", ms: 220, sr: 99.9, env: "production" });
    return A.table([
      { t: "Service", v: function (r) { return r.id ? A.prov(r.id) : "<b>" + r.name + "</b>"; } },
      { t: compact ? "Status" : "Type", v: function (r) { return compact ? A.pill(r.h) : r.type; } },
      { t: "Avg response", num: 1, v: function (r) { return r.ms ? r.ms + " ms" : "—"; } },
      { t: "Success", num: 1, v: function (r) { return r.sr ? r.sr + "%" : "—"; } },
    ].concat(compact ? [] : [{ t: "Environment", v: function (r) { return '<span class="tagx">' + r.env + "</span>"; } }, { t: "Status", v: function (r) { return A.pill(r.h); } }]), rows);
  }

  V.products = function () {
    var caps = D.CAPABILITY_LABELS;
    return head("Insurance products", "Every product shares the same engine. Capabilities decide what is digital today.", '<button class="btn-d" data-toast="New products are added by configuration + provider adapters (demo)"><i class="far fa-plus"></i> Add product</button>') +
      '<div class="note-d mb-3" style="margin-bottom:16px"><i class="fal fa-diagram-project"></i><div><b>Architecture:</b> Insurance Product → Capabilities → Providers → Provider Adapter → Quote → Order → Policy. Motor is simply the first product with all capabilities enabled; Medical, Travel, etc. become digital by connecting providers and switching capabilities on — no new core logic.</div></div>' +
      A.table([
        { t: "Product", v: function (p) { return '<span class="who"><i class="fal ' + p.icon + '" style="font-size:18px;color:var(--teal);width:22px"></i><span><b>' + p.name + '</b><span class="sub">' + p.id + "</span></span></span>"; } },
        { t: "Status", v: function (p) { return '<select class="sel" data-prod-status="' + p.id + '">' + selectOpts([["active", "Active"], ["inactive", "Inactive"], ["coming_soon", "Coming soon"]], p.status) + "</select>"; } },
        { t: "Capabilities (click to toggle)", v: function (p) { return '<div class="cap-grid">' + Object.keys(caps).map(function (k) { return '<span class="cap ' + (p.capabilities[k] ? "on" : "off") + '" data-cap="' + p.id + "|" + k + '"><i class="fas ' + (p.capabilities[k] ? "fa-check" : "fa-minus") + '"></i>' + caps[k] + "</span>"; }).join("") + "</div>"; } },
        { t: "Providers", v: function (p) { return p.providers.length ? p.providers.map(function (x) { return A.mono(x); }).join(" ") : '<span class="muted">None connected</span>'; } },
        { t: "Website CTA", v: function (p) { return p.capabilities.onlineQuote ? '<span class="tagx" style="background:var(--ok-bg);color:var(--ok)">Get a quote (online)</span>' : '<span class="tagx">Request a quote (lead)</span>'; } },
      ], S.products());
  };

  V.providers = function () {
    var ps = S.providers();
    return head("Insurance providers", "Each insurer is connected through its own adapter. Adding one does not change the core.", '<button class="btn-d pri" id="addProv"><i class="far fa-plus"></i> Add provider</button>') +
      '<div class="grid-d g-2">' + ps.map(function (p) {
        return '<div class="card-d"><div class="hd"><div class="who">' + A.mono(p.id, 1) + '<div><h3 style="font-size:16px">' + p.name + '</h3><small>' + (p.fullName || p.hq) + "</small></div></div>" + '<label class="tgl" title="Enable / disable"><input type="checkbox" data-prov-enable="' + p.id + '" ' + (p.enabled ? "checked" : "") + (p.products.length ? "" : " disabled") + " /><i></i></label></div>" +
          '<div class="kvx">' + kv("Adapter", '<span class="mono">' + p.adapter + "</span>") + kv("Protocol", p.protocol) + kv("Auth", p.auth) + kv("Environment", '<span class="tagx">' + p.environment + "</span>") + kv("Products", p.products.map(function (x) { return A.label(x); }).join(", ") || "—") + kv("Commission", p.commission ? p.commission + "%" : "—") + kv("Timeout / retries", p.timeoutMs / 1000 + "s / " + p.retries) + kv("Health", A.pill(p.enabled ? p.health : "not_connected")) + "</div>" +
          (p.note ? '<div class="note-d warn mt-2"><i class="fal fa-circle-info"></i><div>' + p.note + "</div></div>" : "") +
          '<div class="row-flex mt-3"><button class="btn-d sm" data-test="' + p.id + '"><i class="far fa-plug"></i> Test connection</button><button class="btn-d sm" data-edit-prov="' + p.id + '"><i class="far fa-pen"></i> Configure</button><a class="btn-d sm ghost" href="#/integrations">Logs</a></div></div>';
      }).join("") + "</div>";
  };

  V["quote-requests"] = function (id) {
    if (id) setTimeout(function () { openQR(id); }, 0);
    var list = st().quoteRequests;
    return head("Quote requests", "Each request fans out to all enabled providers in parallel.") + A.table([
      { t: "Request", v: function (q) { return "<b>" + q.id + '</b><span class="sub">' + U.fmtDate(q.createdAt, true) + "</span>"; } },
      { t: "Customer", v: function (q) { return U.esc(q.customer.name) + '<span class="sub">' + q.customer.email + "</span>"; } },
      { t: "Vehicle", v: function (q) { return q.vehicle.year + " " + q.vehicle.make + " " + q.vehicle.model + '<span class="sub">' + U.money0(q.vehicle.value) + "</span>"; } },
      { t: "Providers", v: function (q) { return Object.keys(q.providers || {}).map(function (k) { var r = q.providers[k]; return '<span title="' + k + ": " + r.status + '" style="opacity:' + (r.status === "success" ? 1 : 0.35) + '">' + A.mono(k) + "</span>"; }).join(" "); } },
      { t: "Offers", num: 1, v: function (q) { return (q.quoteIds || []).length; } },
      { t: "Status", v: function (q) { return A.pill(q.status); } },
    ], list, { click: function (q) { return "qr:" + q.id; } });
  };

  V.quotes = function () {
    var list = st().quotes.slice().sort(function (a, b) { return new Date(b.createdAt) - new Date(a.createdAt); });
    if (filt.qprov) list = list.filter(function (q) { return q.providerId === filt.qprov; });
    return head("Quotes", "Normalised offers returned by provider adapters (internal quote standard).") +
      '<div class="toolbar"><select class="sel" data-filter="qprov"><option value="">All providers</option>' + selectOpts(S.providers().filter(function (p) { return p.products.length; }).map(function (p) { return [p.id, p.name]; }), filt.qprov) + '</select><span class="sp"></span><span class="muted">' + list.length + " quotes</span></div>" +
      A.table([
        { t: "Quote", v: function (q) { return '<span class="mono">' + q.id + '</span><span class="sub">' + q.quoteRequestId + "</span>"; } },
        { t: "Provider", v: function (q) { return A.prov(q.providerId); } }, { t: "Plan", v: function (q) { return q.planName + '<span class="sub">' + (q.insuranceType === "comprehensive" ? "Comp." : "TPL") + (q.repairType ? " · " + q.repairType : "") + "</span>"; } },
        { t: "Deductible", num: 1, v: function (q) { return q.deductible ? U.money0(q.deductible) : "—"; } }, { t: "Premium", num: 1, v: function (q) { return U.money(q.premium + q.addonTotal); } }, { t: "VAT", num: 1, v: function (q) { return U.money(q.vat); } }, { t: "Total", num: 1, v: function (q) { return "<b>" + U.money(q.total) + "</b>"; } },
        { t: "Valid until", v: function (q) { return new Date(q.validUntil) < new Date() ? A.pill("expired") : U.fmtDate(q.validUntil); } },
      ], list.slice(0, 80));
  };

  V.orders = function (id) {
    if (id) setTimeout(function () { openOrder(id); }, 0);
    var list = st().orders;
    if (filt.ostate) list = list.filter(function (o) { return o.state === filt.ostate; });
    var states = D.ORDER_STATES.map(function (s) { return [s.id, s.label]; }).concat(D.ORDER_ALT_STATES.map(function (s) { return [s, A.label(s)]; }));
    return head("Orders", "Order ≠ Quote ≠ Policy. Click an order to see its state machine.", '<button class="btn-d" id="exportOrders"><i class="far fa-download"></i> Export CSV</button>') +
      '<div class="toolbar"><select class="sel" data-filter="ostate"><option value="">All states</option>' + selectOpts(states, filt.ostate) + '</select><span class="sp"></span><span class="muted">' + list.length + " orders</span></div>" +
      A.table([
        { t: "Order", v: function (o) { return "<b>" + o.id + '</b><span class="sub">' + U.fmtDate(o.createdAt, true) + "</span>"; } },
        { t: "Customer", v: function (o) { return U.esc(o.customerName) + '<span class="sub">' + o.vehicle + "</span>"; } },
        { t: "Insurer", v: function (o) { return A.prov(o.providerId); } }, { t: "State", v: function (o) { return A.pill(o.state); } },
        { t: "ERP", v: function (o) { return A.pill(o.erp ? o.erp.status : "not_started"); } }, { t: "Total", num: 1, v: function (o) { return U.money(o.total); } },
      ], list, { click: function (o) { return "order:" + o.id; } });
  };

  V.policies = function () {
    return head("Policies", "Issued policies. A policy stays valid even if ERP sync fails.") + A.table([
      { t: "Policy", v: function (p) { return "<b>" + p.number + '</b><span class="sub">' + p.id + "</span>"; } },
      { t: "Customer", v: function (p) { return U.esc(p.customerName) + '<span class="sub">' + p.vehicle + "</span>"; } },
      { t: "Insurer", v: function (p) { return A.prov(p.providerId); } }, { t: "Plan", v: function (p) { return p.planName; } },
      { t: "Period", v: function (p) { return U.fmtDate(p.startDate) + '<span class="sub">to ' + U.fmtDate(p.endDate) + "</span>"; } },
      { t: "Premium", num: 1, v: function (p) { return U.money(p.premium); } }, { t: "Status", v: function (p) { return A.pill(p.status); } }, { t: "ERP", v: function (p) { return A.pill(p.erp ? p.erp.status : "pending"); } },
      { t: "", v: function (p) { return '<a class="btn-d sm" target="_blank" href="policy-document.html?policy=' + p.id + '">Schedule</a>'; } },
    ], st().policies);
  };

  V.customers = function () {
    var s = st();
    return head("Customers", "Created automatically at checkout. PII access is audit-logged.") + A.table([
      { t: "Customer", v: function (c) { return "<b>" + U.esc(c.name) + '</b><span class="sub">' + c.id + "</span>"; } },
      { t: "Contact", v: function (c) { return c.email + '<span class="sub">' + (c.mobile || "") + "</span>"; } },
      { t: "Emirate", v: function (c) { return c.emirate || "—"; } }, { t: "Nationality", v: function (c) { return c.nationality || "—"; } },
      { t: "Policies", num: 1, v: function (c) { return s.policies.filter(function (p) { return p.customerId === c.id; }).length; } },
      { t: "Orders", num: 1, v: function (c) { return s.orders.filter(function (o) { return o.customerId === c.id; }).length; } },
      { t: "Since", v: function (c) { return U.fmtDate(c.createdAt); } },
    ], s.customers, { click: function (c) { return "cust:" + c.id; } });
  };

  V.leads = function () {
    return head("Leads & consultation requests", "Requests for advisor-assisted products (Medical, Home, Travel, Boat, Pet) and general enquiries.") + A.table([
      { t: "Lead", v: function (l) { return "<b>" + l.id + '</b><span class="sub">' + U.fmtDate(l.at, true) + "</span>"; } },
      { t: "Product", v: function (l) { var p = S.product(l.product); return p ? '<span class="who"><i class="fal ' + p.icon + '" style="color:var(--teal)"></i>' + p.name + "</span>" : A.label(l.product); } },
      { t: "Name", v: function (l) { return U.esc(l.name) + '<span class="sub">' + U.esc(l.mobile || "") + " · " + U.esc(l.email || "") + "</span>"; } },
      { t: "Request", v: function (l) { return '<span style="max-width:280px;display:inline-block">' + U.esc(l.message || "—") + "</span>"; } },
      { t: "Assignee", v: function (l) { return '<select class="sel" data-lead-assign="' + l.id + '">' + selectOpts([["", "Unassigned"], ["Rania (Sales)", "Rania (Sales)"], ["Imran (Sales)", "Imran (Sales)"]], l.assignee || "") + "</select>"; } },
      { t: "Status", v: function (l) { return '<select class="sel" data-lead-status="' + l.id + '">' + selectOpts([["new", "New"], ["contacted", "Contacted"], ["quoted", "Quoted"], ["won", "Won"], ["lost", "Lost"]], l.status) + "</select>"; } },
    ], st().leads);
  };

  V.documents = function () {
    return head("Documents", "Private storage. Access is permission-based, time-limited and logged.") + A.table([
      { t: "Document", v: function (x) { return "<b>" + x.label + '</b><span class="sub mono">' + x.storage + "</span>"; } },
      { t: "Order", v: function (x) { return x.orderId; } }, { t: "Customer", v: function (x) { var c = customer(x.customerId); return c ? U.esc(c.name) : "—"; } },
      { t: "Uploaded", v: function (x) { return U.fmtDate(x.uploadedAt, true); } }, { t: "Status", v: function (x) { return A.pill(x.status); } },
      { t: "", v: function (x) { return x.status === "pending_review" ? '<div class="row-flex"><button class="btn-d sm" data-doc-verify="' + x.id + '">Verify</button><button class="btn-d sm danger" data-doc-reject="' + x.id + '">Reject</button></div>' : '<button class="btn-d sm ghost" data-toast="Signed URL generated · expires in 5 min · access logged">View</button>'; } },
    ], st().documents.slice(0, 120));
  };

  V.payments = function () {
    var p = st().payments;
    return head("Payments", "Payment is separate from Order — one order can have several attempts. No card data is stored.") +
      '<div class="grid-d g-3" style="margin-bottom:16px">' + kpi("Captured", U.money0(sum(p.filter(function (x) { return x.status === "captured"; }), function (x) { return x.amount; })), "fa-circle-check", p.filter(function (x) { return x.status === "captured"; }).length + " payments") + kpi("Declined attempts", p.filter(function (x) { return x.status === "declined"; }).length, "fa-circle-xmark", "Retries allowed") + kpi("Success rate", p.length ? Math.round(p.filter(function (x) { return x.status === "captured"; }).length / p.length * 100) + "%" : "—", "fa-chart-line", "All attempts") + "</div>" +
      A.table([
        { t: "Payment", v: function (x) { return "<b>" + x.id + '</b><span class="sub">Attempt #' + x.attempt + "</span>"; } }, { t: "Order", v: function (x) { return '<a href="#/orders/' + x.orderId + '">' + x.orderId + "</a>"; } },
        { t: "Method", v: function (x) { return x.cardBrand + " •••• " + x.last4; } }, { t: "Gateway ref", v: function (x) { return '<span class="mono">' + x.gatewayRef + "</span>"; } },
        { t: "Date", v: function (x) { return U.fmtDate(x.at, true); } }, { t: "Status", v: function (x) { return A.pill(x.status) + (x.reason ? '<span class="sub">' + x.reason + "</span>" : ""); } },
        { t: "Amount", num: 1, v: function (x) { return U.money(x.amount); } },
      ], p);
  };

  V.commissions = function () {
    var c = st().commissions;
    var byP = S.providers().filter(function (p) { return p.products.length; }).map(function (p) { return [p.short, sum(c.filter(function (x) { return x.providerId === p.id; }), function (x) { return x.amount; })]; });
    return head("Commissions", "Calculated from gross premium × provider commission rate.") +
      '<div class="grid-d g-21"><div>' + A.table([
        { t: "Policy", v: function (x) { return x.policyId + '<span class="sub">' + x.orderId + "</span>"; } }, { t: "Insurer", v: function (x) { return A.prov(x.providerId); } },
        { t: "Gross premium", num: 1, v: function (x) { return U.money(x.gross); } }, { t: "Rate", num: 1, v: function (x) { return x.rate + "%"; } }, { t: "Commission", num: 1, v: function (x) { return "<b>" + U.money(x.amount) + "</b>"; } }, { t: "Status", v: function (x) { return A.pill(x.status); } },
      ], c) + '</div><div class="card-d" style="align-self:start"><div class="hd"><h3>By insurer</h3><small>AED</small></div>' + bars(byP, U.money0) + "</div></div>";
  };

  V.erp = function () {
    var e = st().erpSync;
    var failed = e.filter(function (x) { return x.status === "failed"; });
    return head("ERP synchronisation", "Queued jobs · retries never re-issue a policy.", failed.length ? '<button class="btn-d pri" id="retryAll"><i class="far fa-arrows-rotate"></i> Retry all failed (' + failed.length + ")</button>" : "") +
      '<div class="grid-d g-3" style="margin-bottom:16px">' + kpi("Synced", e.filter(function (x) { return x.status === "synced"; }).length, "fa-circle-check", "Customer · Invoice · Payment · Policy") + kpi("Failed", failed.length, "fa-triangle-exclamation", "Auto-retry with backoff") + kpi("Last sync", e[0] ? U.ago(e[0].lastAttemptAt) : "—", "fa-clock", "ERP (Finance)") + "</div>" +
      A.table([
        { t: "Job", v: function (x) { return "<b>" + x.id + '</b><span class="sub">' + x.orderId + "</span>"; } }, { t: "Entity", v: function (x) { return '<span class="tagx">' + x.entity + "</span> " + x.ref; } },
        { t: "ERP record", v: function (x) { return x.erpRecordId ? '<span class="mono">' + x.erpRecordId + "</span>" : "—"; } }, { t: "Attempts", num: 1, v: function (x) { return x.attempts; } },
        { t: "Last attempt", v: function (x) { return U.fmtDate(x.lastAttemptAt, true); } }, { t: "Status", v: function (x) { return A.pill(x.status) + (x.error ? '<span class="sub" style="color:var(--bad);max-width:300px">' + x.error + "</span>" : ""); } },
        { t: "", v: function (x) { return x.status === "failed" ? '<button class="btn-d sm" data-erp-retry="' + x.id + '">Retry</button>' : ""; } },
      ], e);
  };

  V.integrations = function () {
    var logs = st().apiLogs.slice(0, 40);
    return head("Integration health", "Provider adapters, ERP, payments and notifications.") + healthTable(false) +
      '<div class="card-d mt-3"><div class="hd"><h3>Recent API calls</h3><small>api_logs</small></div>' + A.table([
        { t: "Time", v: function (l) { return U.fmtDate(l.at, true); } }, { t: "Provider", v: function (l) { return A.prov(l.provider); } }, { t: "Operation", v: function (l) { return '<span class="mono">' + l.op + "()</span>"; } },
        { t: "Latency", num: 1, v: function (l) { return l.ms + " ms"; } }, { t: "Result", v: function (l) { return A.pill(/TIMEOUT|5\d\d|4\d\d/.test(l.status) ? "failed" : "active", l.status); } }, { t: "Reference", v: function (l) { return '<span class="mono">' + (l.ref || "") + "</span>"; } },
      ], logs) + "</div>";
  };

  V.users = function () {
    var roles = D.ROLES, perms = D.PERMISSIONS;
    return head("Users & roles", "Role-based access control (RBAC) with 2FA.", '<button class="btn-d pri" data-toast="Invitation email sent (demo)"><i class="far fa-user-plus"></i> Invite user</button>') + A.table([
      { t: "User", v: function (u) { return "<b>" + u.name + '</b><span class="sub">' + u.email + "</span>"; } }, { t: "Role", v: function (u) { return roles.filter(function (r) { return r.id === u.role; })[0].name; } },
      { t: "2FA", v: function (u) { return u.twofa ? A.pill("active", "Enabled") : A.pill("pending", "Not set"); } }, { t: "Last login", v: function (u) { return u.lastLogin ? U.ago(u.lastLogin) : "—"; } }, { t: "Status", v: function (u) { return A.pill(u.status); } },
    ], st().users) +
      '<div class="card-d mt-3"><div class="hd"><h3>Permission matrix</h3><small>' + perms.length + " permissions × " + roles.length + ' roles</small></div><div class="tbl-wrap"><table class="tbl matrix"><thead><tr><th>Permission</th>' + roles.map(function (r) { return "<th>" + r.name.replace(" Manager", " Mgr") + "</th>"; }).join("") + "</tr></thead><tbody>" +
      perms.map(function (p) { return '<tr><td class="mono">' + p + "</td>" + roles.map(function (r) { return D.ROLE_MATRIX[r.id].indexOf(p) > -1 ? '<td class="y"><i class="fas fa-check"></i></td>' : '<td class="n"><i class="far fa-minus"></i></td>'; }).join("") + "</tr>"; }).join("") + "</tbody></table></div></div>";
  };

  V.audit = function () {
    return head("Audit log", "Sensitive actions: logins, providers, credentials, commissions, orders, policies, refunds, permissions, documents, ERP.") + A.table([
      { t: "Time", v: function (a) { return U.fmtDate(a.at, true); } }, { t: "Actor", v: function (a) { return "<b>" + U.esc(a.actor) + '</b><span class="sub mono">' + a.ip + "</span>"; } },
      { t: "Action", v: function (a) { return '<span class="mono">' + a.action + "</span>"; } }, { t: "Entity", v: function (a) { return a.entity + '<span class="sub">' + a.entityId + "</span>"; } },
      { t: "Change", v: function (a) { return a.before || a.after ? '<span class="mono" style="font-size:11.5px">' + U.esc(JSON.stringify(a.before)) + " → " + U.esc(JSON.stringify(a.after)) + "</span>" : "—"; } },
    ], st().audit);
  };

  V.notifications = function () {
    return head("Notifications", "Customer and internal notifications (email, SMS, WhatsApp, internal).") + A.table([
      { t: "Time", v: function (n) { return U.fmtDate(n.at, true); } }, { t: "Channel", v: function (n) { return '<span class="tagx">' + n.channel + "</span>"; } },
      { t: "Template", v: function (n) { return A.label(n.template); } }, { t: "Recipient", v: function (n) { return n.to; } }, { t: "Reference", v: function (n) { return n.ref; } }, { t: "Status", v: function (n) { return A.pill(n.status); } },
    ], st().notifications);
  };

  V.reports = function () {
    var s = st(), provs = S.providers().filter(function (p) { return p.products.length; });
    var rows = provs.map(function (p) {
      var pol = s.policies.filter(function (x) { return x.providerId === p.id; });
      var logs = s.apiLogs.filter(function (l) { return l.provider === p.id; });
      return { p: p, policies: pol.length, premium: sum(pol, function (x) { return x.premium; }), vat: sum(pol, function (x) { return x.vat; }), comm: sum(s.commissions.filter(function (c) { return c.providerId === p.id; }), function (c) { return c.amount; }), api: logs.length ? Math.round(logs.filter(function (l) { return !/TIMEOUT/.test(l.status); }).length / logs.length * 1000) / 10 : 0 };
    });
    return head("Reports", "Premium, VAT, commission and integration KPIs by insurer.", '<button class="btn-d" id="exportReport"><i class="far fa-file-csv"></i> Export CSV</button>') + A.table([
      { t: "Insurer", v: function (r) { return A.prov(r.p.id); } }, { t: "Policies", num: 1, v: function (r) { return r.policies; } }, { t: "Gross premium", num: 1, v: function (r) { return U.money(r.premium); } },
      { t: "VAT", num: 1, v: function (r) { return U.money(r.vat); } }, { t: "Commission", num: 1, v: function (r) { return U.money(r.comm); } }, { t: "Net to insurer", num: 1, v: function (r) { return U.money(r.premium - r.comm); } }, { t: "API success", num: 1, v: function (r) { return r.api + "%"; } },
    ], rows) + '<div class="grid-d g-2 mt-3"><div class="card-d"><div class="hd"><h3>Policies by insurer</h3></div>' + bars(rows.map(function (r) { return [r.p.short, r.policies]; }), function (v) { return v; }) + '</div><div class="card-d"><div class="hd"><h3>Commission by insurer</h3><small>AED</small></div>' + bars(rows.map(function (r) { return [r.p.short, r.comm]; }), U.money0) + "</div></div>";
  };

  V.settings = function () {
    var set = S.settings();
    function tg(k, l, sub) { return '<label class="row-flex" style="padding:10px 0;border-bottom:1px solid var(--line)"><span class="tgl"><input type="checkbox" data-setting="' + k + '" ' + (set[k] ? "checked" : "") + ' /><i></i></span><span><b>' + l + '</b><span class="sub muted" style="display:block;font-size:12.5px">' + sub + "</span></span></label>"; }
    return head("Settings", "Business rules and demo simulation switches.") + '<div class="grid-d g-2"><div class="card-d"><div class="hd"><h3>Quote engine rules</h3></div><div class="kvx">' + kv("VAT rate", "5%") + kv("Quote validity", "7 days") + kv("Agency repair max vehicle age", "5 years") + kv("Parallel provider timeout", "8–10 s per provider") + kv("Min. comprehensive premium", "AED 1,350 (garage) / 1,850 (agency)") + kv("Currency", "AED") + '</div><div class="note-d mt-3"><i class="fal fa-circle-info"></i><div>In production these rules live in configuration tables and are editable per product and provider.</div></div></div>' +
      '<div class="card-d"><div class="hd"><h3>Demo simulation</h3><span class="demo-ribbon">Prototype only</span></div>' + tg("simulateProviderTimeout", "Insurer API timeout (ADNIC)", "Next quote request: ADNIC does not respond; other offers still shown.") + tg("simulatePaymentFailure", "Payment declined", "Next payment attempt is declined; customer can retry.") + tg("simulateErpFailure", "ERP sync failure", "Policy is issued; ERP sync fails and appears here for retry.") + '<div class="row-flex mt-3"><button class="btn-d danger" id="resetDemo"><i class="far fa-rotate"></i> Reset all demo data</button></div></div></div>';
  };

  var TITLES = { dashboard: "Dashboard", reports: "Reports", products: "Products", providers: "Providers", "quote-requests": "Quote Requests", quotes: "Quotes", orders: "Orders", policies: "Policies", customers: "Customers", leads: "Leads", documents: "Documents", payments: "Payments", commissions: "Commissions", erp: "ERP Sync", integrations: "Integration Health", users: "Users & Roles", audit: "Audit Log", notifications: "Notifications", settings: "Settings" };

  /* ----------------------------- drawers ----------------------------- */
  function drawerHead(t, extra) { return '<div class="hd"><h3>' + t + "</h3>" + (extra || "") + '<button class="btn-d sm ghost" style="margin-left:auto" data-close><i class="far fa-xmark"></i></button></div>'; }

  function openOrder(id) {
    var o = S.order(id); if (!o) return;
    var s = st(), c = customer(o.customerId), q = S.quote(o.quoteId);
    var pays = s.payments.filter(function (p) { return p.orderId === id; }), docs = s.documents.filter(function (x) { return x.orderId === id; }), erp = s.erpSync.filter(function (e) { return e.orderId === id; });
    var actions = [];
    if (o.state === "DOCUMENTS_PENDING" || docs.some(function (x) { return x.status === "pending_review"; })) actions.push('<button class="btn-d sm" data-order-act="verify|' + id + '">Verify documents</button>');
    if (["POLICY_ISSUED", "CANCELLED", "REFUNDED"].indexOf(o.state) < 0) actions.push('<button class="btn-d sm danger" data-order-act="cancel|' + id + '">Cancel order</button>');
    if (o.state === "POLICY_ISSUED") actions.push('<button class="btn-d sm danger" data-order-act="refund|' + id + '">Cancel policy &amp; refund</button>');
    if (o.state === "INSURANCE_PROCESSING") actions.push('<button class="btn-d sm pri" data-order-act="issue|' + id + '">Mark policy issued</button>');
    A.drawer(drawerHead(o.id, A.pill(o.state)) + '<div class="bd">' +
      '<div class="card-d"><div class="kvx">' + kv("Customer", c ? U.esc(c.name) + '<span class="sub muted">' + c.email + "</span>" : U.esc(o.customerName)) + kv("Vehicle", o.vehicle) + kv("Insurer / plan", S.provider(o.providerId).name + (q ? " — " + q.planName : "")) + kv("Quote", q ? '<span class="mono">' + q.id + "</span>" : "—") +
      kv("Premium", U.money(o.premium)) + kv("VAT", U.money(o.vat)) + kv("Total", "<b>" + U.money(o.total) + "</b>") + kv("Commission", o.commissionRate + "% · " + U.money(o.premium * o.commissionRate / 100)) + kv("Policy", o.policyId ? '<a target="_blank" href="policy-document.html?policy=' + o.policyId + '">' + o.policyId + "</a>" : "—") + kv("ERP", A.pill(o.erp ? o.erp.status : "not_started")) + "</div>" +
      (actions.length ? '<div class="row-flex mt-3">' + actions.join("") + "</div>" : "") + "</div>" +
      '<div class="grid-d g-2"><div class="card-d"><div class="hd"><h3>State machine</h3></div>' + A.stateMachine(o) + "</div><div class=\"grid-d\" style=\"align-content:start\">" +
      '<div class="card-d"><div class="hd"><h3>Payment attempts</h3></div>' + (pays.map(function (p) { return '<div class="row-flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span>#' + p.attempt + " · " + p.cardBrand + " " + p.last4 + '<span class="sub muted">' + p.gatewayRef + "</span></span>" + A.pill(p.status) + "</div>"; }).join("") || '<p class="muted">No payment yet.</p>') + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Documents</h3></div>' + (docs.map(function (x) { return '<div class="row-flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span style="font-size:13px">' + x.label + "</span>" + A.pill(x.status) + "</div>"; }).join("") || '<p class="muted">None uploaded.</p>') + "</div>" +
      '<div class="card-d"><div class="hd"><h3>ERP sync</h3></div>' + (erp.map(function (e) { return '<div class="row-flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span style="font-size:13px">' + e.entity + "</span>" + A.pill(e.status) + (e.status === "failed" ? '<button class="btn-d sm" data-erp-retry="' + e.id + '">Retry</button>' : "") + "</div>"; }).join("") || '<p class="muted">Starts after policy issuance.</p>') + "</div></div></div></div>");
  }

  function openQR(id) {
    var q = S.quoteRequest(id); if (!q) return;
    var quotes = S.quotesFor(id);
    A.drawer(drawerHead(q.id, A.pill(q.status)) + '<div class="bd"><div class="card-d"><div class="kvx">' + kv("Customer", U.esc(q.customer.name)) + kv("Email", q.customer.email) + kv("Vehicle", q.vehicle.year + " " + q.vehicle.make + " " + q.vehicle.model) + kv("Value", U.money0(q.vehicle.value)) + kv("Cover", q.cover.type) + kv("Created", U.fmtDate(q.createdAt, true)) + "</div></div>" +
      '<div class="card-d"><div class="hd"><h3>Provider responses (parallel)</h3></div>' + Object.keys(q.providers || {}).map(function (k) { var r = q.providers[k]; return '<div class="row-flex" style="justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line)">' + A.prov(k) + "<span>" + (r.ms / 1000).toFixed(2) + "s</span>" + A.pill(r.status === "success" ? "active" : "failed", r.status === "success" ? "Success" : "Timeout") + "</div>"; }).join("") + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Normalised quotes</h3></div>' + A.table([{ t: "Provider", v: function (x) { return A.mono(x.providerId); } }, { t: "Plan", v: function (x) { return x.planName; } }, { t: "Total", num: 1, v: function (x) { return U.money(x.total); } }], quotes) + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Internal quote standard (JSON)</h3></div><pre style="background:#0f1f30;color:#d7e9f4;padding:14px;border-radius:10px;font-size:12px;overflow:auto">' + U.esc(JSON.stringify(quotes[0] ? { provider_id: quotes[0].providerId, quote_id: quotes[0].id, premium: quotes[0].premium, vat: quotes[0].vat, total: quotes[0].total, currency: "AED", insurance_type: quotes[0].insuranceType, repair_type: quotes[0].repairType, deductible: quotes[0].deductible, roadside_assistance: quotes[0].benefits.roadside, gcc_coverage: quotes[0].benefits.gcc, valid_until: quotes[0].validUntil } : {}, null, 2)) + "</pre></div></div>");
  }

  function openCustomer(id) {
    var c = customer(id); if (!c) return;
    var s = st();
    S.audit(ACTOR, "customer.viewed", "customers", id);
    A.drawer(drawerHead(U.esc(c.name), '<span class="tagx">' + c.id + "</span>") + '<div class="bd"><div class="card-d"><div class="kvx">' + kv("Email", c.email) + kv("Mobile", c.mobile) + kv("Emirates ID", c.emiratesId) + kv("Nationality", c.nationality) + kv("Emirate", c.emirate) + kv("Customer since", U.fmtDate(c.createdAt)) + "</div></div>" +
      '<div class="card-d"><div class="hd"><h3>Policies</h3></div>' + A.table([{ t: "Policy", v: function (p) { return p.number; } }, { t: "Vehicle", v: function (p) { return p.vehicle; } }, { t: "Ends", v: function (p) { return U.fmtDate(p.endDate); } }], s.policies.filter(function (p) { return p.customerId === id; })) + "</div>" +
      '<div class="card-d"><div class="hd"><h3>Orders</h3></div>' + A.table([{ t: "Order", v: function (o) { return '<a href="#/orders/' + o.id + '">' + o.id + "</a>"; } }, { t: "State", v: function (o) { return A.pill(o.state); } }, { t: "Total", num: 1, v: function (o) { return U.money(o.total); } }], s.orders.filter(function (o) { return o.customerId === id; })) + '</div><p class="muted" style="font-size:12px"><i class="fal fa-shield-check"></i> This view was recorded in the audit log.</p></div>');
  }

  function openProvider(id) {
    var p = id ? S.provider(id) : null;
    if (!p) {
      A.drawer(drawerHead("Add insurance provider") + '<div class="bd"><div class="card-d"><div class="hd"><h3>Onboarding flow</h3></div><ul class="sm">' + ["Create provider profile (name, logo, contacts)", "Store API credentials securely (vault / encrypted)", "Choose environment: Sandbox → Production", "Map products & plans (provider_products)", "Implement / select adapter (InsuranceProviderInterface)", "Test connection & sample quote", "Set commission, timeout and retries", "Enable — provider joins quote requests"].map(function (t, i) { return '<li class="todo"><span class="d"></span><b>' + (i + 1) + ". " + t + "</b></li>"; }).join("") +
        '</ul></div><div class="card-d"><div class="hd"><h3>Adapter interface</h3></div><pre style="background:#0f1f30;color:#d7e9f4;padding:14px;border-radius:10px;font-size:12.5px">interface InsuranceProviderInterface\n{\n    public function getQuote(QuoteRequest $r): QuoteCollection;\n    public function getPlans(Product $p): PlanCollection;\n    public function createPolicy(Order $o): PolicyResult;\n    public function uploadDocuments(Order $o, array $docs): void;\n    public function getPolicyStatus(string $ref): PolicyStatus;\n    public function cancelPolicy(Policy $p): CancelResult;\n}</pre></div><div class="note-d"><i class="fal fa-circle-info"></i><div>The core (quote engine, orders, payments, policies, ERP) does not change when a provider is added.</div></div></div>');
      return;
    }
    A.drawer(drawerHead("Configure " + p.name, A.pill(p.enabled ? p.health : "not_connected")) + '<div class="bd"><form class="card-d" id="provForm" data-id="' + p.id + '"><div class="grid-d g-2">' +
      '<label><span class="lbl">Environment</span><select class="sel" name="environment" style="width:100%">' + selectOpts([["sandbox", "Sandbox"], ["production", "Production"], ["not_configured", "Not configured"]], p.environment) + "</select></label>" +
      '<label><span class="lbl">Commission %</span><input class="inp" style="width:100%" name="commission" type="number" step="0.5" value="' + p.commission + '" /></label>' +
      '<label><span class="lbl">Timeout (ms)</span><input class="inp" style="width:100%" name="timeoutMs" type="number" step="500" value="' + p.timeoutMs + '" /></label>' +
      '<label><span class="lbl">Retries</span><input class="inp" style="width:100%" name="retries" type="number" min="0" max="5" value="' + p.retries + '" /></label>' +
      '<label style="grid-column:1/-1"><span class="lbl">API base URL</span><input class="inp" style="width:100%" value="https://api-sandbox.' + p.id + '.example/motor/v2" disabled /></label>' +
      '<label style="grid-column:1/-1"><span class="lbl">Credentials</span><input class="inp" style="width:100%" value="••••••••••••••••  (stored encrypted · last rotated 12 days ago)" disabled /></label></div>' +
      '<div class="row-flex mt-3"><button class="btn-d pri" type="submit">Save changes</button><button class="btn-d" type="button" data-toast="Credential rotation requires Technical Admin + 2FA (demo)">Rotate credentials</button></div></form>' +
      '<div class="note-d"><i class="fal fa-list-timeline"></i><div>Changes to commission, credentials and environment are written to the audit log.</div></div></div>');
  }

  /* ---------------------------- actions ------------------------------ */
  function rerender() { w.dispatchEvent(new HashChangeEvent("hashchange")); }

  d.addEventListener("DOMContentLoaded", function () {
    d.getElementById("meBox").innerHTML = '<span class="av">SA</span><div><b>Super Admin</b><span style="color:#7fa3bd">admin@deltains-brokers.com</span></div>';
    function counts() {
      var s = st();
      var set = function (k, n, alert) { var el = d.querySelector('[data-cnt="' + k + '"]'); if (!el) return; el.textContent = n; el.classList.toggle("d-none", !n); el.classList.toggle("alert", !!alert); };
      set("erp", s.erpSync.filter(function (e) { return e.status === "failed"; }).length, 1);
      set("leads", s.leads.filter(function (l) { return l.status === "new"; }).length);
      set("documents", s.documents.filter(function (x) { return x.status === "pending_review"; }).length);
      set("orders", s.orders.filter(function (o) { return o.state === "PAYMENT_FAILED"; }).length, 1);
    }
    counts();
    A.router(V, TITLES, "dashboard");
    d.addEventListener("view:rendered", counts);

    var view = d.getElementById("view");
    view.addEventListener("click", function (e) {
      var r = e.target.closest("tr[data-row]");
      if (r && !e.target.closest("a,button,select,input")) {
        var v = r.getAttribute("data-row").split(":");
        if (v[0] === "order") openOrder(v[1]); else if (v[0] === "qr") openQR(v[1]); else if (v[0] === "cust") openCustomer(v[1]);
      }
      var cap = e.target.closest("[data-cap]");
      if (cap) {
        var pk = cap.getAttribute("data-cap").split("|"), prod = S.product(pk[0]), patch = {};
        patch[pk[1]] = !prod.capabilities[pk[1]];
        if (patch[pk[1]] && !prod.providers.length) { A.toast("⚠ " + prod.name + " has no connected providers yet — capability enabled in config only (demo)."); }
        S.updateProduct(pk[0], { capabilities: patch }, ACTOR); rerender();
      }
      var t = e.target.closest("[data-test]");
      if (t) {
        var p = S.provider(t.getAttribute("data-test"));
        t.innerHTML = '<i class="far fa-spinner-third fa-spin"></i> Testing…';
        setTimeout(function () {
          if (!p.products.length) { A.toast("✖ " + p.name + ": no credentials configured"); t.innerHTML = '<i class="far fa-plug"></i> Test connection'; return; }
          var ms = Math.round(p.avgResponseMs * (0.8 + Math.random() * 0.4));
          A.toast("✔ " + p.name + " sandbox reachable · auth OK · " + ms + " ms");
          S.audit(ACTOR, "provider.test_connection", "insurance_providers", p.id);
          t.innerHTML = '<i class="far fa-check"></i> OK · ' + ms + " ms";
        }, 1100);
      }
      if (e.target.closest("#addProv")) openProvider(null);
      var ep = e.target.closest("[data-edit-prov]"); if (ep) openProvider(ep.getAttribute("data-edit-prov"));
      var dv = e.target.closest("[data-doc-verify]"), dr = e.target.closest("[data-doc-reject]");
      if (dv || dr) {
        var s = st(), idd = (dv || dr).getAttribute(dv ? "data-doc-verify" : "data-doc-reject");
        s.documents.forEach(function (x) { if (x.id === idd) x.status = dv ? "verified" : "rejected"; }); S.save(s);
        S.audit(ACTOR, dv ? "document.verified" : "document.rejected", "documents", idd); rerender();
      }
      if (e.target.closest("#retryAll")) { st().erpSync.filter(function (x) { return x.status === "failed"; }).forEach(function (x) { S.retryErp(x.id, ACTOR); }); A.toast("All failed ERP jobs re-queued and synced"); rerender(); }
      if (e.target.closest("#resetDemo")) { S.reset(); A.toast("Demo data reset"); rerender(); }
      if (e.target.closest("#exportOrders")) csv("delta-orders.csv", [["Order", "Customer", "Vehicle", "Insurer", "State", "Premium", "VAT", "Total", "Created"]].concat(st().orders.map(function (o) { return [o.id, o.customerName, o.vehicle, o.providerId, o.state, o.premium, o.vat, o.total, o.createdAt]; })));
      if (e.target.closest("#exportReport")) csv("delta-report.csv", [["Insurer", "Policies", "Gross premium", "Commission"]].concat(S.providers().filter(function (p) { return p.products.length; }).map(function (p) { var pol = st().policies.filter(function (x) { return x.providerId === p.id; }); return [p.name, pol.length, sum(pol, function (x) { return x.premium; }), sum(st().commissions.filter(function (c) { return c.providerId === p.id; }), function (c) { return c.amount; })]; })));
    });
    view.addEventListener("change", function (e) {
      var el = e.target;
      if (el.matches("[data-prod-status]")) { S.updateProduct(el.getAttribute("data-prod-status"), { status: el.value }, ACTOR); A.toast("Product status updated · audit logged"); }
      if (el.matches("[data-prov-enable]")) { S.updateProvider(el.getAttribute("data-prov-enable"), { enabled: el.checked }, ACTOR); A.toast(el.checked ? "Provider enabled — joins new quote requests" : "Provider disabled — excluded from new quote requests"); rerender(); }
      if (el.matches("[data-filter]")) { filt[el.getAttribute("data-filter")] = el.value; rerender(); }
      if (el.matches("[data-setting]")) { S.setSetting(el.getAttribute("data-setting"), el.checked); A.toast("Demo setting saved"); }
      if (el.matches("[data-lead-status],[data-lead-assign]")) {
        var s = st(), lid = el.getAttribute("data-lead-status") || el.getAttribute("data-lead-assign");
        s.leads.forEach(function (l) { if (l.id === lid) { if (el.matches("[data-lead-status]")) l.status = el.value; else l.assignee = el.value || null; } }); S.save(s);
        S.audit(ACTOR, "lead.updated", "leads", lid); A.toast("Lead updated");
      }
    });

    var pn = d.getElementById("drawerPn");
    pn.addEventListener("click", function (e) {
      var r = e.target.closest("[data-erp-retry]");
      if (r) { S.retryErp(r.getAttribute("data-erp-retry"), ACTOR); A.toast("ERP job retried — synced"); var oid = (pn.querySelector(".hd h3") || {}).textContent; if (S.order(oid)) openOrder(oid); rerender(); }
      var a = e.target.closest("[data-order-act]");
      if (a) {
        var parts = a.getAttribute("data-order-act").split("|"), id = parts[1], s = st();
        if (parts[0] === "verify") { s.documents.forEach(function (x) { if (x.orderId === id && x.status === "pending_review") x.status = "verified"; }); S.save(s); S.audit(ACTOR, "documents.verified", "orders", id); A.toast("Documents verified"); }
        if (parts[0] === "cancel") { S.setOrderState(id, "CANCELLED", "Cancelled by operations", ACTOR); S.audit(ACTOR, "order.cancelled", "orders", id); A.toast("Order cancelled"); }
        if (parts[0] === "refund") { S.setOrderState(id, "CANCELLED", "Policy cancelled via adapter cancelPolicy()", ACTOR); S.setOrderState(id, "REFUNDED", "Refund issued to original card", "Finance"); s = st(); s.policies.forEach(function (p) { if (p.orderId === id) p.status = "cancelled"; }); S.save(s); S.audit(ACTOR, "policy.cancelled_refunded", "orders", id); A.toast("Policy cancelled and refund issued"); }
        if (parts[0] === "issue") { S.setOrderState(id, "POLICY_ISSUED", "Issued manually after insurer confirmation", ACTOR); A.toast("Order marked as issued"); }
        openOrder(id); rerender();
      }
    });
    pn.addEventListener("submit", function (e) {
      if (e.target.id !== "provForm") return;
      e.preventDefault();
      var f = e.target, id = f.getAttribute("data-id");
      S.updateProvider(id, { environment: f.environment.value, commission: Number(f.commission.value), timeoutMs: Number(f.timeoutMs.value), retries: Number(f.retries.value) }, ACTOR);
      A.toast("Provider saved · change recorded in audit log"); A.closeDrawer(); rerender();
    });
    d.addEventListener("click", function (e) { var t = e.target.closest("[data-toast]"); if (t) A.toast(t.getAttribute("data-toast")); });
    d.getElementById("globalSearch").addEventListener("keydown", function (e) {
      if (e.key !== "Enter") return;
      var q = this.value.trim().toUpperCase(); if (!q) return;
      if (S.order(q)) location.hash = "#/orders/" + q; else if (S.quoteRequest(q)) location.hash = "#/quote-requests/" + q;
      else { var c = st().customers.filter(function (x) { return (x.name + x.email).toUpperCase().indexOf(q) > -1; })[0]; if (c) openCustomer(c.id); else A.toast("No match for " + U.esc(this.value)); }
    });
  });
})(window, document);
