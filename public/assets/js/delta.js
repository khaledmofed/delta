/* Delta Insurance — shared UI helpers (header, demo panel, toasts) */
(function (w, d) {
  "use strict";
  var S = w.DELTA.store, U = w.DELTA.util;

  function $(sel, ctx) { return (ctx || d).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || d).querySelectorAll(sel)); }

  function toast(msg, type) {
    var wrap = $(".d-toast-wrap");
    if (!wrap) { wrap = d.createElement("div"); wrap.className = "d-toast-wrap"; d.body.appendChild(wrap); }
    var t = d.createElement("div");
    t.className = "d-toast " + (type || "");
    var ic = type === "ok" ? "fa-circle-check" : type === "err" ? "fa-circle-exclamation" : "fa-circle-info";
    t.innerHTML = '<i class="fas ' + ic + '"></i><div>' + msg + "</div>";
    wrap.appendChild(t);
    setTimeout(function () { t.style.opacity = "0"; t.style.transition = "0.3s"; }, 3600);
    setTimeout(function () { t.remove(); }, 4000);
  }

  function mono(pid, size) { return U.logo(pid, size); }


  /* header, mobile nav, reveal, back-to-top */
  function initNav() {
    var nav = $(".mnav");
    $$("[data-open-nav]").forEach(function (b) { b.addEventListener("click", function () { nav && nav.classList.add("open"); }); });
    nav && nav.addEventListener("click", function (e) { if (e.target === nav || e.target.closest(".close")) nav.classList.remove("open"); });
    var me = S.me();
    $$("[data-account-label]").forEach(function (el) { el.textContent = me ? me.name.split(" ")[0] : "Sign in"; });
    var hdr = $("#hdr"), top = $(".to-top");
    function onScroll() { var y = w.scrollY; hdr && hdr.classList.toggle("scrolled", y > 50); top && top.classList.toggle("show", y > 700); }
    w.addEventListener("scroll", onScroll, { passive: true }); onScroll();
    top && top.addEventListener("click", function (e) { e.preventDefault(); w.scrollTo({ top: 0, behavior: "smooth" }); });
    $$("[data-year]").forEach(function (e) { e.textContent = new Date().getFullYear(); });
    var els = $$(".rv");
    if ("IntersectionObserver" in w) {
      var io = new IntersectionObserver(function (en) { en.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add("in"); io.unobserve(x.target); } }); }, { rootMargin: "0px 0px -8% 0px" });
      els.forEach(function (el) { io.observe(el); });
    } else els.forEach(function (el) { el.classList.add("in"); });
    $$(".ftr .big").forEach(function (el) {
      if (!("IntersectionObserver" in w)) { el.classList.add("in"); return; }
      var o = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { el.classList.add("in"); o.disconnect(); } }, { threshold: 0.15 });
      o.observe(el);
    });
  }

  /* demo controls */
  function initDemo() {
    var fab = $(".d-demo-fab");
    if (!fab) return;
    fab.querySelector("button").addEventListener("click", function () { fab.classList.toggle("open"); });
    var set = S.settings();
    $$("[data-demo-setting]", fab).forEach(function (inp) {
      var k = inp.getAttribute("data-demo-setting");
      inp.checked = !!set[k];
      inp.addEventListener("change", function () {
        S.setSetting(k, inp.checked);
        toast("Demo: " + inp.closest("label").querySelector("span").textContent + (inp.checked ? " ON" : " OFF"));
      });
    });
    var r = $("[data-demo-reset]", fab);
    r && r.addEventListener("click", function () { S.reset(); toast("Demo data reset", "ok"); setTimeout(function () { location.reload(); }, 600); });
  }

  /* quick quote (home + motor page) */
  function initQuick() {
    $$("[data-quick-quote]").forEach(function (form) {
      var mk = $("[name=make]", form), md = $("[name=model]", form), yr = $("[name=year]", form);
      if (mk) {
        mk.innerHTML = '<option value="">Make</option>' + Object.keys(w.DELTA.VEHICLES).map(function (m) { return "<option>" + m + "</option>"; }).join("");
        mk.addEventListener("change", function () {
          var models = w.DELTA.VEHICLES[mk.value] || {};
          md.innerHTML = '<option value="">Model</option>' + Object.keys(models).map(function (m) { return "<option>" + m + "</option>"; }).join("");
        });
      }
      if (yr) {
        var y = new Date().getFullYear() + 1, h = '<option value="">Year</option>';
        for (var i = y; i >= y - 15; i--) h += "<option>" + i + "</option>";
        yr.innerHTML = h;
      }
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var p = new URLSearchParams();
        if (mk && mk.value) p.set("make", mk.value);
        if (md && md.value) p.set("model", md.value);
        if (yr && yr.value) p.set("year", yr.value);
        location.href = "/motor-quote" + (p.toString() ? "?" + p.toString() : "");
      });
    });
  }

  /* consultation / request-a-quote forms for non-digital products */
  function initLeadForms() {
    $$("[data-lead-form]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var ok = true;
        $$("[required]", form).forEach(function (f) { var bad = !String(f.value).trim(); f.classList.toggle("is-invalid", bad); if (bad) ok = false; });
        if (!ok) { toast("Please complete the required fields.", "err"); return; }
        var data = {};
        $$("input,select,textarea", form).forEach(function (f) { if (f.name) data[f.name] = f.type === "checkbox" ? f.checked : f.value; });
        data.product = data.product || form.getAttribute("data-product") || "general";
        var id = S.createLead(data);
        form.innerHTML = '<div class="text-center" style="padding:30px 10px"><span style="width:76px;height:76px;border-radius:50%;background:var(--mint);color:var(--navy);display:inline-grid;place-items:center;font-size:30px"><i class="far fa-check"></i></span><h3 class="d-h3 mt-20">Thank you, ' + U.esc((data.name || "").split(" ")[0]) + '.</h3><p class="d-muted mt-10">Your request <b style="color:var(--ink)">' + id + '</b> has been received. A Delta advisor will contact you shortly.</p><p class="d-muted-2" style="font-size:13px;margin:0">Demo: this request now appears in Admin → Leads.</p></div>';
      });
    });
  }

  d.addEventListener("DOMContentLoaded", function () {
    initNav(); initDemo(); initQuick(); initLeadForms();
  });

  w.DELTA.ui = { $: $, $$: $$, toast: toast, mono: mono };
})(window, document);
