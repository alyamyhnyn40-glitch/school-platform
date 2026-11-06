<div class="print-page" style="max-width:800px;margin:auto;padding:1rem">
 <div class="text-center mb-3"><h1 class="h4 mb-0"><?= e($school['name']) ?></h1><div class="text-muted"><?= e($school['city'] ?? '') ?></div><h2 class="h5 mt-2">بطاقة بيانات الطالب</h2></div>
 <?= \App\Core\View::partial('student_overview', ['s' => $student, 'ov' => $ov, 'enrollments' => $enrollments, 'guardians' => $guardians]) ?>
 <div class="d-flex justify-content-between mt-4 small"><div>تاريخ الطباعة: <?= date('Y-m-d') ?></div><div>توقيع الإدارة: ____________</div></div>
</div>
