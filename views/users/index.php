<?php
global $db;
ob_start();
require_once '../../autoload.php';
$title = "User List";
$user = new User($db);
$users = $user->read();

/* echo '<pre>';
var_dump($users);
exit; */
?>
<script src="/views/users/user-js.js"></script>

<h4>List of Registrations</h4>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th class="text-center">No.</th>
                <th>Name</th>
                <th>Email</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>

        <tbody>
            <?php 
            $count = 1;
            foreach ($users as $row) { ?>
                <tr>
                    <td class="text-center"><?= $count++ ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td class="text-center text-nowrap">
                        <a href="javascript:void(0)"
                            class="btn btn-warning btn-sm editUserBtn"
                            data-bs-toggle="modal"
                            data-bs-target="#UserUpdateModal"
                            data-id="<?= encryptId($row['id']) ?>">
                            Edit
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-sm deleteUserBtn" 
                            data-id="<?= encryptId($row['id']) ?>">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?= csrf_field() ?>
<!-- update -->
<div class="modal fade" tabindex="-1" id="UserUpdateModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form action="/controllers/users/update.php" method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <?= csrf_field() ?>

                    <input type="hidden" name="id" id="user_id"> <!-- db primary key -->

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" id="user_name" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="user_email" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Update</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout.php';