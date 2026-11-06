<?php
// كتالوج الصلاحيات والأدوار الافتراضية. المفتاح: وحدة => [كود => وصف]
return [
  'permissions' => [
    'platform' => ['platform.schools' => 'إدارة المدارس', 'platform.plans' => 'إدارة خطط الاشتراك', 'platform.support' => 'الدخول للدعم الفني', 'platform.audit' => 'سجل تدقيق المنصة', 'platform.settings' => 'إعدادات المنصة (مزود الرسائل)'],
    'school' => ['school.settings' => 'إعدادات المدرسة', 'rbac.manage' => 'إدارة الأدوار والمستخدمين', 'audit.view' => 'عرض سجل التدقيق', 'backup.manage' => 'النسخ الاحتياطي للمدرسة'],
    'academic' => ['academic.view' => 'عرض الهيكل الأكاديمي', 'academic.manage' => 'إدارة السنوات والصفوف والشعب والمواد', 'academic.promote' => 'الترفيع وإغلاق السنة'],
    'students' => ['students.view' => 'عرض الطلاب', 'students.manage' => 'إضافة وتعديل الطلاب', 'students.lifecycle' => 'تغيير حالة الطالب', 'students.discipline' => 'سجل الانضباط', 'guardians.manage' => 'إدارة أولياء الأمور'],
    'staff' => ['staff.view' => 'عرض الموظفين', 'staff.manage' => 'إدارة الموظفين والمعلمين', 'staff.attendance' => 'حضور الموظفين'],
    'import' => ['import.run' => 'الاستيراد الجماعي'],
    'timetable' => ['timetable.view' => 'عرض الجدول', 'timetable.manage' => 'إدارة الجدول الدراسي'],
    'attendance' => ['attendance.view' => 'عرض الحضور', 'attendance.take' => 'تسجيل الحضور', 'attendance.edit' => 'تعديل الحضور بعد التسجيل'],
    'grades' => ['grades.view' => 'عرض الدرجات', 'grades.edit' => 'إدخال الدرجات', 'grades.approve' => 'اعتماد وإقفال الدرجات', 'grades.config' => 'تهيئة نظام الدرجات', 'reportcards.issue' => 'إصدار الشهادات'],
    'reports' => ['reports.view' => 'التقارير', 'reports.export' => 'تصدير التقارير'],
    'fees' => ['fees.view' => 'عرض الرسوم', 'fees.manage' => 'إدارة هياكل الرسوم', 'fees.collect' => 'تحصيل الدفعات', 'fees.void' => 'إلغاء السندات', 'fees.gateway' => 'إعداد الدفع الإلكتروني'],
    'lms' => ['lms.view' => 'عرض المحتوى التعليمي', 'lms.manage' => 'إدارة المحتوى والاختبارات'],
    'comms' => ['comms.view' => 'الرسائل', 'comms.announce' => 'نشر الإعلانات', 'comms.notify' => 'إرسال إشعارات جماعية'],
    'portal' => ['portal.view' => 'بوابة الطالب وولي الأمر'],
    'extended' => ['admissions.manage' => 'القبول والتسجيل', 'risk.view' => 'الإنذار المبكر', 'counseling.manage' => 'الإرشاد', 'payroll.manage' => 'الرواتب والعقود', 'facilities.manage' => 'المرافق', 'facilities.book' => 'حجز المرافق', 'analytics.view' => 'التحليلات المتقدمة'],
  ],
  // أدوار المدرسة الافتراضية: كود => [الاسم، صلاحيات ('*school' = كل صلاحيات المدرسة)]
  'roles' => [
    'school_admin' => ['مدير المدرسة', ['*school']],
    'vice_principal' => ['وكيل المدرسة', ['analytics.view','facilities.book','academic.view','students.view','students.manage','students.lifecycle','students.discipline','guardians.manage','staff.view','timetable.view','timetable.manage','attendance.view','attendance.edit','grades.view','grades.approve','reportcards.issue','reports.view','reports.export','comms.view','comms.announce','audit.view','import.run','lms.view']],
    'counselor' => ['المرشد الطلابي', ['students.view','students.discipline','attendance.view','grades.view','risk.view','counseling.manage','reports.view','comms.view']],
    'accountant' => ['المحاسب', ['students.view','fees.view','fees.manage','fees.collect','fees.void','reports.view','reports.export','comms.view']],
    'teacher' => ['معلم', ['facilities.book','students.view','timetable.view','attendance.view','attendance.take','grades.view','grades.edit','lms.view','lms.manage','comms.view','comms.announce','academic.view']],
    'student' => ['طالب', ['portal.view','lms.view','comms.view']],
    'guardian' => ['ولي أمر', ['portal.view','comms.view']],
  ],
];
