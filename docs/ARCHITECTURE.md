# معمارية منصة «مدرستي»

PHP 8.2+ بنمط MVC يدوي (بلا إطار)، MySQL 8 / MariaDB 10.6+، Bootstrap 5 RTL، وواجهة عربية. منصة متعددة المدارس: كل استعلام مقيّد بـ `school_id` يُقرأ من الجلسة فقط.

## الطبقات
| الطبقة | المكان | المسؤولية |
|---|---|---|
| نقطة الدخول | `public/index.php` → `App\Core\App` | التثبيت/القفل، الترقية التلقائية، الصيانة، الوسطاء، معالجة الأخطاء |
| التوجيه | `app/routes.php` + `Core\Router` | كل مسار يحدد متحكمه ووسطاءه (Auth, Tenant, Permission `a|b`, Module, RateLimit) |
| المتحكمات | `app/Controllers` | تحويل الطلب لاستدعاء خدمة وعرض قالب أو JSON — بلا منطق أعمال |
| الخدمات | `app/Services` | **منطق الأعمال كله**؛ كل خدمة تفحص صلاحيتها بنفسها (`Auth::authorize`) وتسجّل العمليات الحساسة (`AuditService`) |
| المستودعات | `app/Repositories` | وصول مقيّد بالمدرسة للجداول البسيطة (`BaseRepository`) |
| القوالب | `app/Views` | PHP + `e()` للهروب؛ JavaScript في ملفات منفصلة (CSP بلا inline) |
| الترحيلات | `database/migrations` | ملفات SQL مرقّمة تُطبَّق تلقائيًا عند تغيّر `VERSION` |

## الوحدات المستقلة
كل وحدة = خدماتها + متحكماتها + قوالبها + صلاحياتها في `database/seeds/catalog.php`. الوحدات المدفوعة تُفعَّل لكل مدرسة حسب خطة الاشتراك (`ModuleMiddleware`).

| الوحدة | الخدمات الرئيسية | الصلاحيات |
|---|---|---|
| الطلاب | `StudentService`, `TranscriptService`, `ImportService` | `students.*`, `guardians.manage`, `import.run` |
| الأكاديمي | `AcademicService`, `StudyPlanService`, `YearWizardService`, `PromotionService`, `TimetableService` | `academic.*`, `timetable.*` |
| الدرجات والنتائج | `GradingService`, `GradeCalculator` (دوال نقية), `ResultsService`, `ReportCardService`, `AcademicReportService` | `grades.*`, `reportcards.issue` |
| الحضور | `AttendanceService` | `attendance.*` |
| المالية (fees) | `FeeService`, `PaymentService`, `InvoiceService`, `OnlinePaymentService`, `CheckoutService`, `FinanceReportService` | `fees.*` |
| التعليم الإلكتروني (lms) | `LmsService`, `QuizService`, `LiveService` | `lms.manage`, `portal.view` |
| الرسائل (comms) | `CommsService`, `NotificationService`, `NotificationQueueService`, `Sms\*`, `EmailService` | `comms.*` |
| الموسّعة (extended) | القبول، الإنذار المبكر، الإرشاد، الرواتب، المرافق، الشهادات الرقمية، التحليلات | حسب كل وحدة |
| المنصة | `SchoolService`, `PlanService`, `Brand`, `PlatformSettings`, `SecurityCheckService` | `platform.*` |
| الأمان | `Auth`, `AuthService`, `RbacService`, `Scope`, `SecurityLog`, `AuditService`, `Crypto` | `rbac.manage`, `audit.view` |

## الصلاحيات
قائمة صلاحيات دقيقة (Permissions) لا أدوار ثابتة: الدور مجموعة صلاحيات قابلة للتعديل، ويمكن **منح أو حجب صلاحية لمستخدم بعينه** (`user_permissions`) فوق أدواره. الصلاحيات تُعاد قراءتها مع كل طلب، والخدمة تتحقق منها حتى لو تجاوز أحدٌ الواجهة. `Scope` يقيّد المعلم بشعبه.

## البيانات الأكاديمية
السنة ← الفصول ← الصفوف ← الشعب ← تسجيل الطالب (`student_enrollments` لكل سنة).
الخطة الدراسية (`study_plans`) تربط المادة بالسنة والصف والفصل (أو كل الفصول) مع الساعات المعتمدة والاختيارية، وتُطبَّق على `section_subjects`.
الدرجات: تقييمات لكل (فصل، مادة شعبة) ← درجات لكل تسجيل. النتائج تُحسب من الدرجات، وعند إغلاق السنة تُجمَّد في `academic_records` فلا يضيع السجل ولا يتغير بتعديل أنظمة الدرجات لاحقًا.

## الأمان
تشفير الحقول الحساسة (libsodium) بمفتاح `APP_ENC_KEY`، كلمات مرور Argon2/bcrypt، CSRF، CSP، حدود معدّل، حماية SSRF لروابط المزودين (`UrlGuard`)، رفع صور بإعادة ترميز، سجل تدقيق لكل عملية حساسة، سجل أمني للرفض وفشل الدخول، وفحص أمني دوري للمشغّل.

## التوسع المستقبلي
- **المواد الاختيارية والساعات المعتمدة**: موجودة في الخطة الدراسية (`is_elective`, `credit_hours`) واختيار الطلاب (`student_electives`)، والمعدل يمكن أن يكون موزونًا بالساعات.
- **نظام المقررات**: يمكن البناء عليه بجعل التسجيل في المادة لكل طالب (تعميم `student_electives`) بدل الشعبة.
- إضافة وحدة جديدة: ترحيل SQL + خدمة + متحكم + قوالب + صلاحيات في الكتالوج + مسارات + (اختياري) مفتاح وحدة في الخطط.
