<?php require_once '../app/views/layouts/header.php'; ?>
<div class="col-md-6 offset-md-3">
    <div class="card">
        <div class="card-header bg-dark text-white">Start New Inventory Session</div>
        <div class="card-body">
            <p>Select a Lab to begin stocktaking:</p>
            <form action="?page=inventory" method="post">
                <select name="lab_id" class="form-select mb-3" required>
                    <?php foreach($data['labs'] as $lab): ?>
                        <option value="<?php echo $lab['id']; ?>">
                             <?php echo $lab['parent_name'] . ' - ' . $lab['name_en']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-success w-100">Start Counting</button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../app/views/layouts/footer.php'; ?>