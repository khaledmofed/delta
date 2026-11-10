/* Motor quote wizard — collects everything the insurer API needs (Dubai Insurance / IIRIS GenerateQuote),
   runs the insurer eligibility rules up front, then submits to the Laravel backend (MotorQuoteRequest),
   which validates again, stores the documents privately and starts the quote engine. */
(function () {
  "use strict";
  var D = window.DELTA, S = D.store, U = D.util, ui = D.ui, C = D.DIN_CODES;
  var M = window.MOTOR || { demo: false, insurers: [], contactUrl: "/contact" };
  var form, step = 1, MAX = 5, DRAFT_KEY = "delta_quote_draft_v2", LAST_KEY = "delta_last_form";
  var docs = {};   // type => { type, label, fileName, size, file?: File, sample?: true }

  function opt(list, ph) { return '<option value="">' + (ph || "Select") + "</option>" + list.map(function (v) { return "<option>" + v + "</option>"; }).join(""); }
  function codeOpt(list, ph) { return '<option value="">' + (ph || "Select") + "</option>" + list.map(function (v) { return '<option value="' + v[0] + '">' + U.esc(v[1]) + "</option>"; }).join(""); }
  function label(list, code) { var r = list.filter(function (v) { return v[0] === code; })[0]; return r ? r[1] : ""; }
  function f(name) { return form.elements[name]; }
  function val(name) { var el = f(name); if (!el) return ""; if (el.length && el[0] && el[0].type === "radio") { for (var i = 0; i < el.length; i++) if (el[i].checked) return el[i].value; return ""; } return el.type === "checkbox" ? el.checked : el.value; }
  function isoDate(d) { return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); }
  function years(from, to) { return ((to ? new Date(to) : new Date()) - new Date(from)) / 31557600000; }
  function dmy(iso) { if (!iso) return ""; var p = iso.split("-"); return p[2] + "/" + p[1] + "/" + p[0]; }
  function financed() { return val("financed") === "yes"; }
  function docTypes() { return D.DOCUMENT_TYPES.filter(function (dt) { return !dt.financedOnly || financed(); }); }

  /* ------------------------------------------------------------ setup */
  function fillSelects() {
    f("gender").innerHTML = codeOpt(C.gender);
    f("nationality").innerHTML = codeOpt(C.nationality);
    f("emirate").innerHTML = codeOpt(C.emirate);
    f("ncdYears").innerHTML = codeOpt(C.ncdYears);
    f("trafficTranType").innerHTML = codeOpt(C.trafficTranType.filter(function (t) { return ["101", "102", "103", "104", "109"].indexOf(t[0]) > -1; }));
    f("plateSource").innerHTML = codeOpt(C.plateSource);
    f("plateCode").innerHTML = codeOpt(C.plateCode);
    f("bank").innerHTML = codeOpt(C.bank, "Select bank");
    f("make").innerHTML = opt(Object.keys(D.VEHICLES), "Select make");
    var y = new Date().getFullYear() + 1, h = '<option value="">Select year</option>';
    for (var i = y; i >= y - 20; i--) h += "<option>" + i + "</option>";
    f("year").innerHTML = h;
    var start = f("policyStart"), today = new Date();
    start.min = isoDate(today);
    start.max = isoDate(U.addDays(today, 30));
    start.value = isoDate(U.addDays(today, 1));
  }

  function onMake() {
    var models = D.VEHICLES[f("make").value] || {};
    f("model").innerHTML = opt(Object.keys(models), "Select model");
  }

  /* Demo stand-in for the insurer's EDATA lookup by chassis number */
  var WMI = { JT: "Toyota", JN: "Nissan", JM: "Mitsubishi", JH: "Honda", KM: "Hyundai", KN: "Kia", WD: "Mercedes-Benz", WB: "BMW", "1F": "Ford", "1G": "Chevrolet", "5Y": "Tesla", "7S": "Tesla" };
  function lookupVin() {
    var vin = String(val("chassis")).trim().toUpperCase();
    var hint = document.getElementById("vehHint");
    if (vin.length !== 17) { f("chassis").classList.add("is-invalid"); ui.toast("Enter the 17-character chassis number first.", "err"); return; }
    if (!M.demo) {
      // No insurer vehicle lookup (EDATA) is connected yet: never guess the vehicle outside demo mode.
      hint.innerHTML = '<i class="fal fa-circle-info"></i> Please select the make, model and year as shown on your Mulkiya.';
      f("make").focus();
      return;
    }
    var make = WMI[vin.slice(0, 2)] || Object.keys(D.VEHICLES)[U.hash(vin) % Object.keys(D.VEHICLES).length];
    var models = Object.keys(D.VEHICLES[make]);
    var model = models[U.hash(vin + "m") % models.length];
    var year = new Date().getFullYear() - (U.hash(vin + "y") % 8);
    f("make").value = make; onMake(); f("model").value = model; f("year").value = String(year);
    f("ev").value = make === "Tesla" ? "yes" : "no";
    hint.innerHTML = '<i class="fas fa-circle-check" style="color:#00894f"></i> Vehicle found: <b style="color:var(--ink)">' + year + " " + make + " " + model + "</b> · estimated insured value " + U.money0(S.vehicleValue(make, model, year)) + ' <span class="d-muted-2">(demo — live data comes from the insurer’s EDATA lookup)</span>';
    saveDraft(); renderSummary(); checkElig(3, false);
  }

  /* ------------------------------------------------- insurer eligibility rules
     Mirrors the Dubai Insurance error codes so the customer is told before submitting. */
  function rules(n) {
    var out = [];
    if (n === 1) {
      if (val("dob")) { var age = years(val("dob")); if (age < 25) out.push(["8014", "Online cover is available for drivers aged 25 or over."]); if (age >= 65) out.push(["8015", "Online cover is available for drivers under 65."]); }
      if (val("eidExpiry") && new Date(val("eidExpiry")) < new Date()) out.push(["8013", "Your Emirates ID has expired."]);
    }
    if (n === 2) {
      if (val("licenseTo") && new Date(val("licenseTo")) < new Date()) out.push(["8016", "Your driving licence has expired."]);
      if (val("licenseFrom") && years(val("licenseFrom")) < 1) out.push(["8026", "Your driving licence must have been issued at least 1 year ago."]);
    }
    if (n === 3) {
      if (val("year") && new Date().getFullYear() - Number(val("year")) > 10) out.push(["8017", "Vehicles older than 10 years cannot be insured online."]);
      if (val("gccSpec") === "no") out.push(["8024", "Non-GCC (imported) vehicles cannot be insured online."]);
      if (val("ev") === "yes") out.push(["8023", "Electric vehicles are not covered by our online insurer yet."]);
    }
    return out;
  }
  function checkElig(n, toast) {
    var r = rules(n), box = form.querySelector('[data-elig="' + n + '"]');
    if (!box) return true;
    box.innerHTML = r.length ? '<div class="d-elig__box"><i class="fal fa-triangle-exclamation"></i><div><b>We can’t complete this online</b><ul>' + r.map(function (x) { return "<li>" + x[1] + ' <span class="code">' + x[0] + "</span></li>"; }).join("") + '</ul><span>Our advisors can still help — <a class="d-link" href="' + M.contactUrl + '">request a call back</a> or call <a class="d-link" href="tel:+97165747420">+971 6 574 7420</a>.</span></div></div>' : "";
    if (r.length && toast) ui.toast("This application can’t be completed online — see the message below.", "err");
    return !r.length;
  }

  /* ---------------------------------------------------------- validation */
  function validateStep(n) {
    var pane = form.querySelector('[data-step="' + n + '"]');
    var ok = true;
    pane.querySelectorAll("[required]").forEach(function (el) {
      if (el.closest("[style*='display:none'], [style*='display: none']")) { el.classList.remove("is-invalid"); return; }
      var bad = el.type === "checkbox" ? !el.checked : !String(el.value).trim();
      if (el.type === "email" && !bad) bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
      if (el.name === "mobile" && !bad) bad = !/^5\d{8}$/.test(String(el.value).replace(/\D/g, ""));
      if (el.name === "emiratesId" && !bad) bad = !/^784\d{12}$/.test(String(el.value).replace(/\D/g, ""));
      if (el.name === "chassis" && !bad) bad = String(el.value).trim().length !== 17;
      if (el.name === "policyStart" && !bad) bad = el.value < el.min || el.value > el.max;
      el.classList.toggle("is-invalid", bad);
      var group = el.closest(".d-input-group");
      if (group) group.classList.toggle("is-invalid", bad);
      if (bad) ok = false;
    });
    if (n === 3 && financed() && !val("bank")) { f("bank").classList.add("is-invalid"); ok = false; }
    if (n === 4) {
      var missing = docTypes().filter(function (dt) { return (dt.required || dt.financedOnly) && !docs[dt.id]; });
      missing.forEach(function (dt) { var t = document.querySelector('[data-doc="' + dt.id + '"]'); t && t.classList.add("is-invalid"); });
      document.getElementById("docErr").style.display = missing.length ? "block" : "none";
      if (missing.length) ok = false;
    }
    if (n === 5) document.getElementById("consentErr").style.display = ok ? "none" : "block";
    if (!ok) { var first = pane.querySelector(".is-invalid"); first && first.focus && first.focus(); ui.toast("Please check the highlighted fields.", "err"); return false; }
    return checkElig(n, true);
  }

  /* ------------------------------------------------------------- documents */
  function uploadTile(dt) {
    var done = docs[dt.id];
    var alt = dt.altNew && val("isNew") === "yes" ? dt.altNew : "Clear photo or scan";
    return '<div class="col-md-6"><div class="d-upload' + (done ? " is-done" : "") + '" data-doc="' + dt.id + '"><input type="file" accept=".pdf,.jpg,.jpeg,.png" aria-label="' + dt.label + '" /><span class="ic">' + (done ? '<i class="fas fa-circle-check"></i>' : '<i class="fal fa-cloud-arrow-up"></i>') + '</span><div style="flex:1;min-width:0"><b>' + dt.label + ' <em style="color:var(--bad);font-style:normal">*</em></b><span class="meta"><small>' + (done ? U.esc(done.fileName) + " · " + Math.round(done.size / 1024) + " KB" : alt) + '</small></span><div class="bar"><i></i></div></div>' + (M.demo ? '<button type="button" class="d-btn d-btn--ghost d-btn--sm" data-sample="' + dt.id + '" style="position:relative;z-index:2">Sample</button>' : "") + "</div></div>";
  }
  function renderUploads() { document.getElementById("uploads").innerHTML = docTypes().map(uploadTile).join(""); }
  /* file: the real File to upload; null = demo sample (the server substitutes a placeholder in demo mode only) */
  function setDoc(tile, name, size, instant, file) {
    var id = tile.getAttribute("data-doc");
    var dt = D.DOCUMENT_TYPES.filter(function (x) { return x.id === id; })[0];
    function finish() {
      tile.classList.remove("is-uploading", "is-invalid"); tile.classList.add("is-done");
      tile.querySelector(".ic").innerHTML = '<i class="fas fa-circle-check"></i>';
      tile.querySelector(".meta").innerHTML = "<small>" + U.esc(name) + " · " + Math.round(size / 1024) + " KB · encrypted</small>";
      docs[id] = { type: id, din: dt.din, label: dt.label, fileName: name, size: size };
      if (file) docs[id].file = file; else docs[id].sample = true;
      saveDraft();
    }
    if (instant) return finish();
    tile.classList.remove("is-invalid", "is-done"); tile.classList.add("is-uploading");
    var bar = tile.querySelector(".bar i"), pct = 0;
    var t = setInterval(function () { pct += 14 + Math.random() * 18; bar.style.width = Math.min(pct, 100) + "%"; if (pct >= 100) { clearInterval(t); finish(); } }, 80);
  }
  function sampleName(id) { return id + "_" + (val("name") || "customer").split(" ")[0].toLowerCase() + ".jpg"; }

  /* ------------------------------------------------------------------ data */
  function data() {
    var mk = val("make"), md = val("model"), yr = Number(val("year"));
    var ncd = val("ncdYears");
    return {
      customer: { name: val("name"), nameAr: val("nameAr"), email: val("email"), mobile: "+971 " + val("mobile"), dob: val("dob"), gender: val("gender"),
        nationalityCode: val("nationality"), nationality: label(C.nationality, val("nationality")), emirateCode: val("emirate"), emirate: label(C.emirate, val("emirate")),
        emiratesId: val("emiratesId"), eidExpiry: val("eidExpiry"), licenseNo: val("licenseNo"), licenseFrom: val("licenseFrom"), licenseTo: val("licenseTo"),
        experience: val("licenseFrom") ? Math.floor(years(val("licenseFrom"))) : 5, ncdYears: ncd, noClaimsYears: ncd === "" ? 0 : Number(ncd) },
      vehicle: { make: mk, model: md, year: yr, value: M.demo && mk && md && yr ? S.vehicleValue(mk, md, yr) : 0, body: S.bodyOf(mk, md), chassis: String(val("chassis")).toUpperCase(), tcfNumber: val("tcfNumber"),
        trafficTranType: val("trafficTranType"), isNew: val("isNew") === "yes", gccSpec: val("gccSpec") === "yes", ev: val("ev") === "yes",
        plateSource: val("plateSource"), regEmirate: label(C.plateSource, val("plateSource")), plateCode: val("plateCode"), plate: val("regNumber"), regDt: val("regDt"),
        financed: financed(), bankCode: financed() ? val("bank") : "", bank: financed() ? label(C.bank, val("bank")) : "" },
      cover: { type: val("coverType"), repair: val("repair"), policyStart: val("policyStart"), addons: { roadside: val("roadside"), rentACar: val("rentACar"), pa: val("pa"), gcc: val("gcc") } },
      documents: docTypes().filter(function (dt) { return docs[dt.id]; }).map(function (dt) { return docs[dt.id]; }),
    };
  }

  /* Exactly what the DubaiInsuranceAdapter sends to POST /api/v1/Insurance/GenerateQuote */
  function dinPayload(d) {
    var p = {
      insuredName: { en: d.customer.name, ar: d.customer.nameAr || d.customer.name },
      nationality: d.customer.nationalityCode, nationalId: String(d.customer.emiratesId).replace(/\D/g, ""), idExpiryDt: dmy(d.customer.eidExpiry),
      dateOfBirth: dmy(d.customer.dob), gender: d.customer.gender, emirate: d.customer.emirateCode, emailAddress: d.customer.email,
      mobileNumber: "971" + String(d.customer.mobile).replace(/\D/g, "").replace(/^971/, ""),
      licenseNo: d.customer.licenseNo, licenseFmDt: dmy(d.customer.licenseFrom), licenseToDt: dmy(d.customer.licenseTo),
      chassisNumber: d.vehicle.chassis, regNumber: d.vehicle.plate, regDt: dmy(d.vehicle.regDt), plateCode: d.vehicle.plateCode, plateSource: d.vehicle.plateSource,
      tcfNumber: d.vehicle.tcfNumber, ncdYears: d.customer.ncdYears, trafficTranType: d.vehicle.trafficTranType,
      isVehBrandNew: d.vehicle.isNew ? "Y" : "N", agencyRepairYn: d.cover.type === "comprehensive" && d.cover.repair === "agency" ? "Y" : "N",
    };
    if (d.vehicle.financed) p.bankName = d.vehicle.bankCode;
    p.documentLists = d.documents.map(function (x) { return { code: x.din, name: x.label, base64: "<" + x.fileName + " · base64>", type: (x.fileName.split(".").pop() || "png").toLowerCase() }; });
    return p;
  }

  /* --------------------------------------------------------------- drafts */
  function saveDraft() {
    // Files cannot be kept in sessionStorage: only demo samples survive a reload.
    var keep = {};
    Object.keys(docs).forEach(function (k) { if (docs[k].sample) keep[k] = docs[k]; });
    var o = { _docs: keep };
    Array.prototype.forEach.call(form.elements, function (el) { if (!el.name || el.type === "file") return; if (el.type === "radio") { if (el.checked) o[el.name] = el.value; } else if (el.type === "checkbox") o[el.name] = el.checked; else o[el.name] = el.value; });
    try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(o)); } catch (e) {}
  }
  function applyValues(o) {
    if (o.make) { f("make").value = o.make; onMake(); }
    Object.keys(o).forEach(function (k) {
      var el = f(k); if (!el || k === "make" || k.charAt(0) === "_") return;
      if (el.length && el[0] && el[0].type === "radio") Array.prototype.forEach.call(el, function (r) { r.checked = r.value === o[k]; });
      else if (el.type === "checkbox") el.checked = !!o[k];
      else el.value = o[k];
    });
    if (o._docs) docs = o._docs;
  }
  function loadDraft() {
    var o; try { o = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || "null"); } catch (e) {}
    var p = new URLSearchParams(location.search);
    if (!o && p.get("edit")) {
      try { o = JSON.parse(sessionStorage.getItem(LAST_KEY) || "null"); } catch (e) {}
    }
    if (!o) {
      o = {};
      // demo prefill so a presentation can move fast (demo mode only)
      if (M.demo && p.get("demo") !== "0") {
        var t = new Date();
        Object.assign(o, { name: "Omar Al Mansoori", nameAr: "عمر المنصوري", email: "omar@example.com", mobile: "50 100 4000", dob: "1986-04-12", gender: "M", nationality: "102", emirate: "02",
          emiratesId: "784-1986-1234567-1", eidExpiry: isoDate(U.addDays(t, 600)), licenseNo: "1234567", licenseFrom: "2010-06-01", licenseTo: isoDate(U.addDays(t, 900)), ncdYears: "3",
          trafficTranType: "103", chassis: "JTMHV05J604123456", tcfNumber: "10234567", plateSource: "3000", plateCode: "3", regNumber: "45120", regDt: isoDate(U.addDays(t, -340)) });
      }
    }
    // Quick quote (home hero / motor page) always wins over a saved draft or the demo data.
    var fromQuick = false;
    ["make", "model", "year", "plateSource"].forEach(function (k) { if (p.get(k)) { o[k] = p.get(k); fromQuick = true; } });
    if (fromQuick && p.get("make") && !p.get("model")) o.model = "";
    applyValues(o);
  }

  /* ------------------------------------------------------------ rendering */
  function go(n) {
    step = n;
    form.querySelectorAll(".d-step-pane").forEach(function (p) { p.classList.toggle("is-active", Number(p.dataset.step) === n); });
    document.querySelectorAll("[data-step-ind]").forEach(function (li) { var i = Number(li.dataset.stepInd); li.className = i === n ? "is-active" : i < n ? "is-done" : ""; });
    document.getElementById("backBtn").style.visibility = n > 1 ? "visible" : "hidden";
    document.getElementById("nextBtn").innerHTML = n === MAX ? 'Get my quotes <i class="far fa-bolt"></i>' : 'Continue <i class="far fa-arrow-right"></i>';
    if (n === 4) renderUploads();
    if (n === 5) renderReview();
    toggles();
    window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 170, behavior: "smooth" });
  }
  function toggles() {
    document.getElementById("repairWrap").style.display = val("coverType") === "third_party" ? "none" : "";
    document.getElementById("bankWrap").style.display = financed() ? "" : "none";
  }

  function renderReview() {
    var d = data();
    var ad = Object.keys(d.cover.addons).filter(function (k) { return d.cover.addons[k]; }).map(function (k) { return { roadside: "Roadside", rentACar: "Rent-a-car", pa: "Personal accident", gcc: "GCC cover" }[k]; });
    function kv(k, v) { return '<div class="d-kv"><span>' + k + "</span><b>" + U.esc(v || "—") + "</b></div>"; }
    document.getElementById("reviewBox").innerHTML =
      '<div class="d-card"><h5>You <button type="button" data-goto="1">Edit</button></h5>' + kv("Name", d.customer.name) + kv("Mobile", d.customer.mobile) + kv("Date of birth", U.fmtDate(d.customer.dob)) + kv("Nationality", d.customer.nationality) + kv("Emirates ID", d.customer.emiratesId) + '<h5 class="mt-15">Licence <button type="button" data-goto="2">Edit</button></h5>' + kv("Licence no.", d.customer.licenseNo) + kv("Held for", d.customer.experience + " years") + kv("No-claims", label(C.ncdYears, d.customer.ncdYears)) + "</div>" +
      '<div class="d-card"><h5>Vehicle <button type="button" data-goto="3">Edit</button></h5>' + kv("Vehicle", d.vehicle.year + " " + d.vehicle.make + " " + d.vehicle.model) + kv("Chassis", d.vehicle.chassis) + kv("Plate", d.vehicle.regEmirate + " " + label(C.plateCode, d.vehicle.plateCode) + " " + d.vehicle.plate) + kv("Traffic file", d.vehicle.tcfNumber) + kv("Transaction", label(C.trafficTranType, d.vehicle.trafficTranType)) + kv("Financed", d.vehicle.financed ? d.vehicle.bank : "No") + "</div>" +
      '<div class="d-card"><h5>Cover <button type="button" data-goto="4">Edit</button></h5>' + kv("Type", d.cover.type === "comprehensive" ? "Comprehensive" : "Third party") + (d.cover.type === "comprehensive" ? kv("Repair", { any: "Any", agency: "Agency", garage: "Garage" }[d.cover.repair]) : "") + kv("Add-ons", ad.join(", ") || "None") + kv("Start date", U.fmtDate(d.cover.policyStart)) + kv("Documents", d.documents.length + " uploaded") + "</div>";
    document.getElementById("dinPreview").textContent = "POST /api/v1/Insurance/GenerateQuote\nAuthorization: Bearer <token>\nX-REQUEST-ID: <uuid>\n\n" + JSON.stringify(dinPayload(d), null, 2);
  }

  function renderSummary() {
    var d = data(), rows = [];
    if (d.customer.name) rows.push(["Policyholder", d.customer.name]);
    if (d.vehicle.make && d.vehicle.model) rows.push(["Vehicle", [d.vehicle.year || "", d.vehicle.make, d.vehicle.model].join(" ").trim()]);
    if (d.vehicle.value) rows.push(["Est. insured value", U.money0(d.vehicle.value)]);
    if (d.vehicle.chassis) rows.push(["Chassis", d.vehicle.chassis]);
    rows.push(["Cover", d.cover.type === "comprehensive" ? "Comprehensive" : "Third party"]);
    if (d.cover.policyStart) rows.push(["Starts", U.fmtDate(d.cover.policyStart)]);
    rows.push(["Documents", d.documents.length + " / " + docTypes().length]);
    document.getElementById("liveSummary").innerHTML = rows.map(function (r) { return '<div class="d-kv"><span>' + r[0] + "</span><b>" + U.esc(r[1]) + "</b></div>"; }).join("");
  }

  /* Insurer list is rendered by the server (enabled insurers for motor). */
  function renderProviders() {}

  function logo(ins, size) {
    return '<span class="ins-logo ' + (size ? "ins-logo--" + size : "") + '" title="' + U.esc(ins.name) + '"><img src="' + U.esc(ins.logo) + '" alt="' + U.esc(ins.name) + '" data-pid="' + U.esc(ins.code) + '" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span>';
  }

  /* ---------------------------------------------------------------- submit */
  function payload() {
    var fd = new FormData(form);   // every named field, including _token
    if (!financed()) fd.delete("bank");
    docTypes().forEach(function (dt) {
      var d = docs[dt.id];
      if (!d) return;
      if (d.file) fd.append("documents[" + dt.id + "]", d.file, d.fileName);
      else if (d.sample) fd.append("samples[]", dt.id);
    });
    return fd;
  }

  /* 422 from MotorQuoteRequest: same presentation as the browser-side checks. */
  function showServerErrors(res) {
    var target = res.step || 1;
    go(target);
    (res.eligibility || []).forEach(function (e) {
      var box = form.querySelector('[data-elig="' + e.step + '"]');
      if (!box || e.step !== target) return;
      var r = (res.eligibility || []).filter(function (x) { return x.step === e.step; });
      box.innerHTML = '<div class="d-elig__box"><i class="fal fa-triangle-exclamation"></i><div><b>We can’t complete this online</b><ul>' + r.map(function (x) { return "<li>" + U.esc(x.message) + ' <span class="code">' + U.esc(x.code) + "</span></li>"; }).join("") + '</ul><span>Our advisors can still help — <a class="d-link" href="' + M.contactUrl + '">request a call back</a> or call <a class="d-link" href="tel:+97165747420">+971 6 574 7420</a>.</span></div></div>';
    });
    var first = null;
    Object.keys(res.errors || {}).forEach(function (k) {
      var m = k.match(/^documents\.(\w+)/);
      if (m) {
        var t = document.querySelector('[data-doc="' + m[1] + '"]');
        if (t) t.classList.add("is-invalid");
        document.getElementById("docErr").style.display = "block";
        return;
      }
      if (/^consent/.test(k)) document.getElementById("consentErr").style.display = "block";
      var el = f(k.split(".")[0]);
      if (!el) return;
      if (el.length && el[0] && el[0].type === "radio") el = el[0];
      el.classList && el.classList.add("is-invalid");
      var group = el.closest && el.closest(".d-input-group");
      if (group) group.classList.add("is-invalid");
      if (!first) first = el;
    });
    first && first.focus && first.focus();
    ui.toast(res.message || "Please check the highlighted fields.", "err");
  }

  function submit() {
    var btn = document.getElementById("nextBtn");
    var ov = document.getElementById("loadingOverlay");
    var list = document.getElementById("loadingList");
    var formState; try { formState = sessionStorage.getItem(DRAFT_KEY); } catch (e) {}
    list.innerHTML = M.insurers.map(function (p) { return '<div class="d-prov-row" id="lp-' + p.code + '">' + logo(p) + '<div class="nm"><b>' + U.esc(p.name) + '</b><small>Secure insurer API</small></div><span class="st pending"><span class="d-mini-spin"></span> Requesting</span></div>'; }).join("");
    document.getElementById("loadingMsg").textContent = "Contacting insurers in parallel…";
    ov.classList.add("open");
    btn.disabled = true;

    fetch(form.action, { method: "POST", body: payload(), headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); })
      .then(function (r) {
        if (r.status === 200 && r.body.redirect) {
          try { if (formState) sessionStorage.setItem(LAST_KEY, formState); sessionStorage.removeItem(DRAFT_KEY); } catch (e) {}
          document.getElementById("loadingMsg").textContent = "Request received. Preparing your comparison…";
          location.href = r.body.redirect;
          return;
        }
        ov.classList.remove("open");
        btn.disabled = false;
        if (r.status === 422) { showServerErrors(r.body); return; }
        if (r.status === 419) { ui.toast("Your session expired. Please refresh the page and try again.", "err"); return; }
        if (r.status === 429) { ui.toast("Too many attempts. Please wait a minute and try again.", "err"); return; }
        ui.toast("We couldn’t send your request. Please try again or call +971 6 574 7420.", "err");
      })
      .catch(function () {
        ov.classList.remove("open");
        btn.disabled = false;
        ui.toast("Connection problem. Please check your internet connection and try again.", "err");
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    form = document.getElementById("quoteForm");
    if (!C) { console.error("din-codes.js not loaded"); return; }
    fillSelects();
    f("make").addEventListener("change", onMake);
    document.getElementById("vinBtn").addEventListener("click", lookupVin);
    loadDraft();
    if (!f("make").value && String(val("chassis")).length === 17) lookupVin();
    renderProviders(); renderSummary(); toggles();

    var up = document.getElementById("uploads");
    up.addEventListener("change", function (e) {
      if (e.target.type !== "file" || !e.target.files[0]) return;
      var file = e.target.files[0];
      if (file.size > 10 * 1024 * 1024) { ui.toast("File is larger than 10 MB.", "err"); return; }
      if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) { ui.toast("Please upload a PDF, JPG or PNG file.", "err"); return; }
      setDoc(e.target.closest(".d-upload"), file.name, file.size, false, file);
      renderSummary();
    });
    up.addEventListener("click", function (e) {
      var b = e.target.closest("[data-sample]"); if (!b) return;
      e.preventDefault();
      setDoc(b.closest(".d-upload"), sampleName(b.getAttribute("data-sample")), 180000 + Math.round(Math.random() * 300000));
      setTimeout(renderSummary, 900);
    });
    var allSamples = document.getElementById("allSamples");   // demo mode only
    allSamples && allSamples.addEventListener("click", function () {
      up.querySelectorAll(".d-upload").forEach(function (t) { if (!t.classList.contains("is-done")) setDoc(t, sampleName(t.getAttribute("data-doc")), 180000 + Math.round(Math.random() * 300000), true); });
      document.getElementById("docErr").style.display = "none";
      renderSummary();
    });

    form.addEventListener("input", function (e) { e.target.classList.remove("is-invalid"); saveDraft(); renderSummary(); });
    form.addEventListener("change", function (e) {
      if (e.target.name === "financed" || e.target.name === "isNew") { if (!financed()) delete docs.bank_lpo; if (step === 4) renderUploads(); }
      saveDraft(); renderSummary(); toggles();
      var pane = e.target.closest && e.target.closest("[data-step]");
      if (pane) checkElig(Number(pane.dataset.step), false);
    });
    form.addEventListener("click", function (e) { var b = e.target.closest("[data-goto]"); if (b) go(Number(b.dataset.goto)); });
    document.getElementById("backBtn").addEventListener("click", function () { if (step > 1) go(step - 1); });
    document.getElementById("nextBtn").addEventListener("click", function () {
      if (!validateStep(step)) return;
      if (step < MAX) go(step + 1); else submit();
    });
    form.addEventListener("submit", function (e) { e.preventDefault(); });
  });
})();
