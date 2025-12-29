<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="fa fa-edit"></i> تعديل بيانات الموقع
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=locations/edit&id=<?php echo $data['location']['id']; ?>" method="post">
                    
                    <div class="mb-3">
                        <label>الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" class="form-control" value="<?php echo $data['location']['name_en']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>الاسم بالعربية</label>
                        <input type="text" name="name_ar" class="form-control" value="<?php echo $data['location']['name_ar']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>النوع</label>
                        <select name="type" class="form-select" required>
                            <option value="college" <?php echo ($data['location']['type']=='college')?'selected':''; ?>>College (كلية)</option>
                            <option value="branch" <?php echo ($data['location']['type']=='branch')?'selected':''; ?>>Branch (فرع)</option>
                            <option value="building" <?php echo ($data['location']['type']=='building')?'selected':''; ?>>Building (مبنى)</option>
                            <option value="lab" <?php echo ($data['location']['type']=='lab')?'selected':''; ?>>Lab (معمل)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>الموقع الرئيسي (الأب)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- بدون (مستوى رئيسي) --</option>
                            <?php foreach($data['all_locations'] as $loc): ?>
                                <?php if($loc['id'] != $data['location']['id']): ?>
                                    <option value="<?php echo $loc['id']; ?>" <?php echo ($data['location']['parent_id'] == $loc['id']) ? 'selected' : ''; ?>>
                                        <?php echo $loc['type'] . ': ' . $loc['name_en']; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                        <a href="<?php echo URLROOT; ?>/index.php?page=locations" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>