<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<style>
    @media print {
        .no-print, .btn, .card-header, footer, nav { display: none !important; }
        .card { border: none !important; shadow: none !important; }
        table { width: 100% !important; border-collapse: collapse; }
        th, td { border: 1px solid #000 !important; padding: 5px; }
        body { background: #fff; }
    }
</style>

<div class="row mb-3">
    <div class="col-md-6">
        <h1><i class="fa fa-boxes"></i> <?php echo isset($data['title']) ? $data['title'] : 'إدارة الأصول'; ?></h1>
    </div>
    <div class="col-md-6 text-end no-print">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fa fa-print"></i> طباعة القائمة
        </button>
        <a href="<?php echo URLROOT; ?>/index.php?page=assets/add" class="btn btn-primary">
            <i class="fa fa-plus"></i> إضافة جهاز جديد
        </a>
    </div>
</div>

<?php flash('asset_msg'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>التاغ (Tag)</th>
                        <th>النوع</th>
                        <th>الماركة والموديل</th>
                        <th>الموقع</th>
                        <th>تاريخ الإضافة</th>
                        <th class="no-print">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($data['assets'])): ?>
                        <?php foreach($data['assets'] as $asset): ?>
                        <tr>
                            <td><span class="badge bg-dark"><?php echo $asset->asset_tag; ?></span></td>
                            <td><?php echo $asset->type; ?></td>
                            <td><?php echo $asset->brand . ' - ' . $asset->model; ?></td>
                            
                            <td><?php echo isset($asset->location_name) ? $asset->location_name : 'غير محدد'; ?></td>
                            
                            <td><?php echo date('Y-m-d', strtotime($asset->created_at)); ?></td>
                            
                            <td class="no-print">
                                <a href="<?php echo URLROOT; ?>/index.php?page=assets/edit&id=<?php echo $asset->id; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                <a href="<?php echo URLROOT; ?>/index.php?page=assets/delete&id=<?php echo $asset->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟');"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">لا توجد أصول مسجلة.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>