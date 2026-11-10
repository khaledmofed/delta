/* ==========================================================================
   Delta Insurance — Demo data store & business logic (front-end only)
   --------------------------------------------------------------------------
   Simulates the Laravel backend so the full scenario can be presented:
     Quote Request → Provider adapters (parallel) → Normalised quotes
     → Order → Documents → Payment attempts → Policy issuance
     → ERP synchronisation → Notifications → Audit log
   Everything is persisted in localStorage (key: delta_demo_v1).
   ========================================================================== */
(function (w) {
  "use strict";
  var D = w.DELTA;
  var KEY = "delta_demo_v1";
  var VAT = 0.05;

  /* ------------------------------ utils --------------------------------- */
  function pad(n, l) { n = String(n); while (n.length < l) n = "0" + n; return n; }
  function rnd(seed) { var x = Math.sin(seed) * 10000; return x - Math.floor(x); }
  function hash(str) { var h = 0; str = String(str); for (var i = 0; i < str.length; i++) { h = (h << 5) - h + str.charCodeAt(i); h |= 0; } return Math.abs(h); }
  function now() { return new Date().toISOString(); }
  function addDays(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function addMin(d, n) { return new Date(new Date(d).getTime() + n * 60000); }
  function uid(prefix) {
    var s = get();
    s.counters = s.counters || {};
    s.counters[prefix] = (s.counters[prefix] || 1000) + 1;
    save(s);
    return prefix + "-" + new Date().getFullYear() + "-" + pad(s.counters[prefix], 6);
  }
  function money(v, withCur) {
    var n = Number(v || 0);
    var s = n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return withCur === false ? s : "AED " + s;
  }
  function money0(v) { return "AED " + Math.round(Number(v || 0)).toLocaleString("en-US"); }
  function fmtDate(d, withTime) {
    if (!d) return "—";
    var x = new Date(d);
    var o = { day: "2-digit", month: "short", year: "numeric" };
    if (withTime) { o.hour = "2-digit"; o.minute = "2-digit"; }
    return x.toLocaleString("en-GB", o);
  }
  function ago(d) {
    var s = (Date.now() - new Date(d).getTime()) / 1000;
    if (s < 60) return "just now";
    if (s < 3600) return Math.floor(s / 60) + " min ago";
    if (s < 86400) return Math.floor(s / 3600) + " h ago";
    return Math.floor(s / 86400) + " d ago";
  }
  function esc(s) { return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function qs(name) { return new URLSearchParams(w.location.search).get(name); }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }

  /* --------------------------- persistence ------------------------------ */
  var memory = null;
  function get() {
    if (memory) return memory;
    try {
      var raw = w.localStorage.getItem(KEY);
      if (raw) { memory = JSON.parse(raw); return memory; }
    } catch (e) {}
    memory = seed();
    save(memory);
    return memory;
  }
  function save(s) {
    memory = s;
    try { w.localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
  }
  function reset() {
    try { w.localStorage.removeItem(KEY); } catch (e) {}
    memory = null;
    return get();
  }

  /* --------------------- products & providers (admin) -------------------- */
  function products() {
    var s = get();
    return D.PRODUCTS.map(function (p) {
      var o = s.productOverrides && s.productOverrides[p.id];
      var x = clone(p);
      if (o) { if (o.status) x.status = o.status; if (o.capabilities) x.capabilities = Object.assign(x.capabilities, o.capabilities); }
      return x;
    });
  }
  function product(id) { return products().filter(function (p) { return p.id === id; })[0]; }
  function updateProduct(id, patch, actor) {
    var s = get();
    s.productOverrides = s.productOverrides || {};
    var before = clone(product(id));
    var o = s.productOverrides[id] || {};
    if (patch.status) o.status = patch.status;
    if (patch.capabilities) o.capabilities = Object.assign(o.capabilities || {}, patch.capabilities);
    s.productOverrides[id] = o;
    save(s);
    audit(actor || "Admin", "product.updated", "insurance_products", id, before, product(id));
  }
  function providers() {
    var s = get();
    return D.PROVIDERS.map(function (p) {
      var x = clone(p);
      var o = s.providerOverrides && s.providerOverrides[p.id];
      if (o) Object.assign(x, o);
      return x;
    });
  }
  function provider(id) { return providers().filter(function (p) { return p.id === id; })[0]; }
  function updateProvider(id, patch, actor) {
    var s = get();
    s.providerOverrides = s.providerOverrides || {};
    var before = provider(id);
    s.providerOverrides[id] = Object.assign(s.providerOverrides[id] || {}, patch);
    save(s);
    audit(actor || "Admin", "provider.updated", "insurance_providers", id, before, provider(id));
  }

  /* ------------------------------ settings ------------------------------ */
  function settings() { return get().settings; }
  function setSetting(k, v) { var s = get(); s.settings[k] = v; save(s); }

  /* ------------------------------ audit --------------------------------- */
  function audit(actor, action, entity, entityId, before, after) {
    var s = get();
    s.audit.unshift({
      id: "AUD-" + pad(s.audit.length + 1, 5),
      at: now(), actor: actor, action: action, entity: entity, entityId: entityId,
      ip: actor === "System" ? "10.0.0.12" : "94.200.14." + (hash(actor) % 200),
      before: before ? summarize(before) : null, after: after ? summarize(after) : null,
    });
    s.audit = s.audit.slice(0, 400);
    save(s);
  }
  function summarize(o) {
    if (typeof o !== "object") return o;
    var keep = {};
    ["status", "enabled", "environment", "commission", "capabilities", "state", "health", "timeoutMs"].forEach(function (k) { if (o[k] !== undefined) keep[k] = o[k]; });
    return keep;
  }
  function notify(to, channel, template, ref) {
    var s = get();
    s.notifications.unshift({ id: "NTF-" + pad(s.notifications.length + 1, 5), at: now(), to: to, channel: channel, template: template, ref: ref, status: "sent" });
    save(s);
  }
  function apiLog(providerId, op, ms, status, ref) {
    var s = get();
    s.apiLogs.unshift({ at: now(), provider: providerId, op: op, ms: ms, status: status, ref: ref });
    s.apiLogs = s.apiLogs.slice(0, 300);
    save(s);
  }

  /* ----------------------------- quote engine --------------------------- */
  function vehicleValue(make, model, year) {
    var ref = D.VEHICLES[make] && D.VEHICLES[make][model];
    if (!ref) return 90000;
    var age = Math.max(0, new Date().getFullYear() - Number(year || new Date().getFullYear()));
    var v = ref[0] * Math.pow(0.85, age);
    return Math.max(15000, Math.round(v / 500) * 500);
  }
  function bodyOf(make, model) { var r = D.VEHICLES[make] && D.VEHICLES[make][model]; return r ? r[1] : "Sedan"; }

  function priceFor(plan, prov, input) {
    var value = Number(input.vehicle.value);
    var age = new Date().getFullYear() - Number(input.vehicle.year);
    var driverAge = input.customer.dob ? Math.floor((Date.now() - new Date(input.customer.dob).getTime()) / 31557600000) : 35;
    var exp = Number(input.customer.experience || 5);
    var ncd = Number(input.customer.noClaimsYears || 0);
    var seed = hash(prov.id + plan.code + input.vehicle.make + input.vehicle.model + input.vehicle.year);
    var premium;
    if (plan.type === "third_party") {
      var base = { SUV: 900, Sedan: 720, Hatchback: 650, Pickup: 950, Coupe: 980 }[bodyOf(input.vehicle.make, input.vehicle.model)] || 750;
      premium = base * prov.rateFactor * (0.95 + rnd(seed) * 0.12);
      if (driverAge < 25) premium *= 1.35;
    } else {
      var rate = (plan.repair === "agency" ? 0.0275 : 0.0215) * prov.rateFactor;
      rate *= 0.94 + rnd(seed) * 0.14;
      if (age > 3 && plan.repair === "agency") rate *= 1.18;
      if (driverAge < 25) rate *= 1.3;
      if (exp < 1) rate *= 1.2;
      premium = value * rate;
      premium *= 1 - Math.min(ncd, 4) * 0.05;
      premium = Math.max(premium, plan.repair === "agency" ? 1850 : 1350);
    }
    // requested add-ons that the plan does not include are priced on top
    var addons = [];
    var ad = input.cover.addons || {};
    if (plan.type !== "third_party") {
      if (ad.roadside && !plan.roadside) addons.push({ k: "Roadside assistance", v: 120 });
      if (ad.rentACar && !plan.rentACar) addons.push({ k: "Rent-a-car (7 days)", v: 220 });
      if (ad.pa && !plan.pa) addons.push({ k: "Personal accident — driver & passengers", v: 180 });
      if (ad.gcc && !plan.gcc) addons.push({ k: "GCC cover extension", v: 350 });
    } else {
      if (ad.pa && !plan.pa) addons.push({ k: "Personal accident — driver", v: 120 });
    }
    var addonTotal = addons.reduce(function (a, b) { return a + b.v; }, 0);
    premium = Math.round(premium);
    var net = premium + addonTotal;
    var vat = Math.round(net * VAT * 100) / 100;
    return { premium: premium, addons: addons, addonTotal: addonTotal, vat: vat, total: Math.round((net + vat) * 100) / 100 };
  }

  function createQuoteRequest(input) {
    var s = get();
    var id = uid("QR");
    var qr = {
      id: id, product: "motor", createdAt: now(), status: "QUOTE_REQUESTED",
      customer: input.customer, vehicle: input.vehicle, cover: input.cover,
      documents: input.documents || [], din: input.din || null, form: input.form || null,
      providers: {}, quoteIds: [], customerId: s.session.customerId || null,
    };
    s = get();
    s.quoteRequests.unshift(qr);
    save(s);
    audit("Customer", "quote_request.created", "quote_requests", id);
    return qr;
  }

  function quoteRequest(id) { return get().quoteRequests.filter(function (q) { return q.id === id; })[0]; }
  function updateQuoteRequest(id, patch) {
    var s = get();
    s.quoteRequests = s.quoteRequests.map(function (q) { return q.id === id ? Object.assign(q, patch) : q; });
    save(s);
  }

  /* Runs every enabled provider adapter "in parallel" with timeouts */
  function runQuoteEngine(qrId, onUpdate) {
    var qr = quoteRequest(qrId);
    var provs = providers().filter(function (p) { return p.enabled && p.products.indexOf("motor") > -1; });
    var set = settings();
    var results = {};
    return new Promise(function (resolve) {
      var done = 0;
      if (!provs.length) resolve({ quotes: [], results: results });
      provs.forEach(function (p, i) {
        var fail = set.simulateProviderTimeout && p.id === set.timeoutProvider;
        var ms = fail ? Math.min(p.timeoutMs, 4200) : Math.round(p.avgResponseMs * (0.7 + Math.random() * 0.6));
        onUpdate && onUpdate(p, "pending");
        setTimeout(function () {
          var status = fail ? "timeout" : "success";
          results[p.id] = { status: status, ms: fail ? p.timeoutMs : ms };
          apiLog(p.id, "getQuote", fail ? p.timeoutMs : ms, fail ? "TIMEOUT" : "200 OK", qrId);
          if (!fail) {
            var quotes = (D.MOTOR_PLANS[p.id] || []).map(function (plan) {
              var pr = priceFor(plan, p, qr);
              return {
                id: "Q-" + p.mono + "-" + pad(hash(qrId + plan.code) % 1000000, 6),
                quoteRequestId: qrId, providerId: p.id, providerName: p.name, planCode: plan.code, planName: plan.name,
                insuranceType: plan.type, repairType: plan.repair, deductible: plan.deductible,
                benefits: { roadside: plan.roadside, rentACar: plan.rentACar, gcc: plan.gcc, oman: plan.oman, pa: plan.pa, windscreen: plan.windscreen },
                premium: pr.premium, addons: pr.addons, addonTotal: pr.addonTotal, vat: pr.vat, total: pr.total, currency: "AED",
                sumInsured: plan.type === "third_party" ? null : Number(qr.vehicle.value),
                validUntil: addDays(new Date(), 7).toISOString(), createdAt: now(),
                providerRef: p.id.toUpperCase() + "-" + pad(hash(plan.code + qrId) % 99999999, 8),
              };
            });
            // Delta business rule: only offer agency repair to cars ≤ 5 years old
            var age = new Date().getFullYear() - Number(qr.vehicle.year);
            if (age > 5) quotes = quotes.filter(function (q) { return q.repairType !== "agency"; });
            var s = get();
            s.quotes = quotes.concat(s.quotes.filter(function (q) { return !(q.quoteRequestId === qrId && q.providerId === p.id); }));
            save(s);
            results[p.id].count = quotes.length;
          } else {
            audit("System", "provider.timeout", "insurance_providers", p.id);
          }
          onUpdate && onUpdate(p, status, results[p.id]);
          done++;
          if (done === provs.length) {
            var all = get().quotes.filter(function (q) { return q.quoteRequestId === qrId; });
            updateQuoteRequest(qrId, { status: all.length ? "QUOTES_RECEIVED" : "NO_QUOTES", providers: results, quoteIds: all.map(function (q) { return q.id; }), completedAt: now() });
            resolve({ quotes: all, results: results });
          }
        }, ms + i * 120);
      });
    });
  }
  function quotesFor(qrId) { return get().quotes.filter(function (q) { return q.quoteRequestId === qrId; }); }
  function quote(id) { return get().quotes.filter(function (q) { return q.id === id; })[0]; }

  /* ------------------------------- orders -------------------------------- */
  function order(id) { return get().orders.filter(function (o) { return o.id === id; })[0]; }
  function setOrderState(id, state, note, actor) {
    var s = get();
    s.orders = s.orders.map(function (o) {
      if (o.id !== id) return o;
      o.state = state;
      o.updatedAt = now();
      o.history = o.history || [];
      o.history.push({ state: state, at: now(), note: note || "", actor: actor || "System" });
      return o;
    });
    save(s);
  }
  function selectQuote(quoteId) {
    var q = quote(quoteId);
    var qr = quoteRequest(q.quoteRequestId);
    var s = get();
    var existing = s.orders.filter(function (o) { return o.quoteRequestId === qr.id && ["QUOTE_SELECTED", "CUSTOMER_DETAILS_PENDING", "DOCUMENTS_PENDING", "PAYMENT_PENDING", "PAYMENT_FAILED"].indexOf(o.state) > -1; })[0];
    if (existing) {
      s.orders = s.orders.map(function (o) { if (o.id === existing.id) { o.quoteId = quoteId; o.providerId = q.providerId; o.total = q.total; o.premium = q.premium; o.vat = q.vat; } return o; });
      save(s);
      s.current.orderId = existing.id;
      save(s);
      return order(existing.id);
    }
    var id = uid("ORD");
    s = get();
    var o = {
      id: id, product: "motor", quoteRequestId: qr.id, quoteId: quoteId, providerId: q.providerId,
      customerId: s.session.customerId || null, customerName: qr.customer.name, email: qr.customer.email,
      vehicle: qr.vehicle.year + " " + qr.vehicle.make + " " + qr.vehicle.model,
      premium: q.premium + q.addonTotal, vat: q.vat, total: q.total,
      commissionRate: provider(q.providerId).commission,
      state: "QUOTE_SELECTED", createdAt: now(), updatedAt: now(),
      history: [
        { state: "QUOTE_REQUESTED", at: qr.createdAt, actor: "Customer" },
        { state: "QUOTES_RECEIVED", at: qr.completedAt || now(), actor: "System" },
        { state: "QUOTE_SELECTED", at: now(), actor: "Customer", note: q.providerName + " — " + q.planName },
      ],
      paymentIds: [], documentIds: [], erp: { status: "not_started" },
    };
    s.orders.unshift(o);
    s.current.orderId = id;
    s.current.quoteId = quoteId;
    save(s);
    updateQuoteRequest(qr.id, { status: "QUOTE_SELECTED", selectedQuoteId: quoteId });
    audit("Customer", "order.created", "orders", id);
    return o;
  }

  function upsertCustomer(c) {
    var s = get();
    var ex = s.customers.filter(function (x) { return x.email.toLowerCase() === String(c.email).toLowerCase(); })[0];
    if (ex) {
      s.customers = s.customers.map(function (x) { return x.id === ex.id ? Object.assign(x, c, { id: ex.id }) : x; });
      save(s);
      return ex.id;
    }
    var id = "CUS-" + pad(s.customers.length + 1041, 5);
    s.customers.unshift(Object.assign({ id: id, createdAt: now(), status: "active" }, c));
    save(s);
    return id;
  }

  function completeCheckout(orderId, data) {
    var o = order(orderId);
    var qr = quoteRequest(o.quoteRequestId);
    var cid = upsertCustomer({
      name: data.name, email: data.email, mobile: data.mobile, emiratesId: data.emiratesId,
      nationality: qr.customer.nationality, emirate: qr.customer.emirate, dob: qr.customer.dob,
    });
    var s = get();
    // vehicle
    var vid = "VEH-" + pad(s.vehicles.length + 2201, 5);
    s.vehicles.unshift({ id: vid, customerId: cid, make: qr.vehicle.make, model: qr.vehicle.model, year: qr.vehicle.year, value: qr.vehicle.value, plate: data.plate, plateCode: data.plateCode, emirate: data.plateEmirate, chassis: data.chassis, createdAt: now() });
    // documents (private storage in production — here only metadata)
    var docIds = [];
    (data.documents || []).forEach(function (d, i) {
      var id = "DOC-" + pad(s.documents.length + 5001 + i, 6);
      docIds.push(id);
      s.documents.unshift({ id: id, orderId: orderId, customerId: cid, type: d.type, label: d.label, fileName: d.fileName, size: d.size, status: "pending_review", uploadedAt: now(), storage: "s3://delta-private/orders/" + orderId + "/" + id });
    });
    s.orders = s.orders.map(function (x) {
      if (x.id === orderId) { x.customerId = cid; x.customerName = data.name; x.email = data.email; x.vehicleId = vid; x.documentIds = docIds; x.policyStart = data.policyStart; }
      return x;
    });
    s.quoteRequests = s.quoteRequests.map(function (q) { if (q.id === qr.id) q.customerId = cid; return q; });
    s.session.customerId = cid;
    save(s);
    setOrderState(orderId, "CUSTOMER_DETAILS_PENDING", "Details confirmed", "Customer");
    setOrderState(orderId, "DOCUMENTS_PENDING", docIds.length + " documents uploaded", "Customer");
    setOrderState(orderId, "PAYMENT_PENDING", "Redirected to payment gateway", "System");
    audit(data.name, "documents.uploaded", "documents", orderId);
    return order(orderId);
  }

  /* ------------------------------ payments ------------------------------- */
  function createPaymentAttempt(orderId, card, forceFail) {
    var o = order(orderId);
    var s = get();
    var attempt = (o.paymentIds || []).length + 1;
    var id = "PAY-" + pad(s.payments.length + 7001, 6);
    var ok = !forceFail;
    var p = {
      id: id, orderId: orderId, attempt: attempt, amount: o.total, currency: "AED", method: "card",
      cardBrand: card.brand || "Visa", last4: card.last4 || "4242", gateway: "Demo Hosted Gateway",
      gatewayRef: "GW" + pad(hash(id + Date.now()) % 99999999, 8),
      status: ok ? "captured" : "declined", reason: ok ? null : "Insufficient funds (demo)", at: now(),
    };
    s.payments.unshift(p);
    s.orders = s.orders.map(function (x) { if (x.id === orderId) { x.paymentIds = (x.paymentIds || []).concat(id); } return x; });
    save(s);
    setOrderState(orderId, ok ? "PAYMENT_SUCCESS" : "PAYMENT_FAILED", "Attempt #" + attempt + " — " + (ok ? "captured" : p.reason), "Payment Gateway");
    notify(o.email, "email", ok ? "payment_successful" : "payment_failed", orderId);
    if (!ok) notify("ops@deltains-brokers.com", "internal", "admin_payment_failed", orderId);
    return p;
  }

  /* -------------------------- policy issuance --------------------------- */
  function issuePolicy(orderId, onStep) {
    var set = settings();
    var steps = [
      { state: "POLICY_REQUESTED", wait: 900, note: "createPolicy() sent to insurer adapter" },
      { state: "INSURANCE_PROCESSING", wait: 1600, note: "Insurer validating documents & underwriting" },
      { state: "POLICY_ISSUED", wait: 1400 },
    ];
    return new Promise(function (resolve) {
      var i = 0;
      function next() {
        if (i >= steps.length) return finish();
        var st = steps[i++];
        setTimeout(function () {
          if (st.state === "POLICY_ISSUED") createPolicy(orderId);
          setOrderState(orderId, st.state, st.note || "", st.state === "INSURANCE_PROCESSING" ? "Insurer" : "System");
          onStep && onStep(st.state);
          next();
        }, st.wait);
      }
      function finish() {
        // ERP runs as a queued job — failure never rolls back the policy
        setTimeout(function () {
          var res = syncErp(orderId, set.simulateErpFailure);
          onStep && onStep(res.status === "synced" ? "ERP_SYNCED" : "ERP_FAILED", res);
          setTimeout(function () {
            var o = order(orderId);
            notify(o.email, "email", "policy_issued", orderId);
            notify(o.email, "sms", "policy_issued", orderId);
            onStep && onStep("NOTIFIED");
            resolve(order(orderId));
          }, 800);
        }, 1200);
      }
      next();
    });
  }
  function createPolicy(orderId) {
    var o = order(orderId);
    var q = quote(o.quoteId);
    var prov = provider(o.providerId);
    var s = get();
    var start = o.policyStart ? new Date(o.policyStart) : new Date();
    var pol = {
      id: "POL-" + prov.mono + "-" + pad(hash(orderId) % 9999999, 7),
      number: prov.mono + "/MTR/" + new Date().getFullYear() + "/" + pad(hash(orderId + "n") % 999999, 6),
      orderId: orderId, customerId: o.customerId, customerName: o.customerName, providerId: o.providerId, product: "motor",
      planName: q ? q.planName : "Motor", insuranceType: q ? q.insuranceType : "comprehensive", repairType: q ? q.repairType : null,
      vehicle: o.vehicle, vehicleId: o.vehicleId, premium: o.premium, vat: o.vat, total: o.total,
      startDate: start.toISOString(), endDate: addDays(addDays(start, 365), -1).toISOString(),
      status: "active", issuedAt: now(), erp: { status: "pending" },
    };
    s.policies.unshift(pol);
    s.orders = s.orders.map(function (x) { if (x.id === orderId) x.policyId = pol.id; return x; });
    // commission (commissions table)
    s.commissions.unshift({ id: "COM-" + pad(s.commissions.length + 301, 5), orderId: orderId, policyId: pol.id, providerId: o.providerId, gross: o.premium, rate: o.commissionRate, amount: Math.round(o.premium * o.commissionRate) / 100, status: "accrued", at: now() });
    s.documents.unshift({ id: "DOC-" + pad(s.documents.length + 9001, 6), orderId: orderId, customerId: o.customerId, type: "policy", label: "Policy schedule", fileName: pol.number.replace(/\//g, "-") + ".pdf", size: 184000, status: "issued", uploadedAt: now(), storage: "s3://delta-private/policies/" + pol.id });
    save(s);
    apiLog(o.providerId, "createPolicy", 2380, "201 Created", orderId);
    audit("System", "policy.issued", "policies", pol.id);
    return pol;
  }
  function policy(id) { return get().policies.filter(function (p) { return p.id === id; })[0]; }

  /* -------------------------------- ERP ---------------------------------- */
  function syncErp(orderId, fail) {
    var s = get();
    var o = order(orderId);
    var entities = ["customer", "invoice", "payment", "policy"];
    var status = fail ? "failed" : "synced";
    entities.forEach(function (e, i) {
      var failed = fail && (e === "invoice" || e === "policy");
      s.erpSync.unshift({
        id: "ERP-" + pad(s.erpSync.length + 1 + i, 5), orderId: orderId, entity: e,
        ref: e === "customer" ? o.customerId : e === "policy" ? o.policyId : e === "payment" ? (o.paymentIds || []).slice(-1)[0] : "INV-" + orderId.slice(-6),
        status: failed ? "failed" : "synced", erpRecordId: failed ? null : "ERP" + pad(hash(orderId + e) % 999999, 6),
        attempts: 1, lastAttemptAt: now(), nextRetryAt: failed ? addMin(new Date(), 10).toISOString() : null,
        error: failed ? "503 Service Unavailable — ERP endpoint /api/invoices timed out" : null,
      });
    });
    s.orders = s.orders.map(function (x) { if (x.id === orderId) x.erp = { status: status, at: now() }; return x; });
    s.policies = s.policies.map(function (p) { if (p.orderId === orderId) p.erp = { status: status, at: now() }; return p; });
    save(s);
    if (fail) { audit("System", "erp.sync_failed", "erp_sync_logs", orderId); notify("finance@deltains-brokers.com", "internal", "admin_erp_sync_failed", orderId); }
    return { status: status };
  }
  function retryErp(syncId, actor) {
    var s = get();
    var orderId = null;
    s.erpSync = s.erpSync.map(function (e) {
      if (e.id === syncId) {
        e.attempts++; e.lastAttemptAt = now(); e.status = "synced"; e.error = null; e.nextRetryAt = null;
        e.erpRecordId = "ERP" + pad(hash(e.orderId + e.entity) % 999999, 6); orderId = e.orderId;
      }
      return e;
    });
    save(s);
    if (orderId) {
      var pending = get().erpSync.filter(function (e) { return e.orderId === orderId && e.status !== "synced"; }).length;
      if (!pending) {
        s = get();
        s.orders = s.orders.map(function (x) { if (x.id === orderId) x.erp = { status: "synced", at: now() }; return x; });
        s.policies = s.policies.map(function (p) { if (p.orderId === orderId) p.erp = { status: "synced", at: now() }; return p; });
        save(s);
      }
    }
    audit(actor || "Admin", "erp.retry", "erp_sync_logs", syncId);
  }

  /* -------------------------------- leads -------------------------------- */
  function createLead(data) {
    var s = get();
    var id = "LEAD-" + pad(s.leads.length + 3101, 5);
    s.leads.unshift(Object.assign({ id: id, at: now(), status: "new", assignee: null, channel: "website" }, data));
    save(s);
    notify("sales@deltains-brokers.com", "internal", "new_consultation_request", id);
    return id;
  }

  /* ------------------------------- session ------------------------------- */
  function login(email) {
    var s = get();
    var c = s.customers.filter(function (x) { return x.email.toLowerCase() === String(email).toLowerCase(); })[0];
    if (!c) return null;
    s.session.customerId = c.id;
    save(s);
    return c;
  }
  function logout() { var s = get(); s.session.customerId = null; save(s); }
  function me() { var s = get(); return s.customers.filter(function (c) { return c.id === s.session.customerId; })[0] || null; }

  /* =============================== SEED ================================= */
  function seed() {
    var s = {
      version: 1, createdAt: now(),
      settings: { simulateProviderTimeout: false, timeoutProvider: "adnic", simulatePaymentFailure: false, simulateErpFailure: false },
      session: { customerId: null }, current: {}, counters: {},
      customers: [], vehicles: [], quoteRequests: [], quotes: [], orders: [], payments: [], policies: [],
      documents: [], erpSync: [], notifications: [], audit: [], apiLogs: [], leads: [], commissions: [],
      productOverrides: {}, providerOverrides: {},
    };
    var people = [
      ["Omar Al Mansoori", "United Arab Emirates", "Sharjah"], ["Fatima Haddad", "Jordan", "Dubai"], ["Rahul Menon", "India", "Sharjah"],
      ["Sara Khalil", "Lebanon", "Dubai"], ["Ahmed Farouk", "Egypt", "Ajman"], ["Maria Santos", "Philippines", "Dubai"],
      ["Khalid Al Suwaidi", "United Arab Emirates", "Abu Dhabi"], ["James Whitfield", "United Kingdom", "Dubai"],
      ["Layla Nasser", "Syria", "Sharjah"], ["Hassan Qureshi", "Pakistan", "Sharjah"], ["Noura Al Ali", "United Arab Emirates", "Ras Al Khaimah"],
      ["Yousef Barakat", "Palestine", "Sharjah"],
    ];
    var cars = [["Toyota", "Land Cruiser", 2023], ["Nissan", "Patrol", 2022], ["Toyota", "Camry", 2021], ["Hyundai", "Tucson", 2024], ["Kia", "Sportage", 2020], ["Honda", "Civic", 2022],
      ["Lexus", "LX", 2024], ["BMW", "X5", 2021], ["Mitsubishi", "Pajero", 2019], ["Toyota", "Corolla", 2023], ["Nissan", "Altima", 2022], ["Tesla", "Model Y", 2024]];
    var provIds = ["sukoon", "orient", "adnic"];
    var states = ["POLICY_ISSUED", "POLICY_ISSUED", "POLICY_ISSUED", "PAYMENT_PENDING", "POLICY_ISSUED", "DOCUMENTS_PENDING", "POLICY_ISSUED", "INSURANCE_PROCESSING", "PAYMENT_FAILED", "POLICY_ISSUED", "QUOTE_EXPIRED", "QUOTES_RECEIVED"];
    var base = new Date();
    people.forEach(function (p, i) {
      var cid = "CUS-" + pad(1041 + i, 5);
      var email = p[0].toLowerCase().replace(/[^a-z ]/g, "").split(" ").join(".") + "@example.com";
      if (i === 0) email = "omar@example.com";
      s.customers.push({ id: cid, name: p[0], email: email, mobile: "+971 5" + (i % 6) + " " + pad(100 + i * 37, 3) + " " + pad(4000 + i * 311, 4), nationality: p[1], emirate: p[2], emiratesId: "784-19" + (80 + i) + "-" + pad(1234567 + i * 7919, 7) + "-" + (i % 9), dob: new Date(1980 + (i % 15), i % 12, 3 + i).toISOString(), createdAt: addDays(base, -60 + i * 4).toISOString(), status: "active" });
      var car = cars[i];
      var vid = "VEH-" + pad(2201 + i, 5);
      var value = vehicleValue(car[0], car[1], car[2]);
      s.vehicles.push({ id: vid, customerId: cid, make: car[0], model: car[1], year: car[2], value: value, plate: String(10000 + i * 731), plateCode: ["1", "2", "3", "A", "B", "K"][i % 6], emirate: p[2], chassis: "JTM" + pad(hash(vid) % 99999999999999, 14), createdAt: addDays(base, -60 + i * 4).toISOString() });
      var qrId = "QR-" + base.getFullYear() + "-" + pad(900 + i, 6);
      var created = addDays(base, -40 + i * 3);
      var input = { customer: { name: p[0], email: email, dob: new Date(1980 + (i % 15), 1, 1).toISOString(), experience: 3 + (i % 8), noClaimsYears: i % 4, nationality: p[1], emirate: p[2] }, vehicle: { make: car[0], model: car[1], year: car[2], value: value, body: bodyOf(car[0], car[1]) }, cover: { type: i % 5 === 3 ? "third_party" : "comprehensive", repair: "agency", addons: { roadside: true } } };
      var qr = { id: qrId, product: "motor", createdAt: created.toISOString(), completedAt: addMin(created, 1).toISOString(), status: "QUOTES_RECEIVED", customer: input.customer, vehicle: input.vehicle, cover: input.cover, providers: {}, quoteIds: [], customerId: cid };
      provIds.forEach(function (pid) {
        var prov = D.PROVIDERS.filter(function (x) { return x.id === pid; })[0];
        qr.providers[pid] = { status: i === 7 && pid === "adnic" ? "timeout" : "success", ms: Math.round(prov.avgResponseMs * (0.8 + rnd(i + pid.length) * 0.4)) };
        if (qr.providers[pid].status === "timeout") return;
        D.MOTOR_PLANS[pid].forEach(function (plan) {
          var pr = priceFor(plan, prov, input);
          var q = { id: "Q-" + prov.mono + "-" + pad(hash(qrId + plan.code) % 1000000, 6), quoteRequestId: qrId, providerId: pid, providerName: prov.name, planCode: plan.code, planName: plan.name, insuranceType: plan.type, repairType: plan.repair, deductible: plan.deductible, benefits: { roadside: plan.roadside, rentACar: plan.rentACar, gcc: plan.gcc, oman: plan.oman, pa: plan.pa, windscreen: plan.windscreen }, premium: pr.premium, addons: pr.addons, addonTotal: pr.addonTotal, vat: pr.vat, total: pr.total, currency: "AED", sumInsured: plan.type === "third_party" ? null : value, validUntil: addDays(created, 7).toISOString(), createdAt: created.toISOString(), providerRef: pid.toUpperCase() + "-" + pad(hash(plan.code + qrId) % 99999999, 8) };
          s.quotes.push(q); qr.quoteIds.push(q.id);
        });
      });
      s.quoteRequests.push(qr);

      var st = states[i];
      if (st === "QUOTES_RECEIVED" || st === "QUOTE_EXPIRED") { qr.status = st; return; }
      var myQuotes = s.quotes.filter(function (q) { return q.quoteRequestId === qrId && q.insuranceType === input.cover.type; });
      var chosen = myQuotes[i % myQuotes.length];
      qr.status = "QUOTE_SELECTED"; qr.selectedQuoteId = chosen.id;
      var oid = "ORD-" + base.getFullYear() + "-" + pad(500 + i, 6);
      var prov2 = D.PROVIDERS.filter(function (x) { return x.id === chosen.providerId; })[0];
      var hist = [{ state: "QUOTE_REQUESTED", at: created.toISOString(), actor: "Customer" }, { state: "QUOTES_RECEIVED", at: addMin(created, 1).toISOString(), actor: "System" }, { state: "QUOTE_SELECTED", at: addMin(created, 6).toISOString(), actor: "Customer", note: chosen.providerName + " — " + chosen.planName }];
      var order = { id: oid, product: "motor", quoteRequestId: qrId, quoteId: chosen.id, providerId: chosen.providerId, customerId: cid, customerName: p[0], email: email, vehicle: car[2] + " " + car[0] + " " + car[1], vehicleId: vid, premium: chosen.premium + chosen.addonTotal, vat: chosen.vat, total: chosen.total, commissionRate: prov2.commission, state: st, createdAt: addMin(created, 6).toISOString(), updatedAt: addMin(created, 30).toISOString(), history: hist, paymentIds: [], documentIds: [], erp: { status: "not_started" } };
      var reach = ["CUSTOMER_DETAILS_PENDING", "DOCUMENTS_PENDING"];
      if (st !== "DOCUMENTS_PENDING") reach.push("PAYMENT_PENDING");
      reach.forEach(function (r, k) { hist.push({ state: r, at: addMin(created, 10 + k * 3).toISOString(), actor: k === 2 ? "System" : "Customer" }); });
      if (st !== "DOCUMENTS_PENDING") {
        D.DOCUMENT_TYPES.filter(function (dt) { return dt.required; }).forEach(function (dt, k) {
          var did = "DOC-" + pad(5001 + i * 10 + k, 6);
          s.documents.push({ id: did, orderId: oid, customerId: cid, type: dt.id, label: dt.label, fileName: dt.id + "_" + p[0].split(" ")[0].toLowerCase() + ".pdf", size: 240000 + k * 51000, status: st === "PAYMENT_PENDING" ? "pending_review" : "verified", uploadedAt: addMin(created, 12).toISOString(), storage: "s3://delta-private/orders/" + oid + "/" + did });
          order.documentIds.push(did);
        });
      }
      if (["POLICY_ISSUED", "INSURANCE_PROCESSING", "PAYMENT_FAILED"].indexOf(st) > -1) {
        var attempts = st === "PAYMENT_FAILED" ? 1 : i === 4 ? 2 : 1;
        for (var a = 1; a <= attempts; a++) {
          var ok = !(st === "PAYMENT_FAILED" || (attempts === 2 && a === 1));
          var pid2 = "PAY-" + pad(7001 + i * 3 + a, 6);
          s.payments.push({ id: pid2, orderId: oid, attempt: a, amount: chosen.total, currency: "AED", method: "card", cardBrand: a % 2 ? "Visa" : "Mastercard", last4: pad(1000 + i * 97 % 9000, 4), gateway: "Demo Hosted Gateway", gatewayRef: "GW" + pad(hash(pid2) % 99999999, 8), status: ok ? "captured" : "declined", reason: ok ? null : "Card declined by issuer", at: addMin(created, 20 + a * 4).toISOString() });
          order.paymentIds.push(pid2);
          hist.push({ state: ok ? "PAYMENT_SUCCESS" : "PAYMENT_FAILED", at: addMin(created, 20 + a * 4).toISOString(), actor: "Payment Gateway", note: "Attempt #" + a });
        }
      }
      if (st === "POLICY_ISSUED" || st === "INSURANCE_PROCESSING") {
        hist.push({ state: "POLICY_REQUESTED", at: addMin(created, 32).toISOString(), actor: "System" });
        hist.push({ state: "INSURANCE_PROCESSING", at: addMin(created, 33).toISOString(), actor: "Insurer" });
      }
      if (st === "POLICY_ISSUED") {
        var issued = addMin(created, 41);
        hist.push({ state: "POLICY_ISSUED", at: issued.toISOString(), actor: "Insurer" });
        var erpFail = i === 9;
        var pol = { id: "POL-" + prov2.mono + "-" + pad(hash(oid) % 9999999, 7), number: prov2.mono + "/MTR/" + base.getFullYear() + "/" + pad(hash(oid + "n") % 999999, 6), orderId: oid, customerId: cid, customerName: p[0], providerId: chosen.providerId, product: "motor", planName: chosen.planName, insuranceType: chosen.insuranceType, repairType: chosen.repairType, vehicle: order.vehicle, vehicleId: vid, premium: order.premium, vat: order.vat, total: order.total, startDate: addDays(created, 1).toISOString(), endDate: addDays(created, 365).toISOString(), status: "active", issuedAt: issued.toISOString(), erp: { status: erpFail ? "failed" : "synced", at: addMin(issued, 2).toISOString() } };
        s.policies.push(pol);
        order.policyId = pol.id;
        order.erp = pol.erp;
        s.commissions.push({ id: "COM-" + pad(301 + i, 5), orderId: oid, policyId: pol.id, providerId: chosen.providerId, gross: order.premium, rate: prov2.commission, amount: Math.round(order.premium * prov2.commission) / 100, status: i % 3 === 0 ? "received" : "accrued", at: issued.toISOString() });
        s.documents.push({ id: "DOC-" + pad(9001 + i, 6), orderId: oid, customerId: cid, type: "policy", label: "Policy schedule", fileName: pol.number.replace(/\//g, "-") + ".pdf", size: 184000, status: "issued", uploadedAt: issued.toISOString(), storage: "s3://delta-private/policies/" + pol.id });
        ["customer", "invoice", "payment", "policy"].forEach(function (e, k) {
          var f = erpFail && (e === "invoice" || e === "policy");
          s.erpSync.push({ id: "ERP-" + pad(100 + i * 4 + k, 5), orderId: oid, entity: e, ref: e === "customer" ? cid : e === "policy" ? pol.id : e === "payment" ? order.paymentIds.slice(-1)[0] : "INV-" + oid.slice(-6), status: f ? "failed" : "synced", erpRecordId: f ? null : "ERP" + pad(hash(oid + e) % 999999, 6), attempts: f ? 3 : 1, lastAttemptAt: addMin(issued, 2 + k).toISOString(), nextRetryAt: f ? addMin(new Date(), 25).toISOString() : null, error: f ? "422 Unprocessable — cost centre DLT-MTR not mapped in ERP" : null });
        });
        s.notifications.push({ id: "NTF-" + pad(i + 1, 5), at: issued.toISOString(), to: email, channel: "email", template: "policy_issued", ref: oid, status: "delivered" });
      }
      s.orders.push(order);
    });
    // Extra quote requests without purchase (abandoned / still comparing) — realistic funnel
    for (var x = 0; x < 22; x++) {
      var pp = people[(x * 5 + 3) % people.length], cc = cars[(x * 7 + 2) % cars.length];
      var cr = addMin(addDays(base, -(x % 14) - (x > 13 ? 1 : 0)), -((x * 97) % 600));
      var qid = "QR-" + base.getFullYear() + "-" + pad(700 + x, 6);
      var val = vehicleValue(cc[0], cc[1], cc[2]);
      var inp = { customer: { name: pp[0], email: pp[0].toLowerCase().replace(/[^a-z ]/g, "").split(" ").join(".") + "@example.com", dob: new Date(1978 + (x % 18), 5, 9).toISOString(), experience: 1 + (x % 6), noClaimsYears: x % 3, nationality: pp[1], emirate: pp[2] }, vehicle: { make: cc[0], model: cc[1], year: cc[2], value: val, body: bodyOf(cc[0], cc[1]) }, cover: { type: x % 4 === 0 ? "third_party" : "comprehensive", repair: "any", addons: { roadside: x % 2 === 0 } } };
      var q2 = { id: qid, product: "motor", createdAt: cr.toISOString(), completedAt: addMin(cr, 1).toISOString(), status: (Date.now() - cr.getTime()) / 86400000 > 7 ? "QUOTE_EXPIRED" : "QUOTES_RECEIVED", customer: inp.customer, vehicle: inp.vehicle, cover: inp.cover, providers: {}, quoteIds: [], customerId: null };
      provIds.forEach(function (pid) {
        var prov = D.PROVIDERS.filter(function (y) { return y.id === pid; })[0];
        var to = x % 9 === 4 && pid === "adnic";
        q2.providers[pid] = { status: to ? "timeout" : "success", ms: to ? prov.timeoutMs : Math.round(prov.avgResponseMs * (0.75 + rnd(x + pid.length) * 0.5)) };
        if (to) return;
        D.MOTOR_PLANS[pid].forEach(function (plan) {
          var pr = priceFor(plan, prov, inp);
          var qq = { id: "Q-" + prov.mono + "-" + pad(hash(qid + plan.code) % 1000000, 6), quoteRequestId: qid, providerId: pid, providerName: prov.name, planCode: plan.code, planName: plan.name, insuranceType: plan.type, repairType: plan.repair, deductible: plan.deductible, benefits: { roadside: plan.roadside, rentACar: plan.rentACar, gcc: plan.gcc, oman: plan.oman, pa: plan.pa, windscreen: plan.windscreen }, premium: pr.premium, addons: pr.addons, addonTotal: pr.addonTotal, vat: pr.vat, total: pr.total, currency: "AED", sumInsured: plan.type === "third_party" ? null : val, validUntil: addDays(cr, 7).toISOString(), createdAt: cr.toISOString(), providerRef: pid.toUpperCase() + "-" + pad(hash(plan.code + qid) % 99999999, 8) };
          s.quotes.push(qq); q2.quoteIds.push(qq.id);
        });
      });
      s.quoteRequests.push(q2);
    }
    // Expiring policy reminder data point
    if (s.policies[1]) { s.policies[1].endDate = addDays(base, 21).toISOString(); }

    // Leads for non-digital products (consultation / request-a-quote forms)
    var leads = [
      ["medical", "Family health cover for 4 members", "Mona Adel", "Sharjah", "new"],
      ["home", "Villa contents & building cover", "Peter Grant", "Dubai", "contacted"],
      ["travel", "Annual multi-trip for a family", "Aisha Rahman", "Sharjah", "quoted"],
      ["boat", "Leisure boat — 32ft, Dubai Marina", "Faisal Karim", "Dubai", "new"],
      ["pet", "Two cats — accident & illness", "Nadia Farah", "Ajman", "contacted"],
      ["medical", "SME group medical — 18 employees", "Tariq Aziz", "Sharjah", "won"],
    ];
    leads.forEach(function (l, i) {
      s.leads.push({ id: "LEAD-" + pad(3101 + i, 5), product: l[0], message: l[1], name: l[2], emirate: l[3], email: l[2].toLowerCase().split(" ").join(".") + "@example.com", mobile: "+971 50 " + pad(200 + i * 13, 3) + " " + pad(1100 + i * 57, 4), status: l[4], assignee: l[4] === "new" ? null : ["Rania (Sales)", "Imran (Sales)"][i % 2], at: addDays(base, -i * 2 - 1).toISOString(), channel: "website" });
    });

    // API logs & audit trail samples
    for (var k = 0; k < 24; k++) {
      var pv = provIds[k % 3];
      var pr0 = D.PROVIDERS.filter(function (x) { return x.id === pv; })[0];
      s.apiLogs.push({ at: addMin(base, -k * 37).toISOString(), provider: pv, op: k % 5 === 0 ? "createPolicy" : "getQuote", ms: Math.round(pr0.avgResponseMs * (0.7 + rnd(k) * 0.6)), status: k === 7 ? "TIMEOUT" : k % 5 === 0 ? "201 Created" : "200 OK", ref: "QR-" + base.getFullYear() + "-" + pad(900 + (k % 12), 6) });
    }
    var au = [
      ["Huda (Insurance Manager)", "commission.updated", "insurance_providers", "orient", { commission: 11 }, { commission: 12 }],
      ["Sameer (Technical Admin)", "provider.credentials_rotated", "provider_credentials", "sukoon", null, null],
      ["Rania (Sales)", "lead.status_changed", "leads", "LEAD-03102", { status: "new" }, { status: "contacted" }],
      ["Finance Team", "erp.retry", "erp_sync_logs", "ERP-00136", null, null],
      ["Compliance Officer", "document.viewed", "documents", "DOC-005021", null, null],
      ["Super Admin", "user.role_changed", "users", "USR-0007", { status: "support" }, { status: "operations" }],
      ["Unknown", "auth.login_failed", "users", "admin@deltains-brokers.com", null, null],
      ["Super Admin", "auth.login", "users", "USR-0001", null, null],
    ];
    au.forEach(function (a, i) { s.audit.push({ id: "AUD-" + pad(i + 1, 5), at: addMin(base, -(i + 1) * 95).toISOString(), actor: a[0], action: a[1], entity: a[2], entityId: a[3], ip: "94.200.14." + (20 + i * 7), before: a[4], after: a[5] }); });

    ["quoteRequests", "orders", "payments", "policies", "documents", "erpSync", "commissions", "customers", "vehicles"].forEach(function (k) {
      s[k].sort(function (a, b) { return new Date(b.createdAt || b.at || b.issuedAt || b.uploadedAt || b.lastAttemptAt) - new Date(a.createdAt || a.at || a.issuedAt || a.uploadedAt || a.lastAttemptAt); });
    });
    s.users = [
      { id: "USR-0001", name: "Super Admin", email: "admin@deltains-brokers.com", role: "super_admin", twofa: true, lastLogin: addMin(base, -30).toISOString(), status: "active" },
      { id: "USR-0002", name: "Huda Karam", email: "huda@deltains-brokers.com", role: "insurance", twofa: true, lastLogin: addMin(base, -300).toISOString(), status: "active" },
      { id: "USR-0003", name: "Rania Saeed", email: "rania@deltains-brokers.com", role: "support", twofa: true, lastLogin: addMin(base, -80).toISOString(), status: "active" },
      { id: "USR-0004", name: "Imran Sheikh", email: "imran@deltains-brokers.com", role: "operations", twofa: true, lastLogin: addMin(base, -1440).toISOString(), status: "active" },
      { id: "USR-0005", name: "Finance Team", email: "finance@deltains-brokers.com", role: "finance", twofa: true, lastLogin: addMin(base, -600).toISOString(), status: "active" },
      { id: "USR-0006", name: "Compliance Officer", email: "compliance@deltains-brokers.com", role: "compliance", twofa: true, lastLogin: addMin(base, -2900).toISOString(), status: "active" },
      { id: "USR-0007", name: "Sameer Joshi", email: "sameer@deltains-brokers.com", role: "tech", twofa: false, lastLogin: addMin(base, -120).toISOString(), status: "active" },
      { id: "USR-0008", name: "Board Viewer", email: "board@deltains-brokers.com", role: "readonly", twofa: true, lastLogin: null, status: "invited" },
    ];
    return s;
  }

  w.DELTA.store = {
    get: get, save: save, reset: reset, settings: settings, setSetting: setSetting,
    products: products, product: product, updateProduct: updateProduct,
    providers: providers, provider: provider, updateProvider: updateProvider,
    vehicleValue: vehicleValue, bodyOf: bodyOf,
    createQuoteRequest: createQuoteRequest, quoteRequest: quoteRequest, runQuoteEngine: runQuoteEngine,
    quotesFor: quotesFor, quote: quote, selectQuote: selectQuote, order: order, setOrderState: setOrderState,
    completeCheckout: completeCheckout, createPaymentAttempt: createPaymentAttempt, issuePolicy: issuePolicy,
    policy: policy, syncErp: syncErp, retryErp: retryErp, createLead: createLead,
    login: login, logout: logout, me: me, audit: audit, notify: notify,
  };

  /* Insurer logo with automatic fallback to a monogram when the logo file is missing */
  var MONO = { awnic: ["AW", "#7A5C2E"], din: ["DI", "#0B5E8E"], sukoon: ["SK", "#2E7D6B"], orient: ["OI", "#8A5A1F"], adnic: ["AD", "#6B3FA0"], gig: ["GG", "#B03A48"], albuhaira: ["AB", "#1F6FA8"] };
  function logoFallback(img) {
    var pid = img.getAttribute("data-pid"), m = MONO[pid] || ["?", "#256383"], wrap = img.parentNode;
    if (!wrap) return;
    wrap.className = wrap.className.replace("ins-logo", "d-mono mono-d") + " is-fallback";
    wrap.style.setProperty("--tint", m[1]);
    wrap.textContent = m[0];
  }
  function logo(pid, size) {
    var p = (w.DELTA.PROVIDERS || []).filter(function (x) { return x.id === pid; })[0] || { name: pid };
    return '<span class="ins-logo ' + (size ? "ins-logo--" + size : "") + '" title="' + esc(p.name) + '"><img src="images/insurers/' + pid + '.png" alt="' + esc(p.name) + '" data-pid="' + pid + '" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span>';
  }
  function fixLogos() { Array.prototype.forEach.call(document.querySelectorAll("img[data-pid]"), function (i) { if (i.complete && !i.naturalWidth) logoFallback(i); }); }
  if (typeof document !== "undefined") document.addEventListener("DOMContentLoaded", function () { fixLogos(); setTimeout(fixLogos, 800); });
  w.DELTA.util = { logo: logo, logoFallback: logoFallback, fixLogos: fixLogos, money: money, money0: money0, fmtDate: fmtDate, ago: ago, esc: esc, qs: qs, pad: pad, hash: hash, addDays: addDays };
})(window);
