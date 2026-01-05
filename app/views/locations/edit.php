<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
  .wrap{ direction: rtl; text-align: right; }
  .form-row-rtl{ flex-direction: row-reverse; }
  .card{ border-radius: 12px; }
  .card-header{ border-top-left-radius:12px; border-top-right-radius:12px; }
  .hint{ color:#6c757d; font-size:.9rem; }
  .pill{ border-radius:999px; padding:.15rem .6rem; font-weight:700; font-size:.85rem; }
  .pill-primary{ background:rgba(13,110,253,.1); border:1px solid rgba(13,110,253,.2); color:#0d6efd; }
  .pill-dark{ background:rgba(33,37,41,.08); border:1px solid rgba(33,37,41,.15); color:#212529; }
  .btn-icon{
    width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;
    padding:0!important;border-radius:10px!important;
  }
  .btn-soft-danger{ background:rgba(220,53,69,.10)!important; border:1px solid rgba(220,53,69,.18)!important; color:#dc3545!important; }
  .btn-soft-danger:hover{ background:rgba(220,53,69,.16)!important; }
  .btn-save{ border-radius:10px!important; font-weight:800; padding:.55rem 1.2rem!important; }
</style>

<?php
  if (function_exists('flash')) {
    flash('location_msg');
    flash('access_denied');
  }

  $typeLabels = [
    'College'     => 'كلية / فرع رئيسي',
    'Building'    => 'مبنى',
    'Department'  => 'قسم',
    'Lab'         => 'معمل',
    'Office'      => 'مكتب',
    'Other'       => 'أخرى',
  ];

  $rolePerms = $data['rolePerms'] ?? [];
  $getRole = function($role, $key) use ($rolePerms) {
    return !empty($rolePerms[$role][$key]) ? 1 : 0;
  };

  $userPerms = $data['userPerms'] ?? [];
  $users = $data['users'] ?? [];
  $children = $data['children'] ?? [];
  $audit = $data['audit'] ?? [];
  // ✅ قطع الغيار لهذا الموقع (قادمة من LocationsController)
  $spareSummary = $data['spareSummary'] ?? null;
  $spareStocks  = $data['spareStocks']  ?? [];

  $sp_items = (int)($spareSummary->items_count ?? 0);
  $sp_total = (int)($spareSummary->total_qty   ?? 0);
  $sp_low   = (int)($spareSummary->low_count   ?? 0);
  $sp_zero  = (int)($spareSummary->zero_count  ?? 0);

  $canManage = function_exists('canManageLocation') ? canManageLocation($data['id'], 'manage') : true;
?>

<div class="container-fluid wrap py-4">

  <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      أنت تعدّل: <strong><?= htmlspecialchars($data['name_ar'] ?? '') ?></strong>
      <span class="pill pill-primary ms-2"><?= htmlspecialchars($typeLabels[$data['type']] ?? ($data['type'] ?? '')) ?></span>
    </div>
    <a class="btn btn-outline-secondary" href="index.php?page=locations/index">
      <i class="bi bi-arrow-right"></i> رجوع
    </a>
  </div>

  <div class="row g-3">

    <!-- Left: Permissions -->
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
          <i class="bi bi-shield-lock"></i> صلاحيات إدارة هذا الموقع
        </div>

        <div class="card-body">
          <?php if (!$canManage): ?>
            <div class="alert alert-warning">
              ليس لديك صلاحية لإدارة صلاحيات هذا الموقع.
            </div>
          <?php else: ?>
            <div class="hint mb-3">
              هنا تحدد من يقدر يدير هذا الموقع (إضافة/تعديل/حذف/إضافة مواقع تابعة).
            </div>

            <form method="post" action="index.php?page=locations/edit&id=<?= (int)$data['id'] ?>">
              <input type="hidden" name="save_permissions" value="1">

              <div class="mb-3">
                <div class="fw-bold mb-2">صلاحيات حسب الدور (Role)</div>

                <?php
                  // أدوارك حسب جدول users
                  $rolesUI = [
                    'manager' => 'Manager',
                    'user'    => 'User',
                  ];
                  foreach ($rolesUI as $role => $label):
                ?>
                  <div class="border rounded-3 p-2 mb-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="fw-bold"><?= htmlspecialchars($label) ?></div>
                      <span class="pill pill-dark"><?= htmlspecialchars($role) ?></span>
                    </div>

                    <div class="row g-2 mt-1">
                      <div class="col-6">
                        <label class="form-check">
                          <input class="form-check-input" type="checkbox" name="role_<?= $role ?>_manage" <?= $getRole($role,'can_manage') ? 'checked' : '' ?>>
                          <span class="form-check-label">تحكم كامل</span>
                        </label>
                      </div>
                      <div class="col-6">
                        <label class="form-check">
                          <input class="form-check-input" type="checkbox" name="role_<?= $role ?>_add" <?= $getRole($role,'can_add_children') ? 'checked' : '' ?>>
                          <span class="form-check-label">إضافة مواقع تابعة</span>
                        </label>
                      </div>
                      <div class="col-6">
                        <label class="form-check">
                          <input class="form-check-input" type="checkbox" name="role_<?= $role ?>_edit" <?= $getRole($role,'can_edit') ? 'checked' : '' ?>>
                          <span class="form-check-label">تعديل</span>
                        </label>
                      </div>
                      <div class="col-6">
                        <label class="form-check">
                          <input class="form-check-input" type="checkbox" name="role_<?= $role ?>_delete" <?= $getRole($role,'can_delete') ? 'checked' : '' ?>>
                          <span class="form-check-label">حذف</span>
                        </label>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <hr>

              <div class="mb-2 fw-bold">إضافة/تحديث صلاحية لمستخدم معيّن</div>
              <div class="row g-2 align-items-end">
                <div class="col-12">
                  <label class="form-label">اختر المستخدم (اختياري)</label>
                  <select class="form-select" name="target_user_id">
                    <option value="">— اختر المستخدم —</option>
                    <?php foreach ($users as $u): ?>
                      <?php
                        $label = $u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id);
                        $roleTxt = $u->role ?? '';
                      ?>
                      <option value="<?= (int)$u->id ?>">
                        <?= htmlspecialchars($label) ?> (<?= htmlspecialchars($roleTxt) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-6">
                  <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="user_manage">
                    <span class="form-check-label">تحكم كامل</span>
                  </label>
                </div>
                <div class="col-6">
                  <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="user_add">
                    <span class="form-check-label">إضافة مواقع تابعة</span>
                  </label>
                </div>
                <div class="col-6">
                  <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="user_edit">
                    <span class="form-check-label">تعديل</span>
                  </label>
                </div>
                <div class="col-6">
                  <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="user_delete">
                    <span class="form-check-label">حذف</span>
                  </label>
                </div>
              </div>

              <div class="mt-3">
                <button class="btn btn-primary btn-save" type="submit">
                  <i class="bi bi-save"></i> حفظ إعدادات الصلاحيات
                </button>
              </div>

              <hr class="my-3">

              <div class="fw-bold mb-2">الصلاحيات المضافة يدويًا للمستخدمين</div>

              <?php if (empty($userPerms)): ?>
                <div class="text-muted">لا توجد صلاحيات مخصصة لمستخدمين حتى الآن.</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead>
                      <tr>
                        <th>المستخدم</th>
                        <th>Manage</th>
                        <th>Add</th>
                        <th>Edit</th>
                        <th>Delete</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($userPerms as $p): ?>
                        <?php
                          $name = $p->name ?? $p->username ?? $p->email ?? ('User#'.$p->user_id);
                          $sub  = $p->email ?? $p->username ?? '';
                        ?>
                        <tr>
                          <td>
                            <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                            <?php if ($sub): ?><div class="text-muted small"><?= htmlspecialchars($sub) ?></div><?php endif; ?>
                          </td>
                          <td><?= (int)$p->can_manage ?></td>
                          <td><?= (int)$p->can_add_children ?></td>
                          <td><?= (int)$p->can_edit ?></td>
                          <td><?= (int)$p->can_delete ?></td>
                          <td>
                            <button class="btn btn-soft-danger btn-icon"
                                    type="submit"
                                    name="remove_user_id"
                                    value="<?= (int)$p->user_id ?>"
                                    title="حذف صلاحية المستخدم">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right: Location Data -->
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <i class="bi bi-info-circle"></i> بيانات الموقع
        </div>
        <div class="card-body">
          <form method="post" action="index.php?page=locations/edit&id=<?= (int)$data['id'] ?>">
            <input type="hidden" name="save_location" value="1">

            <div class="row g-3 align-items-end form-row-rtl">
              <div class="col-12 col-lg-6">
                <label class="form-label">الاسم (عربي) <span class="text-danger">*</span></label>
                <input class="form-control" name="name_ar" value="<?= htmlspecialchars($data['name_ar'] ?? '') ?>" required>
              </div>
              <div class="col-12 col-lg-6">
                <label class="form-label">الاسم (إنجليزي) (اختياري)</label>
                <input class="form-control" name="name_en" value="<?= htmlspecialchars($data['name_en'] ?? '') ?>" placeholder="Ex: IT Building A">
              </div>

              <div class="col-12 col-lg-6">
                <label class="form-label">نوع المكان</label>
                <select class="form-select" name="type">
                  <?php foreach ($typeLabels as $val => $label): ?>
                    <option value="<?= htmlspecialchars($val) ?>" <?= (($data['type'] ?? '') === $val) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-lg-6">
                <label class="form-label">يتبع لـ (الموقع الأب)</label>
                <select class="form-select" name="parent_id">
                  <option value="">— اختر الموقع الأب —</option>
                  <?php foreach (($data['locations'] ?? []) as $l): ?>
                    <?php if ((int)$l->id === (int)$data['id']) continue; ?>
                    <option value="<?= (int)$l->id ?>" <?= ((string)($data['parent_id'] ?? '') === (string)$l->id) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($l->name_ar ?? ('موقع#'.$l->id)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="hint mt-1">الكلية هي أعلى مستوى.</div>
              </div>
            </div>

            <div class="mt-3">
              <button class="btn btn-success btn-save" type="submit">
                <i class="bi bi-save"></i> حفظ بيانات الموقع
              </button>
            </div>
          </form>
        </div>
      </div>

            <!-- Spare Parts Stock (for this location) -->
        <div class="card-header bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <i class="bi bi-box-seam"></i> قطع الغيار في هذا الموقع
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap">
    <a class="btn btn-sm btn-primary"
       href="index.php?page=spareparts/add&location_id=<?= (int)$data['id'] ?>">
      <i class="bi bi-plus-lg"></i> إضافة قطعة لهذا الموقع
    </a>

    <div class="hint">
      (قسم عرض فقط الآن — الحركات بنضيفها بالفقرة الجاية)
    </div>
  </div>
</div>


        <div class="card-body">

          <!-- Summary -->
          <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
              <div class="border rounded-3 p-2">
                <div class="text-muted small">إجمالي الأصناف</div>
                <div class="fw-bold" dir="ltr"><?= $sp_items ?></div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="border rounded-3 p-2">
                <div class="text-muted small">إجمالي الكمية</div>
                <div class="fw-bold" dir="ltr"><?= $sp_total ?></div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="border rounded-3 p-2">
                <div class="text-muted small">تحت الحد</div>
                <div class="fw-bold" dir="ltr"><?= $sp_low ?></div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="border rounded-3 p-2">
                <div class="text-muted small">نفد (0)</div>
                <div class="fw-bold" dir="ltr"><?= $sp_zero ?></div>
              </div>
            </div>
          </div>

          <?php if (empty($spareStocks)): ?>
            <div class="alert alert-secondary mb-0">
              لا يوجد مخزون قطع غيار مربوط بهذا الموقع حاليًا.
            </div>
            <div class="hint mt-2">
              أول خطوة بالفقرة القادمة: نعمل صفحة قطع الغيار + صفحة حركة مخزون عشان نبدأ نضيف ونصرف.
            </div>
          <?php else: ?>

            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>اسم القطعة</th>
                    <th>Part No</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>الحد الأدنى</th>
                    <th>الحالة</th>
                  </tr>
                </thead>
                <tbody>
  <?php if (empty($spareStocks)): ?>
    <tr>
      <td colspan="7" class="text-center text-muted py-4">
        لا يوجد مخزون قطع غيار مربوط بهذا الموقع حاليًا.
      </td>
    </tr>
  <?php else: ?>
    <?php $i = 1; foreach ($spareStocks as $row): ?>
      <?php
        $qty = (int)($row->quantity ?? 0);
        $min = (int)($row->min_quantity ?? 0);

        if ($qty <= 0) {
          $statusTxt = 'نفد';
          $badge = 'bg-danger';
        } elseif ($qty <= $min) {
          $statusTxt = 'تحت الحد';
          $badge = 'bg-warning text-dark';
        } else {
          $statusTxt = 'متوفر';
          $badge = 'bg-success';
        }

        $locId = (int)($data['id'] ?? 0);
        $returnTo = "index.php?page=locations/edit&id={$locId}";
      ?>
      <tr>
        <td dir="ltr"><?= $i++ ?></td>

        <td class="fw-bold">
          <?= htmlspecialchars($row->name ?? '') ?>
        </td>

        <td dir="ltr">
          <?= htmlspecialchars($row->part_number ?? '—') ?>
        </td>

        <td class="fw-bold" dir="ltr">
          <?= $qty ?>
        </td>

        <td dir="ltr">
          <?= $min ?>
        </td>

        <td>
          <span class="badge <?= $badge ?>"><?= $statusTxt ?></span>
        </td>

        <td class="d-flex gap-2 flex-wrap align-items-center">

  <!-- ✅ خانة تحديد الكمية -->
  <input
    type="number"
    class="form-control form-control-sm"
    id="qty_box_<?= (int)($row->id ?? 0) ?>"
    value="1"
    min="1"
    style="width:85px"
    dir="ltr"
  >

  <!-- ✅ توريد (delta = +N) -->
  <form method="post" action="index.php?page=spareParts/adjust" class="m-0"
        onsubmit="this.delta.value = document.getElementById('qty_box_<?= (int)($row->id ?? 0) ?>').value;">
    <input type="hidden" name="id" value="<?= (int)($row->id ?? 0) ?>">
    <input type="hidden" name="delta" value="1">
    <input type="hidden" name="location_id" value="<?= (int)$locId ?>">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
    <button class="btn btn-sm btn-success" title="توريد (زيادة الكمية)">
      توريد
    </button>
  </form>

  <!-- ✅ صرف (delta = -N) -->
  <form method="post" action="index.php?page=spareParts/adjust" class="m-0"
        onsubmit="this.delta.value = -1 * document.getElementById('qty_box_<?= (int)($row->id ?? 0) ?>').value;">
    <input type="hidden" name="id" value="<?= (int)($row->id ?? 0) ?>">
    <input type="hidden" name="delta" value="-1">
    <input type="hidden" name="location_id" value="<?= (int)$locId ?>">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
    <button class="btn btn-sm btn-warning" title="صرف (إنقاص الكمية)">
      صرف
    </button>
  </form>

  <!-- ✅ تعديل -->
  <a class="btn btn-sm btn-outline-primary"
     href="index.php?page=spareParts/edit&id=<?= (int)($row->id ?? 0) ?>">
    تعديل
  </a>

</td>

      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</tbody>


              </table>
            </div>

            <div class="hint mt-2">
              بالفقرة الجاية بنضيف أزرار: توريد / صرف / نقل + سجل حركات.
            </div>

          <?php endif; ?>

        </div>
      </div>


      <!-- Children -->
      <div class="card shadow-sm mt-3">
        <div class="card-header bg-light">
          <i class="bi bi-layers"></i> المواقع التابعة لهذا الموقع
        </div>
        <div class="card-body">
          <?php if (empty($children)): ?>
            <div class="text-muted">لا توجد مواقع تابعة لهذا الموقع حاليًا.</div>
          <?php else: ?>
            <div class="list-group">
              <?php foreach ($children as $c): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   href="index.php?page=locations/edit&id=<?= (int)$c->id ?>">
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($c->name_ar ?? '') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($typeLabels[$c->type] ?? $c->type) ?></div>
                  </div>
                  <i class="bi bi-chevron-left"></i>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="hint mt-2">إضافة مواقع تابعة تتم من صفحة المواقع الرئيسية باستخدام زر (+) بجانب الموقع.</div>
        </div>
      </div>

      <!-- Audit -->
      <div class="card shadow-sm mt-3">
        <div class="card-header bg-light">
          <i class="bi bi-clock-history"></i> سجل آخر التعديلات
        </div>
        <div class="card-body">
          <?php if (empty($audit)): ?>
            <div class="text-muted">لا يوجد سجل تعديلات حتى الآن.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>الوقت</th>
                    <th>المستخدم</th>
                    <th>الإجراء</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($audit as $a): ?>
                    <?php
                      $who = $a->name ?? $a->user_name ?? $a->username ?? $a->user_username ?? '—';
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($a->created_at ?? '') ?></td>
                      <td><?= htmlspecialchars($who) ?></td>
                      <td><?= htmlspecialchars($a->action ?? '') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <div class="hint">يعرض آخر 20 عملية.</div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
