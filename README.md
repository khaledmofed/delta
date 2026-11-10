# Delta Insurance — Laravel portal

Backend + insurer integrations for the Delta Insurance Services website.
Phase 1: Motor insurance sold online; other products use lead forms.

## ما الموجود حالياً

| الجزء | الحالة |
|---|---|
| هيكل Laravel 12 + قاعدة البيانات (منتجات، شركات، طلبات تسعير، عروض، طلبات، دفعات، وثائق، مستندات، سجلات) | ✅ |
| ربط دبي للتأمين (DIN / IIRIS V0.0.3): دخول، تسعير، اختيار خطة، رابط الدفع، حالة الوثيقة | ✅ مكتوب ومختبر بردود وهمية |
| ربط الوثبة (AWNIC): قوائم، تسعير، بدء الوثيقة، تثبيتها، المستندات | ✅ مكتوب، يحتاج تجربة بعد تسجيل الـ IP |
| سجل كل طلب مع الشركات (`api_logs`) مع إخفاء كلمات السر والمستندات | ✅ |
| أوامر فحص: `insurers:ping`، `insurers:quote-test`، `awnic:sync-masters` | ✅ |
| سكربتات السيرفر والنشر (`deploy/`) | ✅ |
| صفحات الموقع (تحويل نموذج HTML إلى Blade) | ⏳ المرحلة التالية |

## التشغيل على السيرفر (Ubuntu 24.04)

```bash
# 1) مرة واحدة كـ root — يثبّت nginx, PHP 8.3, MySQL, Redis, Supervisor ويطبع الـ IP
sudo bash deploy/server-setup.sh uat.deltains-brokers.com

# 2) كمستخدم deploy
cd /var/www/delta
git clone <repo-url> .
bash deploy/deploy.sh          # أول مرة ينشئ .env ويتوقف
nano .env                      # DB_PASSWORD + بيانات الشركات (انسخها من .env.uat)
bash deploy/deploy.sh          # ترحيل قاعدة البيانات + البذور + الكاش

# 3) HTTPS بعد ما الدومين يشير للسيرفر
sudo certbot --nginx -d uat.deltains-brokers.com

# 4) فحص الاتصال مع الشركات (بعد تسجيل IP السيرفر عندهم)
php artisan insurers:ping
php artisan insurers:quote-test din database/data/motor-quote.example.json
php artisan awnic:sync-masters --dump
```

## على جهاز المطوّر

```bash
composer install
cp .env.uat .env               # أو .env.example
php artisan key:generate
php artisan migrate --seed
php artisan test               # اختبارات التحويل والربط (بدون اتصال حقيقي)
```

## الأمان

- بيانات الدخول للشركات في `.env` على السيرفر فقط. `.env` و`.env.uat` مستثنيان من Git.
- رقم الهوية ورقم الرخصة ومدخلات طلب التسعير مشفّرة في قاعدة البيانات.
- المستندات على قرص خاص (`storage/app`)، غير متاحة للعامة.
- `api_logs` لا تحفظ كلمات السر ولا مفاتيح API ولا محتوى المستندات.

## البنية

```
app/Insurers/
  Contracts/InsurerAdapter.php   ← العقد الموحّد لكل شركة
  Data/                          ← MotorQuoteInput, Offer, QuoteResult, CheckoutResult, PolicyStatus
  Din/DinMapper.php              ← تحويل الطلبات/الردود (بدون Laravel، قابل للاختبار)
  Din/DinAdapter.php             ← الاتصال (JWT + X-REQUEST-ID)
  Awnic/AwnicMapper.php
  Awnic/AwnicAdapter.php         ← الاتصال (Partner-Id + Api-Key)
  Support/InsurerHttp.php        ← HTTP + api_logs
  InsurerManager.php             ← الشركات المفعّلة لكل منتج
app/Services/QuoteEngine.php     ← يسأل كل الشركات ويحفظ العروض
config/insurers.php              ← إعدادات الشركات (من .env)
```

إضافة شركة جديدة = Mapper + Adapter + سطر في `config/insurers.php`.

## نقاط مفتوحة مع الشركات

- **DIN:** التسعير يشتغل الآن (8006) بس بيرجع تأمين ضد الغير فقط. ChooseScheme بيرجع 8112 (كود العمولة غير معرّف لحسابنا) فالدفع موقوف لحد ما يصلحوه. كمان: صلاحية التوكن، Webhook الدفع، رابط الرجوع بعد الدفع، بطاقة دفع تجريبية.
- **AWNIC:** تسجيل IP السيرفر، شكل رد `masterList`، أكواد `docCategory` للمستندات، مكان `paymentUrl` في رد `initializePolicyGeneration`.
