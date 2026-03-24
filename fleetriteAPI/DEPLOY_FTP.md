# رفع الباك اند (Laravel) على السيرفر عبر FTP

## بيانات الاتصال FTP

| الحقل | القيمة |
|------|--------|
| **Host** | `167.235.1.40` |
| **Username** | `vue_back` |
| **Password** | استخدم كلمة المرور الخاصة بحساب الباك اند (لا تُحفظ في الملفات) |
| **الموقع النهائي** | `https://vue2.esoft-eg.com` |

---

## الطريقة 1: استخدام FileZilla (مُوصى به)

1. **فتح FileZilla** → File → Site Manager → New Site.
2. إدخال:
   - **Host:** `167.235.1.40`
   - **Protocol:** FTP - File Transfer Protocol
   - **Logon Type:** Normal
   - **User:** `vue_back`
   - **Password:** (كلمة مرور الحساب)
3. اضغط **Connect**.

### ماذا ترفع؟

- **المجلد المحلي (يسار):** انتقل إلى  
  `c:\xampp\htdocs\fleetrite-web\fleetriteAPI`
- **السيرفر (يمين):** انتقل إلى مجلد الباك اند على السيرفر (غالباً `public_html` أو `vue2.esoft-eg.com` أو كما حدده الاستضافة).

### المجلدات والملفات المطلوب رفعها:

| ارفع | ملاحظة |
|------|--------|
| `app/` | كامل |
| `bootstrap/` | كامل |
| `config/` | كامل |
| `database/` | كامل (migrations, seeders) |
| `public/` | **كامل** (بما فيه `index.php`, `.htaccess`) |
| `routes/` | كامل |
| `storage/` | المجلد والهيكل فقط (انظر تحت) |
| `vendor/` | كامل إن لم يكن عندك SSH لتشغيل `composer install` على السيرفر |
| `.env` | انسخ من المشروع وعدّل إعدادات السيرفر (DB، APP_DEBUG، إلخ) |

### لا ترفع:

- `node_modules/` (غير مطلوب لتشغيل Laravel على السيرفر)
- `.env.example` فقط للاسترشاد (الاعتماد على `.env` الفعلي على السيرفر)
- `.git/` إذا كان موجوداً (اختياري)

### مجلد Storage على السيرفر

تأكد أن الهيكل التالي موجود داخل `storage/` على السيرفر:

```
storage/
  app/
  framework/
    cache/
    sessions/
    views/
  logs/
```

إن لم تكن موجودة، أنشئها عبر FTP ثم اعطِ الصلاحيات المناسبة (عادة 775 أو حسب استضافة Laravel).

---

## الطريقة 2: سطر الأوامر (لو عندك FTP فقط)

من PowerShell أو CMD من مجلد المشروع:

```bash
# مثال باستخدام curl (رفع ملف واحد - للتجربة)
# الأفضل استخدام FileZilla للرفع الكامل
```

الأسهل للرفع الكامل من ويندوز: **FileZilla** أو **WinSCP**.

---

## بعد الرفع على السيرفر

1. **صلاحيات المجلدات (عبر FTP أو SSH إن وُجد):**
   - `storage/` و `bootstrap/cache/` → 775 أو 755 حسب الاستضافة
2. **ملف `.env` على السيرفر:**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://vue2.esoft-eg.com`
   - إعدادات قاعدة البيانات الصحيحة للسيرفر
3. **إن وُجد SSH:**
   ```bash
   cd /path/to/your/laravel
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   ```

---

## أمان

- **لا ترفع ملف `.env` الحقيقي إلى أي مكان عام أو Git.**
- على السيرفر استخدم `.env` خاص بالسيرفر فقط.
- يُفضّل تغيير كلمة مرور FTP بعد النشر إذا تم مشاركتها سابقاً.
