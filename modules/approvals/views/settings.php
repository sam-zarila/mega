<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$doc_order = ['quotation', 'credit_note', 'journal_entry', 'payment', 'purchase_requisition'];
$headings  = [
    'quotation'             => _l('approval_settings_heading_quotation'),
    'credit_note'           => _l('approval_settings_heading_credit_note'),
    'journal_entry'         => _l('approval_settings_heading_journal'),
    'payment'               => _l('approval_settings_heading_payment'),
    'purchase_requisition'  => _l('approval_settings_heading_pr'),
];

$by = [];
foreach ($thresholds as $t) {
    $by[$t->document_type] = $t;
}

$fmt_mwk = static function ($n) {
    return 'MWK ' . number_format((float) $n, 2, '.', ',');
};

$summary_rows = [];
foreach ($doc_order as $dt) {
    if (empty($by[$dt])) {
        continue;
    }
    $t = $by[$dt];
    $h = $headings[$dt];

    if ((int) $t->always_gm === 1) {
        $summary_rows[] = [
            'type'   => $h,
            'range'  => _l('approval_settings_summary_all_values'),
            'routes' => _l('approval_settings_summary_gm_always'),
        ];
        continue;
    }

    if ($dt === 'purchase_requisition') {
        if ((int) $t->total_stages > 1) {
            $summary_rows[] = [
                'type'   => $h,
                'range'  => _l('approval_settings_summary_stage1'),
                'routes' => $t->tier1_role ?: '—',
            ];
            $summary_rows[] = [
                'type'   => '',
                'range'  => _l('approval_settings_summary_stage2'),
                'routes' => $t->tier2_role ?: '—',
            ];
        } else {
            $summary_rows[] = [
                'type'   => $h,
                'range'  => _l('approval_settings_summary_all_values'),
                'routes' => $t->tier1_role ?: '—',
            ];
        }
        continue;
    }

    if ($dt === 'quotation') {
        $first = true;
        if (!empty($t->tier1_role) && $t->tier1_max !== null && $t->tier1_max !== '') {
            $summary_rows[] = [
                'type'   => $h,
                'range'  => sprintf(_l('approval_settings_summary_range_to'), 'MWK 0', $fmt_mwk($t->tier1_max)),
                'routes' => $t->tier1_role,
            ];
            $first = false;
        }
        if (!empty($t->tier2_role) && $t->tier2_max !== null && $t->tier2_max !== '' && $t->tier1_max !== null && $t->tier1_max !== '') {
            $lo = $fmt_mwk((float) $t->tier1_max + 0.01);
            $summary_rows[] = [
                'type'   => $first ? $h : '',
                'range'  => sprintf(_l('approval_settings_summary_range_to'), $lo, $fmt_mwk($t->tier2_max)),
                'routes' => $t->tier2_role,
            ];
            $first = false;
        }
        if (!empty($t->tier3_role)) {
            $range = ($t->tier2_max !== null && $t->tier2_max !== '')
                ? sprintf(_l('approval_settings_summary_above'), $fmt_mwk($t->tier2_max))
                : _l('approval_settings_summary_all_values');
            $summary_rows[] = [
                'type'   => $first ? $h : '',
                'range'  => $range,
                'routes' => $t->tier3_role,
            ];
            $first = false;
        }
        if ($first) {
            $summary_rows[] = [
                'type'   => $h,
                'range'  => _l('approval_settings_summary_all_values'),
                'routes' => '—',
            ];
        }
        continue;
    }

    $summary_rows[] = [
        'type'   => $h,
        'range'  => _l('approval_settings_summary_all_values'),
        'routes' => $t->tier3_role ?: ($t->tier1_role ?: '—'),
    ];
}
?>
<style>
.approval-settings-summary { margin-bottom: 24px; }
.approval-settings-summary th { background: #fafbfc; font-weight: 600; }
.approval-settings-panel-h {
    background: #f4f6f9;
    border-bottom: 1px solid #e4e8f0;
    padding: 12px 16px;
    font-size: 15px;
    font-weight: 600;
    margin: 0;
}
.approval-settings-hint { color: #6b7280; font-size: 13px; margin-top: 6px; }
.approval-settings-locked-toggle .checkbox { margin-bottom: 0; }
.approval-settings-locked-toggle input[disabled] { cursor: not-allowed; }
</style>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-bold tw-text-xl tw-mb-2"><?= e($title); ?></h4>
                <p class="text-muted tw-mb-3">
                    <a href="<?= admin_url('approvals'); ?>"><?= _l('approval_settings_back_dashboard'); ?></a>
                </p>
                <p class="tw-mb-4"><?= e(_l('approval_settings_intro')); ?></p>

                <div class="panel_s approval-settings-summary">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-3"><?= e(_l('approval_settings_effective_routing')); ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('approval_settings_col_doc_type')); ?></th>
                                        <th><?= e(_l('approval_settings_col_value_range')); ?></th>
                                        <th><?= e(_l('approval_settings_col_routes_to')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($summary_rows as $sr) { ?>
                                    <tr>
                                        <td><?= $sr['type'] !== '' ? e($sr['type']) : ''; ?></td>
                                        <td><?= e($sr['range']); ?></td>
                                        <td><?= e($sr['routes']); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?= form_open(admin_url('approvals/settings')); ?>

                <?php
                $q = $by['quotation'] ?? null;
                if ($q) {
                    $dt = 'quotation';
                    ?>
                <div class="panel_s tw-mb-4">
                    <h5 class="approval-settings-panel-h"><?= e($headings[$dt]); ?></h5>
                    <div class="panel-body">
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][always_gm]" value="0" />
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_route_upto')); ?></label>
                                    <div class="row">
                                        <div class="col-sm-6 tw-mb-2">
                                            <input type="text" name="thresholds[<?= e($dt); ?>][tier1_role]" class="form-control"
                                                value="<?= e($q->tier1_role ?: 'Sales Manager'); ?>"
                                                placeholder="<?= e('Sales Manager'); ?>" />
                                        </div>
                                        <div class="col-sm-6 tw-mb-2">
                                            <div class="input-group">
                                                <span class="input-group-addon">MWK</span>
                                                <input type="number" step="0.01" name="thresholds[<?= e($dt); ?>][tier1_max]" class="form-control"
                                                    value="<?= e($q->tier1_max !== null && $q->tier1_max !== '' ? $q->tier1_max : '3000000'); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_route_between')); ?></label>
                                    <div class="row">
                                        <div class="col-sm-6 tw-mb-2">
                                            <input type="text" name="thresholds[<?= e($dt); ?>][tier2_role]" class="form-control"
                                                value="<?= e($q->tier2_role ?: 'Finance Manager'); ?>"
                                                placeholder="<?= e('Finance Manager'); ?>" />
                                        </div>
                                        <div class="col-sm-6 tw-mb-2">
                                            <div class="input-group">
                                                <span class="input-group-addon">MWK</span>
                                                <input type="number" step="0.01" name="thresholds[<?= e($dt); ?>][tier2_max]" class="form-control"
                                                    value="<?= e($q->tier2_max !== null && $q->tier2_max !== '' ? $q->tier2_max : '5000000'); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_route_exceeds')); ?></label>
                                    <input type="text" name="thresholds[<?= e($dt); ?>][tier3_role]" class="form-control"
                                        value="<?= e($q->tier3_role ?: 'General Manager'); ?>"
                                        placeholder="<?= e('General Manager'); ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_sla_hours')); ?></label>
                                    <p class="text-muted small tw-mb-1"><?= e(_l('approval_settings_sla_label_before')); ?> … <?= e(_l('approval_settings_sla_label_after')); ?></p>
                                    <input type="number" min="1" name="thresholds[<?= e($dt); ?>][sla_hours]" class="form-control"
                                        value="<?= (int) $q->sla_hours; ?>" style="max-width:160px;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php
                }

                $gm_types = ['credit_note', 'journal_entry', 'payment'];
                foreach ($gm_types as $dt) {
                    $t = $by[$dt] ?? null;
                    if (!$t) {
                        continue;
                    }
                    ?>
                <div class="panel_s tw-mb-4">
                    <h5 class="approval-settings-panel-h"><?= e($headings[$dt]); ?></h5>
                    <div class="panel-body">
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][always_gm]" value="1" />
                        <div class="form-group approval-settings-locked-toggle">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" checked disabled />
                                    <?= e(_l('approval_settings_always_gm_locked')); ?>
                                </label>
                            </div>
                        </div>
                        <p class="approval-settings-hint"><?= e(_l('approval_settings_gm_policy_note')); ?></p>
                        <div class="form-group">
                            <label><?= e(_l('approval_sla_hours')); ?></label>
                            <p class="text-muted small tw-mb-1"><?= e(_l('approval_settings_sla_label_before')); ?> … <?= e(_l('approval_settings_sla_label_after')); ?></p>
                            <input type="number" min="1" name="thresholds[<?= e($dt); ?>][sla_hours]" class="form-control"
                                value="<?= (int) $t->sla_hours; ?>" style="max-width:160px;" />
                        </div>
                    </div>
                </div>
                    <?php
                }

                $pr = $by['purchase_requisition'] ?? null;
                if ($pr) {
                    $dt = 'purchase_requisition';
                    $pr_two = (int) $pr->total_stages > 1;
                    ?>
                <div class="panel_s tw-mb-4">
                    <h5 class="approval-settings-panel-h"><?= e($headings[$dt]); ?></h5>
                    <div class="panel-body">
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][always_gm]" value="0" />
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][enable_two_stage]" value="0" />
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_pr_stage1_label')); ?></label>
                                    <input type="text" name="thresholds[<?= e($dt); ?>][tier1_role]" class="form-control"
                                        value="<?= e($pr->tier1_role ?: 'Finance Manager'); ?>"
                                        placeholder="<?= e('Finance Manager'); ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_pr_stage1_max')); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-addon">MWK</span>
                                        <input type="number" step="0.01" name="thresholds[<?= e($dt); ?>][tier1_max]" class="form-control"
                                            value="<?= e($pr->tier1_max !== null && $pr->tier1_max !== '' ? $pr->tier1_max : '5000000'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= e(_l('approval_settings_pr_stage2_label')); ?></label>
                                    <input type="text" name="thresholds[<?= e($dt); ?>][tier2_role]" class="form-control"
                                        value="<?= e($pr->tier2_role ?: 'General Manager'); ?>"
                                        placeholder="<?= e('General Manager'); ?>" />
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][tier2_max]" value="" />
                        <input type="hidden" name="thresholds[<?= e($dt); ?>][tier3_role]" value="" />
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="thresholds[<?= e($dt); ?>][enable_two_stage]" value="1"<?= $pr_two ? ' checked' : ''; ?> />
                                    <?= e(_l('approval_settings_pr_two_stage')); ?>
                                </label>
                            </div>
                            <p class="approval-settings-hint tw-mb-0"><?= e(_l('approval_settings_pr_note')); ?></p>
                        </div>
                        <div class="form-group">
                            <label><?= e(_l('approval_sla_hours')); ?></label>
                            <p class="text-muted small tw-mb-1"><?= e(_l('approval_settings_sla_label_before')); ?> … <?= e(_l('approval_settings_sla_label_after')); ?></p>
                            <input type="number" min="1" name="thresholds[<?= e($dt); ?>][sla_hours]" class="form-control"
                                value="<?= (int) $pr->sla_hours; ?>" style="max-width:160px;" />
                        </div>
                    </div>
                </div>
                    <?php
                }
                ?>

                <button type="submit" class="btn btn-primary btn-lg"><?= e(_l('approval_settings_save_all')); ?></button>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
