<?php defined('BASEPATH') or exit('No direct script access allowed');

$year = date('Y');
$jcPrefix = isset($settings['jc_prefix']) ? (string) $settings['jc_prefix'] : 'JC';
$jcNext   = isset($settings['jc_next_number']) ? (string) $settings['jc_next_number'] : '1';
$issPrefix = isset($settings['iss_prefix']) ? (string) $settings['iss_prefix'] : 'ISS';
$issNext   = isset($settings['iss_next_number']) ? (string) $settings['iss_next_number'] : '1';
$jcNum     = max(1, (int) $jcNext);
$issNum    = max(1, (int) $issNext);
$previewJc = $jcPrefix . '-' . $year . '-' . str_pad((string) $jcNum, 5, '0', STR_PAD_LEFT);
$previewIss = $issPrefix . '-' . $year . '-' . str_pad((string) $issNum, 5, '0', STR_PAD_LEFT);

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title">Job Card Settings</h4>
                <?php echo form_open(admin_url('job_cards/settings')); ?>

                <div class="panel_s">
                    <div class="panel-heading"><strong>Reference Format</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>JC Prefix</label>
                                    <input type="text" name="jc_prefix" id="jc_prefix" class="form-control jc-preview-input" value="<?php echo e($jcPrefix); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Next JC Number</label>
                                    <input type="number" name="jc_next_number" id="jc_next_number" class="form-control jc-preview-input" min="1" value="<?php echo e($jcNext); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>ISS Prefix</label>
                                    <input type="text" name="iss_prefix" id="iss_prefix" class="form-control jc-preview-input" value="<?php echo e($issPrefix); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Next Issue Number</label>
                                    <input type="number" name="iss_next_number" id="iss_next_number" class="form-control jc-preview-input" min="1" value="<?php echo e($issNext); ?>">
                                </div>
                            </div>
                        </div>
                        <p class="text-muted mtop10">
                            <strong>Next Job Card:</strong> <span id="jc-preview-ref"><?php echo e($previewJc); ?></span><br>
                            <strong>Next Issue:</strong> <span id="jc-preview-iss"><?php echo e($previewIss); ?></span>
                        </p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><strong>Auto-creation</strong></div>
                    <div class="panel-body">
                        <input type="hidden" name="jc_auto_create_on_approval" value="0">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="jc_auto_create_on_approval" value="1" <?php echo ((string) ($settings['jc_auto_create_on_approval'] ?? '1') === '1') ? 'checked' : ''; ?>>
                                Automatically create job card when proposal is approved
                            </label>
                        </div>
                        <div class="form-group mtop15">
                            <label>Default deadline (days from creation)</label>
                            <input type="number" name="jc_default_deadline_days" class="form-control" style="max-width:120px;" min="1" value="<?php echo e($settings['jc_default_deadline_days'] ?? '7'); ?>">
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><strong>Department Role Mapping</strong></div>
                    <div class="panel-body">
                        <p>When a job card is routed to a department, all active staff with the following roles are notified:</p>
                        <div class="form-group">
                            <label>Studio/Production role name</label>
                            <input type="text" name="jc_studio_role" class="form-control" value="<?php echo e($settings['jc_studio_role'] ?? 'Studio/Production'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Stores role name</label>
                            <input type="text" name="jc_stores_role" class="form-control" value="<?php echo e($settings['jc_stores_role'] ?? 'Storekeeper/Stores Clerk'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Store Manager role name</label>
                            <input type="text" name="jc_store_manager_role" class="form-control" value="<?php echo e($settings['jc_store_manager_role'] ?? 'Store Manager'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Field Team role name</label>
                            <input type="text" name="jc_field_team_role" class="form-control" value="<?php echo e($settings['jc_field_team_role'] ?? 'Field Team'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Warehouse role name</label>
                            <input type="text" name="jc_warehouse_role" class="form-control" value="<?php echo e($settings['jc_warehouse_role'] ?? 'Storekeeper/Stores Clerk'); ?>">
                        </div>
                        <p class="text-warning"><small>Role names must match exactly as defined in Setup &gt; Roles</small></p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><strong>WAC &amp; GL</strong></div>
                    <div class="panel-body">
                        <p class="text-muted mbot0">
                            Material issues deduct from <?php echo db_prefix(); ?>ware_commodity.current_quantity.
                            WAC recalculation and GL posting (Dr WIP / Cr Inventory) is handled by the accounting module.
                            Ensure the Accounting addon is active.
                        </p>
                    </div>
                </div>

                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function($){
    var year = <?php echo json_encode($year); ?>;
    function pad5(n){
        n = parseInt(n, 10) || 1;
        if (n < 1) n = 1;
        return String(n).padStart(5, '0');
    }
    function updatePreview(){
        var jp = ($('#jc_prefix').val() || 'JC').trim();
        var jn = parseInt($('#jc_next_number').val(), 10) || 1;
        var ip = ($('#iss_prefix').val() || 'ISS').trim();
        var inum = parseInt($('#iss_next_number').val(), 10) || 1;
        $('#jc-preview-ref').text(jp + '-' + year + '-' + pad5(jn));
        $('#jc-preview-iss').text(ip + '-' + year + '-' + pad5(inum));
    }
    $(function(){
        $('.jc-preview-input').on('input change', updatePreview);
    });
})(jQuery);
</script>
</body>
</html>
