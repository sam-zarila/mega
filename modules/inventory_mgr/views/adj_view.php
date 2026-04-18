<?php defined('BASEPATH') or exit('No direct script access allowed');

$adj = isset($adj) ? $adj : null;
if (!$adj) {
    show_404();
}
$lines = isset($adj->lines) ? $adj->lines : [];
$canApprove = !empty($can_approve);
$canPost    = !empty($can_post);
$st = (string) ($adj->status ?? '');

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
                <a href="<?php echo admin_url('inventory_mgr/adjustments'); ?>" class="btn btn-default">Back</a>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Header</div>
            <div class="panel-body">
                <p><strong>Status:</strong> <?php echo html_escape($st); ?></p>
                <p><strong>Type:</strong> <?php echo html_escape($adj->adj_type ?? ''); ?></p>
                <p><strong>Warehouse ID:</strong> <?php echo (int) ($adj->warehouse_id ?? 0); ?></p>
                <p><strong>Total value:</strong> <?php echo inv_mgr_format_mwk((float) ($adj->total_value ?? 0)); ?></p>
                <?php if (!empty($adj->gl_journal_id)) { ?>
                    <p><strong>GL journal ID:</strong> <?php echo (int) $adj->gl_journal_id; ?></p>
                <?php } ?>
                <p><strong>Reason:</strong><br><?php echo nl2br(html_escape($adj->reason ?? '')); ?></p>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">Lines</div>
            <div class="panel-body">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Item</th><th class="text-right">Qty current</th><th class="text-right">Qty adjust</th><th class="text-right">WAC</th><th class="text-right">Line value</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $ln) { ?>
                            <tr>
                                <td><?php echo html_escape($ln->item_code ?? ''); ?> — <?php echo html_escape($ln->item_name ?? ''); ?></td>
                                <td class="text-right"><?php echo isset($ln->qty_current) ? inv_mgr_format_qty((float) $ln->qty_current) : '—'; ?></td>
                                <td class="text-right"><?php echo inv_mgr_format_qty((float) ($ln->qty_adjust ?? 0)); ?></td>
                                <td class="text-right"><?php echo number_format((float) ($ln->wac_at_adj ?? 0), 4); ?></td>
                                <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($ln->line_value ?? 0)); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($canApprove && in_array($st, ['pending_approval', 'draft'], true)) { ?>
            <?php echo form_open(admin_url('inventory_mgr/approve_adjustment_action/' . (int) $adj->id)); ?>
            <button type="submit" class="btn btn-success">Approve</button>
            <?php echo form_close(); ?>
        <?php } ?>

        <?php if ($canPost && in_array($st, ['approved', 'draft'], true)) { ?>
            <?php echo form_open(admin_url('inventory_mgr/post_adjustment_action/' . (int) $adj->id)); ?>
            <button type="submit" class="btn btn-primary mtop10">Post to stock</button>
            <?php echo form_close(); ?>
        <?php } ?>
    </div>
</div>
<?php init_tail(); ?>
