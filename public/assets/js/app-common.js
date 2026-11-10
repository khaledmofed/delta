/* Shared helpers for the Portal and Admin apps: hash router, tables, pills, drawer */
(function (w, d) {
  "use strict";
  var D = w.DELTA, S = D.store, U = D.util;

  var STATE_TONE = {
    POLICY_ISSUED: "ok", PAYMENT_SUCCESS: "ok", QUOTES_RECEIVED: "info", QUOTE_SELECTED: "info", QUOTE_REQUESTED: "info",
    CUSTOMER_DETAILS_PENDING: "warn", DOCUMENTS_PENDING: "warn", PAYMENT_PENDING: "warn", POLICY_REQUESTED: "info", INSURANCE_PROCESSING: "info",
    PAYMENT_FAILED: "bad", DOCUMENTS_REJECTED: "bad", QUOTE_EXPIRED: "mute", INSURANCE_REJECTED: "bad", POLICY_FAILED: "bad", CANCELLED: "mute", REFUNDED: "mute", NO_QUOTES: "bad",
    active: "ok", synced: "ok", captured: "ok", verified: "ok", issued: "ok", received: "ok", won: "ok", healthy: "ok", sent: "ok", delivered: "ok",
    pending: "warn", pending_review: "warn", accrued: "info", new: "info", contacted: "warn", quoted: "info", invited: "mute", degraded: "warn",
    failed: "bad", declined: "bad", rejected: "bad", lost: "mute", down: "bad", not_connected: "mute", not_started: "mute", expired: "mute",
    coming_soon: "warn", inactive: "mute",
  };
  function label(s) {
    if (!s) return "—";
    var st = D.ORDER_STATES.filter(function (x) { return x.id === s; })[0];
    if (st) return st.label;
    return String(s).replace(/_/g, " ").toLowerCase().replace(/^\w/, function (c) { return c.toUpperCase(); });
  }
  function pill(s, text) { return '<span class="pill ' + (STATE_TONE[s] || "mute") + '">' + (text || label(s)) + "</span>"; }
  function mono(pid, lg) { return U.logo(pid, lg ? "lg" : ""); }
  function prov(pid) { var p = S.provider(pid); return '<span class="who">' + mono(pid) + "<span>" + (p ? p.name : pid) + "</span></span>"; }

  function table(cols, rows, opts) {
    opts = opts || {};
    if (!rows.length) return '<div class="card-d empty-d"><i class="fal fa-inbox"></i>' + (opts.empty || "Nothing here yet.") + "</div>";
    return '<div class="tbl-wrap"><table class="tbl"><thead><tr>' + cols.map(function (c) { return '<th class="' + (c.num ? "num" : "") + '">' + c.t + "</th>"; }).join("") + "</tr></thead><tbody>" +
      rows.map(function (r) { return "<tr" + (opts.click ? ' class="click" data-row="' + U.esc(opts.click(r)) + '"' : "") + ">" + cols.map(function (c) { return '<td class="' + (c.num ? "num" : "") + '">' + c.v(r) + "</td>"; }).join("") + "</tr>"; }).join("") + "</tbody></table></div>";
  }

  function stateMachine(order) {
    var hist = order.history || [];
    var reached = {};
    hist.forEach(function (h) { reached[h.state] = h; });
    var alt = D.ORDER_ALT_STATES.filter(function (s) { return reached[s]; });
    var items = D.ORDER_STATES.filter(function (s) { return s.id !== "DRAFT"; }).map(function (s) {
      var h = reached[s.id];
      var cur = order.state === s.id;
      return '<li class="' + (h ? "done" : "todo") + (cur ? " cur" : "") + '"><span class="d">' + (h ? '<i class="fas fa-check"></i>' : "") + "</span><b>" + s.label + "</b><small>" + (h ? U.fmtDate(h.at, true) + " · " + (h.actor || "") + (h.note ? " — " + U.esc(h.note) : "") : "Pending") + "</small></li>";
    });
    var altHtml = alt.map(function (s) { var h = reached[s]; return '<li class="bad"><span class="d"><i class="fas fa-xmark"></i></span><b>' + label(s) + "</b><small>" + U.fmtDate(h.at, true) + (h.note ? " — " + U.esc(h.note) : "") + "</small></li>"; }).join("");
    return '<ul class="sm">' + items.join("") + altHtml + "</ul>";
  }

  function drawer(html) {
    d.getElementById("drawerPn").innerHTML = html;
    d.getElementById("drawer").classList.add("open");
  }
  function closeDrawer() { d.getElementById("drawer").classList.remove("open"); }

  function toast(msg) {
    var t = d.createElement("div"); t.className = "toast-d"; t.innerHTML = msg; d.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 3200);
  }

  function router(routes, titles, fallback) {
    function run() {
      var h = (location.hash || "#/" + fallback).replace(/^#\//, "").split("/");
      var key = h[0] || fallback, arg = h[1] ? decodeURIComponent(h[1]) : null;
      var fn = routes[key] || routes[fallback];
      d.querySelectorAll("[data-nav]").forEach(function (a) { a.classList.toggle("active", a.getAttribute("data-nav") === key); });
      d.getElementById("pageTitle").textContent = titles[key] || titles[fallback];
      d.getElementById("view").innerHTML = fn(arg);
      d.getElementById("side").classList.remove("open");
      w.scrollTo(0, 0);
      d.dispatchEvent(new CustomEvent("view:rendered", { detail: { key: key, arg: arg } }));
    }
    w.addEventListener("hashchange", run);
    run();
    return run;
  }

  d.addEventListener("click", function (e) { if (e.target.closest("[data-close]")) closeDrawer(); });
  d.addEventListener("keydown", function (e) { if (e.key === "Escape") closeDrawer(); });

  w.DELTA.app = { pill: pill, label: label, mono: mono, prov: prov, table: table, stateMachine: stateMachine, drawer: drawer, closeDrawer: closeDrawer, toast: toast, router: router };
})(window, document);
