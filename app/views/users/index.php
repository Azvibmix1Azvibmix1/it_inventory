<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-4">

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h1><i class="fa fa-users-cog text-primary"></i> إدارة المستخدمين</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?php echo URLROOT; ?>/index.php?page=users/register" class="btn btn-primary">
                <i class="fa fa-user-plus"></i> إضافة مستخدم جديد
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور (الصلاحية)</th>
                            <th>تاريخ التسجيل</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($data['users']) && !empty($data['users'])): ?>
                            <?php foreach($data['users'] as $user): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <i class="fa fa-user-circle text-secondary me-2"></i>
                                        <?php echo isset($user->username) ? $user->username : $user->name; ?>
                                    </td>
                                    <td><?php echo $user->email; ?></td>
                                    <td>
                                        <?php if($user->role == 'super_admin'): ?>
                                            <span class="badge bg-dark">سوبر أدمن (Super Admin)</span>
                                        <?php elseif($user->role == 'manager' || $user->role == 'admin'): ?>
                                            <span class="badge bg-danger">مدير (Admin/Manager)</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">موظف (User)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($user->created_at) ? date('Y-m-d', strtotime($user->created_at)) : '-'; ?></td>
                                    <td class="d-flex gap-1">
                                        <a href="<?php echo URLROOT; ?>/index.php?page=users/edit&id=<?php echo $user->id; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <?php if($user->id != $_SESSION['user_id']): ?>
                                            <!-- حذف المستخدم: POST فقط (أكثر أمانًا من GET) -->
                                            <form action="<?php echo URLROOT; ?>/index.php?page=users/delete" method="POST" class="d-inline"
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                                <input type="hidden" name="id" value="<?php echo (int)$user->id; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">لا يوجد مستخدمين مسجلين.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
