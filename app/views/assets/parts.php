<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <h1><i class="fa fa-tools"></i> قطع الغيار (Spare Parts)</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo URLROOT; ?>/index.php?page=assets/add" class="btn btn-primary">
            <i class="fa fa-plus"></i> إضافة قطعة جديدة
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>الرقم التسلسلي</th>
                    <th>اسم القطعة / الموديل</th>
                    <th>النوع</th>
                    <th>الحالة</th>
                    <th>تاريخ الإضافة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($data['parts'])): ?>
                    <?php foreach($data['parts'] as $part): ?>
                        <tr>
                            <td><?php echo $part['serial_no']; ?></td>
                            <td>
                                <strong><?php echo $part['brand']; ?></strong><br>
                                <small class="text-muted"><?php echo $part['model']; ?></small>
                            </td>
                            <td><span class="badge bg-info text-dark"><?php echo $part['type']; ?></span></td>
                            <td>
                                <?php if($part['status'] == 'Available'): ?>
                                    <span class="badge bg-success">متاح</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo $part['status']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($part['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo URLROOT; ?>/index.php?page=assets/edit&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                <a href="<?php echo URLROOT; ?>/index.php?page=assets/delete&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف؟')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">لا توجد قطع غيار مسجلة.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>