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


  /* Hero floating card: Coming soon rows move up one row at a time (2.5 s on screen + 0.6 s move), endless.
     The track ends with copies of the first rows, so after the last step it jumps back to 0 invisibly.
     Off when the user prefers reduced motion (the first rows just stay) and paused while the tab is hidden. */
  function initTickers() {
    var reduce = w.matchMedia ? w.matchMedia("(prefers-reduced-motion: reduce)") : null;
    $$("[data-fc-ticker]").forEach(function (box) {
      var track = box.querySelector(".fc-track");
      if (!track) return;
      var originals = track.querySelectorAll(".row-mini:not([aria-hidden])").length;
      var visible = parseInt(getComputedStyle(box).getPropertyValue("--rows"), 10) || 1;
      if (originals <= visible) return;   // nothing to cycle
      var i = 0, timer = null;
      function rowH() { return track.firstElementChild.getBoundingClientRect().height || 47; }
      function reset() { track.classList.remove("is-moving"); i = 0; track.style.transform = "translateY(0)"; }
      function step() { i++; track.classList.add("is-moving"); track.style.transform = "translateY(" + -i * rowH() + "px)"; }
      track.addEventListener("transitionend", function () { if (i >= originals) reset(); });
      function start() { if (!timer && !(reduce && reduce.matches) && !document.hidden) timer = setInterval(step, 3100); }
      function stop() { clearInterval(timer); timer = null; if (i >= originals) reset(); }
      document.addEventListener("visibilitychange", function () { document.hidden ? stop() : start(); });
      if (reduce) {
        var onChange = function () { if (reduce.matches) { stop(); reset(); } else start(); };
        reduce.addEventListener ? reduce.addEventListener("change", onChange) : reduce.addListener && reduce.addListener(onChange);
      }
      start();
    });
  }

  /* header, mobile nav, reveal, back-to-top */
  function initNav() {
    var nav = $(".mnav");
    $$("[data-open-nav]").forEach(function (b) { b.addEventListener("click", function () { nav && nav.classList.add("open"); }); });
    nav && nav.addEventListener("click", function (e) { if (e.target === nav || e.target.closest(".close")) nav.classList.remove("open"); });
    initTickers();
    /* header "My order" menu: click to open, closes on outside click or Escape */
    $$("[data-acc-menu]").forEach(function (m) {
      var b = m.querySelector("button.acc-link");
      function set(o) { m.classList.toggle("open", o); b.setAttribute("aria-expanded", o ? "true" : "false"); }
      b.addEventListener("click", function (e) { e.stopPropagation(); set(!m.classList.contains("open")); });
      document.addEventListener("click", function (e) { if (!m.contains(e.target)) set(false); });
      document.addEventListener("keydown", function (e) { if (e.key === "Escape") set(false); });
    });
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
        // Every filled field (make, model, year, registration emirate...) prefills the motor wizard.
        var p = new URLSearchParams();
        $$("select[name], input[name]", form).forEach(function (f) { if (f.value) p.set(f.name, f.value); });
        var base = form.getAttribute("action") || "/motor-quote";
        location.href = base + (p.toString() ? "?" + p.toString() : "");
      });
    });
  }

  /* consultation / request-a-quote forms for non-digital products + contact page -> LeadController (leads table, emails) */
  function thanks(name, ref) {
    return '<div class="text-center" style="padding:30px 10px"><span style="width:76px;height:76px;border-radius:50%;background:var(--mint);color:var(--navy);display:inline-grid;place-items:center;font-size:30px"><i class="far fa-check"></i></span><h3 class="d-h3 mt-20">Thank you, ' + U.esc(name) + '.</h3><p class="d-muted mt-10">Your request <b style="color:var(--ink)">' + U.esc(ref) + '</b> has been received. A Delta advisor will contact you shortly.</p><p class="d-muted-2" style="font-size:13px;margin:0">We have sent a confirmation to your email.</p></div>';
  }
  function initLeadForms() {
    $$("[data-lead-form]").forEach(function (form) {
      form.addEventListener("input", function (e) { e.target.classList.remove("is-invalid"); });
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var ok = true;
        $$("[required]", form).forEach(function (f) {
          var bad = f.type === "checkbox" ? !f.checked : !String(f.value).trim();
          if (f.type === "email" && !bad) bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value);
          if (f.name === "mobile" && !bad) bad = !/^(971|0)?5\d{8}$/.test(String(f.value).replace(/\D/g, ""));
          f.classList.toggle("is-invalid", bad); if (bad) ok = false;
        });
        if (!ok) { toast("Please complete the required fields.", "err"); return; }
        var btn = form.querySelector("button[type=submit]");
        if (btn) btn.disabled = true;
        fetch(form.action, { method: "POST", body: new FormData(form), headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
          .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); })
          .then(function (r) {
            if (btn) btn.disabled = false;
            if (r.status === 200 && r.body.reference) { form.innerHTML = thanks(r.body.name || "", r.body.reference); return; }
            if (r.status === 422) {
              Object.keys(r.body.errors || {}).forEach(function (k) { var f = form.elements[k]; if (f && f.classList) f.classList.add("is-invalid"); });
              var first = Object.keys(r.body.errors || {})[0];
              toast(first ? r.body.errors[first][0] : "Please check the highlighted fields.", "err");
              return;
            }
            if (r.status === 429) { toast("Too many requests. Please wait a minute and try again.", "err"); return; }
            if (r.status === 419) { toast("Your session expired. Please refresh the page and try again.", "err"); return; }
            toast("We couldn’t send your request. Please call us on +971 6 574 7420.", "err");
          })
          .catch(function () { if (btn) btn.disabled = false; toast("Connection problem. Please try again.", "err"); });
      });
    });
    // Without JavaScript the form posts normally and comes back here with the reference.
    var done = w.DELTA_LEAD_SENT;
    if (done) toast("Thank you — your request " + U.esc(done) + " has been received.", "ok");
    // Server notices (e.g. an expired email link), shown as a toast on pages without their own message box.
    if (w.DELTA_NOTICE && !d.querySelector(".d-note[data-notice]")) toast(U.esc(w.DELTA_NOTICE));
  }

  d.addEventListener("DOMContentLoaded", function () {
    initNav(); initDemo(); initQuick(); initLeadForms();
  });

  w.DELTA.ui = { $: $, $$: $$, toast: toast, mono: mono };
})(window, document);
