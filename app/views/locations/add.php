<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <a href="<?php echo URLROOT; ?>/locations" class="btn btn-light mb-3"><i class="fa fa-backward"></i> رجوع للقائمة</a>
        
        <div class="card card-body bg-light mt-5">
            <h2>إضافة موقع جديد</h2>
            <p>قم بتعبئة النموذج لإنشاء موقع أو مبنى جديد</p>
            
            <form action="<?php echo URLROOT; ?>/locations/add" method="post">
                
                <div class="form-group mb-3">
                    <label for="name">اسم الموقع: <sup>*</sup></label>
                    <input type="text" name="name" class="form-control form-control-lg <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['name']; ?>">
                    <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                </div>

                <div class="form-group mb-3">
                    <label for="parent_id">يتبع لـ (الموقع الرئيسي):</label>
                    <select name="parent_id" class="form-control form-control-lg">
                        <option value="">-- لا يوجد (موقع رئيسي/مبنى مستقل) --</option>
                        
                        <?php foreach($data['parents'] as $parent) : ?>
                            <?php 
                                $selected = '';
                                if($data['parent_id'] == $parent->id){
                                    $selected = 'selected';
                                }
                            ?>
                            <option value="<?php echo $parent->id; ?>" <?php echo $selected; ?>>
                                <?php echo $parent->name; ?>
                            </option>
                        <?php endforeach; ?>
                        
                    </select>
                    <small class="text-muted">اتركه فارغاً إذا كان هذا مبنى رئيسي، أو اختر مبنى ليكون هذا قسماً بداخله.</small>
                </div>

                <div class="row mt-4">
                    <div class="col">
                        <input type="submit" value="حفظ الموقع" class="btn btn-success btn-block">
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>