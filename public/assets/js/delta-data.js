/* ==========================================================================
   Delta Insurance — Demo catalogue & reference data
   --------------------------------------------------------------------------
   This file mirrors the entities planned for the Laravel backend:
   insurance_products, insurance_providers, provider_products, vehicle
   reference data and the quote engine rules. In production these come
   from the API; here they are static so the prototype runs offline.
   ========================================================================== */
(function (w) {
  "use strict";

  var COMPANY = {
    name: "Delta Insurance Services L.L.C",
    short: "Delta Insurance",
    since: 1990,
    city: "Sharjah",
    country: "United Arab Emirates",
    phone: "+971 6 574 7420",
    phoneHref: "+97165747420",
    email: "dis@deltains-brokers.com",
    website: "deltains-brokers.com",
  };

  /* ---------------------------------------------------------------------
     Insurance products (insurance_products)
     status:        active | inactive | coming_soon
     capabilities:  what the digital platform can do for this product today
     ------------------------------------------------------------------- */
  var PRODUCTS = [
    {
      id: "motor",
      name: "Motor Insurance",
      icon: "fa-car-side",
      page: "motor-insurance.html",
      image: "images/cut/car.webp",
      tagline: "Protect your vehicle with flexible insurance options.",
      status: "active",
      capabilities: { onlineQuote: true, comparison: true, onlinePurchase: true, policyIssuance: true, erpIntegration: true },
      providers: ["sukoon", "orient", "adnic"],
    },
    {
      id: "medical",
      name: "Medical Insurance",
      icon: "fa-heart-pulse",
      page: "medical-insurance.html",
      image: "images/cut/family.webp",
      tagline: "Insurance solutions designed around your healthcare needs.",
      status: "active",
      capabilities: { onlineQuote: false, comparison: false, onlinePurchase: false, policyIssuance: false, erpIntegration: false },
      providers: [],
    },
    {
      id: "home",
      name: "Home Insurance",
      icon: "fa-house-heart",
      page: "home-insurance.html",
      image: "images/cut/villa.webp",
      tagline: "Protect your home and valuable belongings.",
      status: "active",
      capabilities: { onlineQuote: false, comparison: false, onlinePurchase: false, policyIssuance: false, erpIntegration: false },
      providers: [],
    },
    {
      id: "travel",
      name: "Travel Insurance",
      icon: "fa-plane-departure",
      page: "travel-insurance.html",
      image: "images/cut/travel.webp",
      tagline: "Protection and support for your journeys.",
      status: "active",
      capabilities: { onlineQuote: false, comparison: false, onlinePurchase: false, policyIssuance: false, erpIntegration: false },
      providers: [],
    },
    {
      id: "boat",
      name: "Boat Insurance",
      icon: "fa-sailboat",
      page: "boat-insurance.html",
      image: "",
      tagline: "Insurance solutions for your marine assets.",
      status: "active",
      capabilities: { onlineQuote: false, comparison: false, onlinePurchase: false, policyIssuance: false, erpIntegration: false },
      providers: [],
    },
    {
      id: "pet",
      name: "Pet Insurance",
      icon: "fa-paw",
      page: "pet-insurance.html",
      image: "",
      tagline: "Coverage options for your pets.",
      status: "active",
      capabilities: { onlineQuote: false, comparison: false, onlinePurchase: false, policyIssuance: false, erpIntegration: false },
      providers: [],
    },
  ];

  var CAPABILITY_LABELS = {
    onlineQuote: "Online Quote",
    comparison: "Comparison",
    onlinePurchase: "Online Purchase",
    policyIssuance: "Policy Issuance",
    erpIntegration: "ERP Integration",
  };

  /* ---------------------------------------------------------------------
     Insurance providers (insurance_providers + provider credentials meta)
     Real UAE insurers used as DEMO data only — no live integration exists.
     ------------------------------------------------------------------- */
  var PROVIDERS = [
    {
      id: "din",
      logo: "images/insurers/din.png",
      name: "Dubai Insurance",
      fullName: "Dubai Insurance Company (DIN)",
      short: "Dubai Insurance",
      mono: "DI",
      tint: "#0B5E8E",
      hq: "Dubai",
      adapter: "DubaiInsuranceAdapter (IIRIS)",
      protocol: "REST / JSON",
      auth: "JWT bearer (username / password)",
      environment: "uat",
      enabled: true,
      products: ["motor"],
      commission: 12.0,
      timeoutMs: 15000,
      retries: 1,
      rateFactor: 0.98,
      avgResponseMs: 1900,
      successRate: 99.0,
      health: "healthy",
      note: "Real API spec V0.0.3 · UAT login verified 09-Nov-2026 · quote test pending DIN test data",
      api: { baseUrl: "https://uatbrokerportal.dubins.ae", auth: "/api/v1/User/Auth", quote: "/api/v1/Insurance/GenerateQuote", choose: "/api/v1/Insurance/ChooseScheme", status: "/api/v1/Insurance/GetPaymentInfo" },
    },
    {
      id: "sukoon",
      logo: "images/insurers/sukoon.png",
      name: "Sukoon Insurance",
      short: "Sukoon",
      mono: "SK",
      tint: "#2E7D6B",
      hq: "Dubai",
      adapter: "SukoonMotorAdapter",
      protocol: "REST / JSON",
      auth: "OAuth2 client credentials",
      environment: "sandbox",
      enabled: true,
      products: ["motor"],
      commission: 12.5,
      timeoutMs: 8000,
      retries: 2,
      rateFactor: 1.0,
      avgResponseMs: 1380,
      successRate: 99.1,
      health: "healthy",
    },
    {
      id: "orient",
      logo: "images/insurers/orient.png",
      name: "Orient Insurance",
      short: "Orient",
      mono: "OI",
      tint: "#8A5A1F",
      hq: "Dubai",
      adapter: "OrientMotorAdapter",
      protocol: "SOAP / XML",
      auth: "WS-Security + IP whitelist",
      environment: "sandbox",
      enabled: true,
      products: ["motor"],
      commission: 12.0,
      timeoutMs: 10000,
      retries: 2,
      rateFactor: 0.94,
      avgResponseMs: 2140,
      successRate: 97.8,
      health: "healthy",
    },
    {
      id: "adnic",
      logo: "images/insurers/adnic.png",
      name: "ADNIC",
      fullName: "Abu Dhabi National Insurance Company",
      short: "ADNIC",
      mono: "AD",
      tint: "#6B3FA0",
      hq: "Abu Dhabi",
      adapter: "AdnicMotorAdapter",
      protocol: "REST / JSON",
      auth: "API key + HMAC signature",
      environment: "sandbox",
      enabled: true,
      products: ["motor"],
      commission: 11.5,
      timeoutMs: 8000,
      retries: 1,
      rateFactor: 1.07,
      avgResponseMs: 1720,
      successRate: 98.4,
      health: "healthy",
    },
    {
      id: "gig",
      logo: "images/insurers/gig.png",
      name: "GIG Gulf",
      short: "GIG Gulf",
      mono: "GG",
      tint: "#B03A48",
      hq: "Dubai",
      adapter: "GigGulfAdapter",
      protocol: "REST / JSON",
      auth: "OAuth2",
      environment: "not_configured",
      enabled: false,
      products: [],
      commission: 0,
      timeoutMs: 8000,
      retries: 2,
      rateFactor: 1,
      avgResponseMs: 0,
      successRate: 0,
      health: "not_connected",
      note: "Onboarding — future provider for Medical / Travel",
    },
    {
      id: "albuhaira",
      logo: "images/insurers/albuhaira.png",
      name: "Al Buhaira National Insurance",
      short: "Al Buhaira",
      mono: "AB",
      tint: "#1F6FA8",
      hq: "Sharjah",
      adapter: "AlBuhairaAdapter",
      protocol: "REST / JSON",
      auth: "API key",
      environment: "not_configured",
      enabled: false,
      products: [],
      commission: 0,
      timeoutMs: 8000,
      retries: 2,
      rateFactor: 1,
      avgResponseMs: 0,
      successRate: 0,
      health: "not_connected",
      note: "Planned — adapter to be built once API docs are received",
    },
  ];

  /* Plans each motor provider returns (provider_products) */
  var MOTOR_PLANS = {
    din: [
      { code: "1001", name: "Comprehensive Gold", type: "comprehensive", repair: "agency", deductible: 500, roadside: true, rentACar: false, gcc: false, oman: true, pa: true, windscreen: 3000 },
      { code: "1001-G", name: "Comprehensive Gold (garage)", type: "comprehensive", repair: "garage", deductible: 500, roadside: true, rentACar: false, gcc: false, oman: true, pa: true, windscreen: 3000 },
      { code: "1521", name: "Third Party Liability", type: "third_party", repair: null, deductible: 0, roadside: false, rentACar: false, gcc: false, oman: false, pa: false, windscreen: 0 },
    ],
    sukoon: [
      { code: "SK-COMP-AG", name: "Motor Comprehensive Plus", type: "comprehensive", repair: "agency", deductible: 500, roadside: true, rentACar: true, gcc: false, oman: true, pa: true, windscreen: 3000 },
      { code: "SK-COMP-GR", name: "Motor Comprehensive", type: "comprehensive", repair: "garage", deductible: 350, roadside: true, rentACar: false, gcc: false, oman: true, pa: false, windscreen: 2000 },
      { code: "SK-TPL", name: "Third Party Liability", type: "third_party", repair: null, deductible: 0, roadside: false, rentACar: false, gcc: false, oman: false, pa: false, windscreen: 0 },
    ],
    orient: [
      { code: "OR-GOLD", name: "Gold Comprehensive", type: "comprehensive", repair: "agency", deductible: 750, roadside: true, rentACar: true, gcc: true, oman: true, pa: true, windscreen: 5000 },
      { code: "OR-SILVER", name: "Silver Comprehensive", type: "comprehensive", repair: "garage", deductible: 500, roadside: true, rentACar: false, gcc: false, oman: true, pa: true, windscreen: 2500 },
      { code: "OR-TPL", name: "Third Party Only", type: "third_party", repair: null, deductible: 0, roadside: true, rentACar: false, gcc: false, oman: false, pa: false, windscreen: 0 },
    ],
    adnic: [
      { code: "AD-PREM", name: "Premier Motor", type: "comprehensive", repair: "agency", deductible: 500, roadside: true, rentACar: true, gcc: true, oman: true, pa: true, windscreen: 4000 },
      { code: "AD-STD", name: "Standard Motor", type: "comprehensive", repair: "garage", deductible: 500, roadside: false, rentACar: false, gcc: false, oman: true, pa: false, windscreen: 1500 },
      { code: "AD-TPL", name: "Third Party Motor", type: "third_party", repair: null, deductible: 0, roadside: false, rentACar: false, gcc: false, oman: false, pa: true, windscreen: 0 },
    ],
  };

  var EMIRATES = ["Abu Dhabi", "Dubai", "Sharjah", "Ajman", "Umm Al Quwain", "Ras Al Khaimah", "Fujairah"];

  var NATIONALITIES = ["United Arab Emirates", "Saudi Arabia", "Egypt", "Jordan", "Syria", "Lebanon", "Palestine", "India", "Pakistan", "Philippines", "United Kingdom", "United States", "Other"];

  /* Vehicle reference data: make → model → approx. new value (AED) + body */
  var VEHICLES = {
    Toyota: { "Land Cruiser": [320000, "SUV"], Prado: [210000, "SUV"], Camry: [115000, "Sedan"], Corolla: [85000, "Sedan"], "RAV4": [120000, "SUV"], Yaris: [62000, "Hatchback"], Hilux: [110000, "Pickup"] },
    Nissan: { Patrol: [290000, "SUV"], Altima: [98000, "Sedan"], Sunny: [60000, "Sedan"], "X-Trail": [115000, "SUV"], Kicks: [78000, "SUV"] },
    Lexus: { LX: [480000, "SUV"], ES: [190000, "Sedan"], RX: [260000, "SUV"] },
    Mitsubishi: { Pajero: [135000, "SUV"], "ASX": [85000, "SUV"], Attrage: [48000, "Sedan"] },
    Honda: { Accord: [120000, "Sedan"], Civic: [92000, "Sedan"], "CR-V": [128000, "SUV"] },
    Hyundai: { Tucson: [105000, "SUV"], Elantra: [78000, "Sedan"], Sonata: [98000, "Sedan"], "Santa Fe": [140000, "SUV"] },
    Kia: { Sportage: [102000, "SUV"], Picanto: [48000, "Hatchback"], K5: [95000, "Sedan"], Sorento: [135000, "SUV"] },
    "Mercedes-Benz": { "C-Class": [210000, "Sedan"], "E-Class": [290000, "Sedan"], GLE: [380000, "SUV"] },
    BMW: { "3 Series": [200000, "Sedan"], "5 Series": [275000, "Sedan"], X5: [370000, "SUV"] },
    Ford: { Explorer: [175000, "SUV"], Mustang: [195000, "Coupe"], Ranger: [115000, "Pickup"] },
    Chevrolet: { Tahoe: [260000, "SUV"], Malibu: [95000, "Sedan"] },
    Tesla: { "Model 3": [165000, "Sedan"], "Model Y": [185000, "SUV"] },
  };

  /* Motor documents — codes are Dubai Insurance (IIRIS) document codes, sent with GenerateQuote */
  var DOCUMENT_TYPES = [
    { id: "eid_front", din: "103", label: "Emirates ID — front", required: true },
    { id: "eid_back", din: "105", label: "Emirates ID — back", required: true },
    { id: "licence_front", din: "106", label: "Driving licence — front", required: true },
    { id: "licence_back", din: "121", label: "Driving licence — back", required: true },
    { id: "mulkiya_front", din: "101", label: "Mulkiya — front", required: true, altNew: "Customs / dealer certificate for new vehicles" },
    { id: "mulkiya_back", din: "102", label: "Mulkiya — back", required: true },
    { id: "bank_lpo", din: "107", label: "Bank LPO", required: false, financedOnly: true },
  ];

  /* Order state machine — mirrors the backend enum */
  var ORDER_STATES = [
    { id: "DRAFT", label: "Draft" },
    { id: "QUOTE_REQUESTED", label: "Quote requested" },
    { id: "QUOTES_RECEIVED", label: "Quotes received" },
    { id: "QUOTE_SELECTED", label: "Quote selected" },
    { id: "CUSTOMER_DETAILS_PENDING", label: "Customer details pending" },
    { id: "DOCUMENTS_PENDING", label: "Documents pending" },
    { id: "PAYMENT_PENDING", label: "Payment pending" },
    { id: "PAYMENT_SUCCESS", label: "Payment successful" },
    { id: "POLICY_REQUESTED", label: "Policy requested" },
    { id: "INSURANCE_PROCESSING", label: "Insurer processing" },
    { id: "POLICY_ISSUED", label: "Policy issued" },
  ];
  var ORDER_ALT_STATES = ["PAYMENT_FAILED", "DOCUMENTS_REJECTED", "QUOTE_EXPIRED", "INSURANCE_REJECTED", "POLICY_FAILED", "CANCELLED", "REFUNDED"];

  var ROLES = [
    { id: "super_admin", name: "Super Admin" },
    { id: "operations", name: "Operations Manager" },
    { id: "insurance", name: "Insurance Manager" },
    { id: "finance", name: "Finance" },
    { id: "support", name: "Customer Support" },
    { id: "compliance", name: "Compliance" },
    { id: "tech", name: "Technical Admin" },
    { id: "readonly", name: "Read Only" },
  ];

  var PERMISSIONS = [
    "products.view", "products.update",
    "providers.view", "providers.update", "providers.credentials",
    "quotes.view", "orders.view", "orders.update",
    "policies.view", "policies.update",
    "payments.view", "payments.refund",
    "documents.view", "documents.verify",
    "commissions.view", "commissions.update",
    "erp.view", "erp.sync",
    "leads.view", "leads.update",
    "users.manage", "audit.view", "settings.update",
  ];

  var ROLE_MATRIX = {
    super_admin: PERMISSIONS.slice(),
    operations: ["products.view", "providers.view", "quotes.view", "orders.view", "orders.update", "policies.view", "policies.update", "payments.view", "documents.view", "documents.verify", "erp.view", "leads.view", "leads.update", "audit.view"],
    insurance: ["products.view", "products.update", "providers.view", "providers.update", "quotes.view", "orders.view", "policies.view", "commissions.view", "commissions.update"],
    finance: ["orders.view", "payments.view", "payments.refund", "commissions.view", "erp.view", "erp.sync", "policies.view"],
    support: ["quotes.view", "orders.view", "policies.view", "documents.view", "leads.view", "leads.update"],
    compliance: ["orders.view", "policies.view", "documents.view", "audit.view", "users.manage"],
    tech: ["providers.view", "providers.update", "providers.credentials", "erp.view", "erp.sync", "settings.update", "audit.view"],
    readonly: ["products.view", "providers.view", "quotes.view", "orders.view", "policies.view", "payments.view"],
  };

  w.DELTA = w.DELTA || {};
  w.DELTA.COMPANY = COMPANY;
  w.DELTA.PRODUCTS = PRODUCTS;
  w.DELTA.CAPABILITY_LABELS = CAPABILITY_LABELS;
  w.DELTA.PROVIDERS = PROVIDERS;
  w.DELTA.MOTOR_PLANS = MOTOR_PLANS;
  w.DELTA.EMIRATES = EMIRATES;
  w.DELTA.NATIONALITIES = NATIONALITIES;
  w.DELTA.VEHICLES = VEHICLES;
  w.DELTA.DOCUMENT_TYPES = DOCUMENT_TYPES;
  w.DELTA.ORDER_STATES = ORDER_STATES;
  w.DELTA.ORDER_ALT_STATES = ORDER_ALT_STATES;
  w.DELTA.ROLES = ROLES;
  w.DELTA.PERMISSIONS = PERMISSIONS;
  w.DELTA.ROLE_MATRIX = ROLE_MATRIX;
})(window);
