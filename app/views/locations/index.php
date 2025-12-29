<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php $locModel = new Location(); ?>

<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 text-primary"><i class="fa fa-sitemap"></i> إدارة الهيكل التنظيمي</h1>
            <p class="text-muted">قم ببناء الهيكل: أضف الكليات، ثم المباني التابعة لها، ثم المعامل والمكاتب.</p>
        </div>
    </div>

    <?php flash('location_msg'); ?>

    <div class="card mb-4 shadow-sm border-top-0 border-end-0 border-start-0 border-primary border-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="m-0 text-primary"><i class="fa fa-plus-circle"></i> إضافة موقع جديد</h5>
        </div>
        <div class="card-body bg-light">
            <form action="<?php echo URLROOT; ?>/index.php?page=locations/add" method="post" class="row g-3">
                
                <div class="col-md-3">
                    <label class="form-label fw-bold">الاسم (عربي) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" placeholder="مثال: كلية الحاسب" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الاسم (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" placeholder="Optional">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label fw-bold text-dark">نوع المكان</label>
                    <select name="type" id="typeSelector" class="form-select border-primary" onchange="filterParents()">
                        <option value="College">كلية / فرع رئيسي</option>
                        <option value="Building">مبنى دراسي</option>
                        <option value="Lab">معمل / مكتب</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold text-dark">يتبع لـ (الموقع الأب)</label>
                    <select name="parent_id" id="parentSelector" class="form-select">
                        <option value="">-- اختر --</option>
                        <?php if(!empty($data['all_locations'])): ?>
                            <?php foreach($data['all_locations'] as $loc): ?>
                                <option value="<?php echo $loc->id; ?>" data-type="<?php echo $loc->type; ?>">
                                    <?php echo $loc->name_ar; ?> 
                                    (<?php 
                                        if($loc->type == 'College') echo 'كلية';
                                        elseif($loc->type == 'Building') echo 'مبنى';
                                        else echo 'أخرى';
                                    ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div id="helpMsg" class="form-text text-muted small mt-1"></div>
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" class="btn btn-success px-5"><i class="fa fa-save"></i> حفظ الموقع</button>
                </div>
            </form>
        </div>
    </div>

    <div class="accordion shadow-sm" id="accordionLocations">
        
        <?php if(!empty($data['main_locations'])): ?>
            <?php foreach($data['main_locations'] as $index => $college): ?>
                
                <div class="accordion-item mb-2 border rounded">
                    <h2 class="accordion-header" id="heading<?php echo $college->id; ?>">
                        <button class="accordion-button <?php echo ($index!=0)?'collapsed':''; ?> fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $college->id; ?>">
                            <i class="fa fa-university me-2 text-primary fa-lg"></i> 
                            <?php echo $college->name_ar; ?>
                        </button>
                    </h2>
                    
                    <div id="collapse<?php echo $college->id; ?>" class="accordion-collapse collapse <?php echo ($index==0)?'show':''; ?>" data-bs-parent="#accordionLocations">
                        <div class="accordion-body bg-light pt-0">
                            
                            <div class="d-flex justify-content-end py-2 mb-2 border-bottom">
                                <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $college->id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('تنبيه: حذف الكلية سيحذف جميع المباني والمعامل بداخلها!\nهل أنت متأكد؟')">
                                    <i class="fa fa-trash"></i> حذف الكلية
                                </a>
                            </div>

                            <?php $buildings = $locModel->getSubLocations($college->id); ?>

                            <?php if(!empty($buildings)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($buildings as $building): ?>
                                        
                                        <div class="list-group-item border-0 border-start border-4 border-info mb-2 bg-white shadow-sm rounded">
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <span class="fw-bold text-dark"><i class="fa fa-building text-info me-2"></i> <?php echo $building->name_ar; ?></span>
                                                    <?php if(!empty($building->name_en)): ?>
                                                        <small class="text-muted ms-1">(<?php echo $building->name_en; ?>)</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $building->id; ?>" class="text-danger small text-decoration-none" onclick="return confirm('حذف المبنى سيحذف جميع المعامل بداخله، هل أنت متأكد؟')">
                                                        <i class="fa fa-times"></i> حذف
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="ms-4 ps-3 border-end border-2">
                                                <?php $labs = $locModel->getSubLocations($building->id); ?>
                                                
                                                <?php if(!empty($labs)): ?>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach($labs as $lab): ?>
                                                            <div class="position-relative">
                                                                <span class="badge bg-light text-dark border p-2 d-flex align-items-center">
                                                                    <i class="fa fa-desktop text-success me-2"></i> 
                                                                    <?php echo $lab->name_ar; ?>
                                                                    
                                                                    <a href="<?php echo URLROOT; ?>/index.php?page=locations/delete&id=<?php echo $lab->id; ?>" class="ms-2 text-danger" onclick="return confirm('حذف المعمل؟')" title="حذف">
                                                                        <i class="fa fa-times-circle"></i>
                                                                    </a>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <small class="text-muted fst-italic"><i class="fa fa-info-circle"></i> لا توجد معامل أو مكاتب مضافة هنا.</small>
                                                <?php endif; ?>
                                            </div>

                                        </div>

                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary text-center m-3">
                                    لا توجد مباني مضافة لهذه الكلية. 
                                    <br>
                                    <small>لإضافة مبنى، اذهب للأعلى، اختر النوع "مبنى" واختر "<?php echo $college->name_ar; ?>" كأب له.</small>
                                </div>
                            <?php endif; ?> </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <h4><i class="fa fa-folder-open"></i> قاعدة البيانات فارغة</h4>
                <p>لا توجد أي كليات مضافة حالياً. ابدأ بإضافة الكلية الأولى من النموذج أعلاه.</p>
            </div>
        <?php endif; ?> </div> </div>



        <?php if(isSuperAdmin()): ?>
    <a href="<?php echo URLROOT; ?>/index.php?page=locations/add" class="btn btn-primary">
        <i class="fas fa-plus"></i> إضافة موقع جديد
    </a>
<?php endif; ?>

<td>
    <?php if(isSuperAdmin()): ?>
        <a href="..." class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
        <a href="..." class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
    <?php else: ?>
        <span class="text-muted small">لا توجد صلاحيات</span>
    <?php endif; ?>
</td>

<script>
var originalOptions = [];

document.addEventListener("DOMContentLoaded", function() {
    var parentSelector = document.getElementById('parentSelector');
    
    // 1. حفظ نسخة من جميع الخيارات
    for (var i = 0; i < parentSelector.options.length; i++) {
        var opt = parentSelector.options[i];
        originalOptions.push({
            value: opt.value,
            text: opt.text,
            type: opt.getAttribute('data-type')
        });
    }

    filterParents();
});

function filterParents() {
    var typeSelector = document.getElementById('typeSelector');
    var parentSelector = document.getElementById('parentSelector');
    var helpMsg = document.getElementById('helpMsg');
    
    var selectedType = typeSelector.value;

    parentSelector.innerHTML = "";

    var defaultOpt = document.createElement('option');
    defaultOpt.value = "";
    defaultOpt.text = "-- اختر الموقع الأب --";
    parentSelector.add(defaultOpt);

    if (selectedType === 'College') {
        parentSelector.disabled = true;
        helpMsg.innerHTML = '<span class="text-info"><i class="fa fa-info-circle"></i> الكلية هي أعلى مستوى.</span>';
        return; 
    } else {
        parentSelector.disabled = false;
    }

    var count = 0;
    
    for (var i = 0; i < originalOptions.length; i++) {
        var item = originalOptions[i];
        if (item.value === "") continue;

        var parentType = item.type;
        var shouldAdd = false;

        if (selectedType === 'Building' && parentType === 'College') {
            shouldAdd = true;
            helpMsg.innerText = "اختر الكلية التي يتبع لها هذا المبنى.";
        }
        else if (selectedType === 'Lab' && parentType === 'Building') {
            shouldAdd = true;
            helpMsg.innerText = "اختر المبنى الذي يقع فيه هذا المعمل.";
        }

        if (shouldAdd) {
            var newOpt = document.createElement('option');
            newOpt.value = item.value;
            newOpt.text = item.text;
            newOpt.setAttribute('data-type', item.type);
            parentSelector.add(newOpt);
            count++;
        }
    }

    if (count === 0) {
        var emptyOpt = document.createElement('option');
        emptyOpt.text = "(لا يوجد خيار مناسب)";
        parentSelector.add(emptyOpt);
        parentSelector.disabled = true;
        
        if(selectedType === 'Lab') {
            helpMsg.innerHTML = '<span class="text-danger fw-bold">تحذير: لا توجد مباني مضافة! أضف مبنى أولاً.</span>';
        } else if (selectedType === 'Building') {
            helpMsg.innerHTML = '<span class="text-danger fw-bold">تحذير: لا توجد كليات مضافة! أضف كلية أولاً.</span>';
        }
    }
}
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>