<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="fa fa-user-plus"></i> Add New User
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/index.php?page=users/add" method="post">
                    
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Ahmed Ali" required>
                    </div>

                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. a.ali" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" class="form-select">
                            <option value="employee">Employee</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="can_add_item" class="form-check-input" id="permCheck" checked>
                        <label class="form-check-label" for="permCheck">Can Add Assets?</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php flash('user_msg'); ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <i class="fa fa-users"></i> System Users
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name / Username</th>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($data['users'])): ?>
                                <?php foreach($data['users'] as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $user['full_name']; ?></strong><br>
                                        <small class="text-muted">@<?php echo $user['username']; ?></small>
                                    </td>
                                    <td>
                                        <?php if($user['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Employee</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($user['can_add_item']): ?>
                                            <i class="fa fa-check text-success"></i> Add Items
                                        <?php else: ?>
                                            <i class="fa fa-times text-muted"></i> No Access
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($_SESSION['user_id'] != $user['id']): // لا يحذف نفسه ?>
                                            <a href="<?php echo URLROOT; ?>/index.php?page=users/delete&id=<?php echo $user['id']; ?>" 
   onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع.');" 
   class="btn btn-sm btn-outline-danger">
    <i class="fa fa-trash"></i>
</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>