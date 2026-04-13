<?php defined('BASEPATH') or exit('No direct script access allowed');
$requests     = isset($requests) ? $requests : [];
$show_approver = !empty($show_approver);
?>
<?php if (empty($requests)) { ?>
<p class="text-muted"><?= _l('approval_no_requests'); ?></p>
<?php } else { ?>
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><?= _l('approval_ref'); ?></th>
            <th><?= _l('document_type'); ?></th>
            <th><?= _l('approval_document_ref'); ?></th>
            <th><?= _l('approval_value'); ?></th>
            <th><?= _l('approval_status'); ?></th>
            <th><?= _l('approval_submitted'); ?></th>
            <?php if ($show_approver) { ?>
            <th><?= _l('approval_current_approver'); ?></th>
            <?php } ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $r) { ?>
        <tr>
            <td><?= e($r['request_ref']); ?></td>
            <td><?= e(ucwords(str_replace('_', ' ', $r['document_type']))); ?></td>
            <td><?= e($r['document_ref'] ?: '#' . $r['document_id']); ?></td>
            <td><?= e(app_format_money($r['document_value'], get_base_currency())); ?></td>
            <td><?= e($r['status']); ?></td>
            <td><?= e(_dt($r['submitted_at'])); ?></td>
            <?php if ($show_approver) { ?>
            <td><?= e(get_staff_full_name($r['current_approver_id'])); ?></td>
            <?php } ?>
            <td>
                <a href="<?= admin_url('approvals/view/' . (int) $r['id']); ?>" class="btn btn-default btn-sm"><?= _l('view'); ?></a>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php } ?>
