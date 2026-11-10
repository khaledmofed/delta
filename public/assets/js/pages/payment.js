/* Hosted payment page mock — each submit is a separate Payment Attempt */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui;
  document.addEventListener("DOMContentLoaded", function () {
    var order = S.order(U.qs("order") || (S.get().current || {}).orderId || "");
    var side = document.getElementById("gwSide");
    var form = document.getElementById("payForm");
    if (!order) { side.innerHTML = "<p>No order found.</p>"; form.innerHTML = '<a class="pay" style="display:grid;place-items:center" href="motor-quote.html">Start a quote</a>'; return; }
    if (order.state === "POLICY_ISSUED" || order.state === "PAYMENT_SUCCESS") { location.href = "confirmation.html?order=" + order.id; return; }
    var q = S.quote(order.quoteId);
    var p = S.provider(order.providerId);
    var attempts = (order.paymentIds || []).length;
    side.innerHTML = '<p style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Order summary</p><h3 style="margin-bottom:14px">' + q.planName + "</h3>" +
      '<div class="kv"><span>Insurer</span><b>' + p.name + '</b></div><div class="kv"><span>Vehicle</span><b>' + order.vehicle + '</b></div><div class="kv"><span>Order</span><b>' + order.id + "</b></div>" +
      '<div class="kv"><span>Premium</span><b>' + U.money(order.premium) + '</b></div><div class="kv"><span>VAT 5%</span><b>' + U.money(order.vat) + '</b></div><div class="kv total"><span>Total</span><b>' + U.money(order.total) + "</b></div>" +
      (attempts ? '<p style="font-size:12.5px;margin-top:14px">Previous attempts: ' + attempts + "</p>" : "");
    document.getElementById("payBtn").textContent = "Pay " + U.money(order.total);
    form.holder.value = order.customerName || "";
    form.number.value = "4242 4242 4242 4242";
    form.exp.value = "12/28";
    form.cvv.value = "123";
    if (order.state === "PAYMENT_FAILED") { var er = document.getElementById("gwErr"); er.style.display = "block"; er.innerHTML = "<b>Your last payment was declined.</b> Please try again or use a different card."; }
    form.number.addEventListener("input", function () { var v = this.value.replace(/\D/g, "").slice(0, 16); this.value = v.replace(/(.{4})/g, "$1 ").trim(); });
    form.exp.addEventListener("input", function () { var v = this.value.replace(/\D/g, "").slice(0, 4); this.value = v.length > 2 ? v.slice(0, 2) + "/" + v.slice(2) : v; });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var num = form.number.value.replace(/\D/g, "");
      if (num.length < 15 || !/^\d{2}\/\d{2}$/.test(form.exp.value) || form.cvv.value.length < 3 || !form.holder.value.trim()) { ui.toast("Please check the card details.", "err"); return; }
      var decline = S.settings().simulatePaymentFailure || num === "4000000000000002";
      var ov = document.getElementById("payOverlay");
      ov.classList.add("open");
      setTimeout(function () { document.getElementById("payMsg").textContent = "Authenticating with your bank (3-D Secure)…"; }, 900);
      setTimeout(function () {
        var res = S.createPaymentAttempt(order.id, { brand: num[0] === "5" ? "Mastercard" : num[0] === "3" ? "Amex" : "Visa", last4: num.slice(-4) }, decline);
        if (res.status === "captured") { location.href = "confirmation.html?order=" + encodeURIComponent(order.id); }
        else {
          ov.classList.remove("open");
          var er = document.getElementById("gwErr"); er.style.display = "block";
          er.innerHTML = "<b>Payment declined</b> (attempt #" + res.attempt + "). " + res.reason + ". No money has been taken. You can try again — each try is recorded as a separate payment attempt.";
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
      }, 2300);
    });
  });
})();
