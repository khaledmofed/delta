/* Checkout — same browser checks as the prototype; the form then posts to the server, which
   asks the insurer for its payment page (adapter->checkout) and redirects there. */
(function () {
  "use strict";
  var ui = window.DELTA.ui;

  document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("checkoutForm");
    if (!form) return;
    form.addEventListener("input", function (e) {
      e.target.classList.remove("is-invalid");
      var g = e.target.closest(".d-input-group"); if (g) g.classList.remove("is-invalid");
    });
    form.addEventListener("submit", function (e) {
      var ok = true;
      form.querySelectorAll("input[required], select[required]").forEach(function (el) {
        var bad = el.type === "checkbox" ? !el.checked : !String(el.value).trim();
        if (el.type === "email" && !bad) bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
        if (el.name === "mobile" && !bad) bad = !/^5\d{8}$/.test(String(el.value).replace(/\D/g, ""));
        el.classList.toggle("is-invalid", bad);
        var g = el.closest(".d-input-group"); if (g) g.classList.toggle("is-invalid", bad);
        if (bad) ok = false;
      });
      document.getElementById("declErr").style.display = ["d1", "d2", "d3"].every(function (n) { return form.elements[n].checked; }) ? "none" : "block";
      if (!ok) { e.preventDefault(); ui.toast("Please complete the highlighted fields.", "err"); return; }
      var btn = form.querySelector("button[type=submit]");
      btn.disabled = true;
      btn.innerHTML = '<span class="d-mini-spin"></span> Opening secure payment…';
    });
  });
})();
