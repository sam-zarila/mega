<?php defined('BASEPATH') or exit('No direct script access allowed');

$rows          = isset($rows) && is_array($rows) ? $rows : [];
$summary_total = isset($summary_total) ? (float) $summary_total : 0.0;
$summary_by_wh = isset($summary_by_wh) && is_array($summary_by_wh) ? $summary_by_wh : ['blantyre' => 0, 'lilongwe' => 0];

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title"><?php echo html_escape($title); ?></h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading">Total inventory value</div>
                    <div class="panel-body">
                        <p class="lead"><?php echo inv_mgr_format_mwk($summary_total); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading">Warehouse 1 (Blantyre)</div>
                    <div class="panel-body">
                        <p class="lead"><?php echo inv_mgr_format_mwk((float) ($summary_by_wh['blantyre'] ?? 0)); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading">Warehouse 2 (Lilongwe)</div>
                    <div class="panel-body">
                        <p class="lead"><?php echo inv_mgr_format_mwk((float) ($summary_by_wh['lilongwe'] ?? 0)); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th class="text-right">WAC</th>
                                <th class="text-right">Blantyre qty</th>
                                <th class="text-right">Blantyre value</th>
                                <th class="text-right">Lilongwe qty</th>
                                <th class="text-right">Lilongwe value</th>
                                <th class="text-right">Total qty</th>
                                <th class="text-right">Total value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r) { ?>
                                <tr>
                                    <td><?php echo html_escape($r['commodity_code'] ?? ''); ?></td>
                                    <td><?php echo html_escape($r['description'] ?? ''); ?></td>
                                    <td><?php echo html_escape($r['category'] ?? ''); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($r['wac'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_qty((float) ($r['blantyre_qty'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($r['blantyre_value'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_qty((float) ($r['lilongwe_qty'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($r['lilongwe_value'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_qty((float) ($r['total_qty'] ?? 0)); ?></td>
                                    <td class="text-right"><?php echo inv_mgr_format_mwk((float) ($r['total_value'] ?? 0)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
