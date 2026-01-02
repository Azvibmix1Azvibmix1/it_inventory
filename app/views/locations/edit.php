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
?>

<div class="container-fluid wrap py-4">

  <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      أنت تعدّل: <strong><?= htmlspecialchars($data['name_ar'] ?? '') ?></strong>
      <span class="pill pill-primary ms-2"><?= htmlspecialchars($typeLabels[$data['type']] ?? $data['type']) ?></span>
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
          <div class="hint mb-3">
            هنا تحدد من يقدر يدير هذا الموقع (إضافة/تعديل/حذف/إضافة مواقع تابعة).
          </div>

          <form method="post" action="index.php?page=locations/edit&id=<?= (int)$data['id'] ?>">
            <input type="hidden" name="save_permissions" value="1">

            <div class="mb-3">
              <div class="fw-bold mb-2">صلاحيات حسب الدور (Role)</div>

              <?php
                $rolesUI = [
                  'admin' => 'Admin',
                  'manager' => 'Manager',
                  'user' => 'User/Staff',
                ];
                foreach ($rolesUI as $role => $label):
              ?>
                <div class="border rounded-3 p-2 mb-2">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="fw-bold"><?= $label ?></div>
                    <span class="pill pill-dark"><?= $role ?></span>
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
                    <option value="<?= (int)$u->id ?>">
                      <?= htmlspecialchars($u->name ?? $u->username ?? $u->email ?? ('User#'.$u->id)) ?>
                      (<?= htmlspecialchars($u->role ?? '') ?>)
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
                      <tr>
                        <td>
                          <div class="fw-bold"><?= htmlspecialchars($p->name ?? '') ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($p->email ?? $p->username ?? '') ?></div>
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
                    <tr>
                      <td><?= htmlspecialchars($a->created_at ?? '') ?></td>
                      <td><?= htmlspecialchars($a->name ?? '—') ?></td>
                      <td><?= htmlspecialchars($a->action ?? '') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <div class="hint">حاليًا يعرض آخر 20 عملية (شكل بسيط).</div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
