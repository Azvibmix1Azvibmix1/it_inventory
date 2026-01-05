<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<?php
  // Dashboard counts
  $parts = $data['parts'] ?? [];
  $totalCount = is_array($parts) ? count($parts) : 0;
  $outCount = 0;
  $lowCount = 0;

  if (is_array($parts)) {
    foreach ($parts as $p) {
      $q = (int)($p->quantity ?? 0);
      $min = (int)($p->min_quantity ?? 0);
      if ($q <= 0) {
        $outCount++;
      } elseif ($q <= $min) {
        $lowCount++;
      }
    }
  }
?>

<style>
  /* ✅ KPI cards: unified navy color */
  .bg-navy { background-color: #0F2A43 !important; }
  .text-navy { color: #0F2A43 !important; }

  .kpi-card {
    border: 0;
    border-radius: 14px;
  }
  .kpi-card .card-text { opacity: .9; }
</style>

<div class="container mt-4">

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h1><i class="fa fa-microchip text-navy"></i> إدارة قطع الغيار</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/add" class="btn btn-primary">
                <i class="fa fa-plus"></i> إضافة قطعة جديدة
            </a>
        </div>
    </div>

    <div class="row mb-4 text-center">
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr"><?php echo $outCount; ?></h1>
                    <p class="card-text">قطع نفذت كميتها</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr"><?php echo $lowCount; ?></h1>
                    <p class="card-text">قطع منخفضة العدد</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-navy mb-3 shadow-sm kpi-card">
                <div class="card-body">
                    <h1 class="display-4 fw-bold" dir="ltr">
                        <?php echo $totalCount; ?>
                    </h1>
                    <p class="card-text">إجمالي القطع المسجلة</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-secondary">سجل المخزون</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>اسم القطعة</th>
                            <th>رقم القطعة (PN)</th>
                            <th>الكمية</th>
                            <th>الموقع</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($data['parts'])): ?>
                            <?php foreach($data['parts'] as $part): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $part->name; ?></td>
                                <td class="text-muted"><?php echo $part->part_number ?? '-'; ?></td>

                                <td>
                                    <?php if($part->quantity == 0): ?>
                                        <span class="text-danger fw-bold">0</span>
                                    <?php elseif($part->quantity <= $part->min_quantity): ?>
                                        <span class="text-warning fw-bold"><?php echo $part->quantity; ?></span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><?php echo $part->quantity; ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                  <?php
                                    $locName = $part->location_name_ar ?? '';
                                    if (empty($locName) && !empty($part->location_name_en)) $locName = $part->location_name_en;
                                    echo !empty($locName) ? htmlspecialchars($locName) : 'غير محدد';
                                  ?>
                                 </td>


                                <td>
                                    <?php if($part->quantity > $part->min_quantity): ?>
                                        <span class="badge bg-success rounded-pill">متوفر</span>
                                    <?php elseif($part->quantity > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill">منخفض</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill">نافذ</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/edit&id=<?php echo $part->id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="<?php echo URLROOT; ?>/index.php?page=spareParts/delete&id=<?php echo $part->id; ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('حذف هذه القطعة؟');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد قطع غيار مسجلة حتى الآن.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
