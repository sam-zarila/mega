<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$settings = isset($settings) && is_array($settings) ? $settings : [];
$v        = static function ($key, $default = '') use ($settings) {
    return array_key_exists($key, $settings) ? (string) $settings[$key] : $default;
};

$yearOn      = $v('qt_year_in_ref', '1') === '1' || strtolower($v('qt_year_in_ref', '1')) === 'true';
$logoPath    = FCPATH . 'uploads/quotation_logo.png';
$logoExists  = is_file($logoPath);
$logoPreview = $logoExists ? base_url('uploads/quotation_logo.png?v=' . (string) @filemtime($logoPath)) : '';

$threshold = isset($quotation_threshold) ? $quotation_threshold : false;
$thresholdRows = [];
if ($threshold) {
    $t1 = isset($threshold->tier1_max) && $threshold->tier1_max !== null && $threshold->tier1_max !== ''
        ? (float) $threshold->tier1_max : null;
    $t2 = isset($threshold->tier2_max) && $threshold->tier2_max !== null && $threshold->tier2_max !== ''
        ? (float) $threshold->tier2_max : null;
    $r1 = isset($threshold->tier1_role) ? trim((string) $threshold->tier1_role) : '';
    $r2 = isset($threshold->tier2_role) ? trim((string) $threshold->tier2_role) : '';
    $r3 = isset($threshold->tier3_role) ? trim((string) $threshold->tier3_role) : '';

    $fmtMwk = static function ($n) {
        return 'MWK ' . number_format((float) $n, 0, '.', ',');
    };

    if ($t1 !== null && $r1 !== '') {
        $thresholdRows[] = ['range' => 'Up to ' . $fmtMwk($t1), 'role' => $r1];
    }
    if ($t1 !== null && $t2 !== null && $r2 !== '') {
        $thresholdRows[] = ['range' => $fmtMwk($t1) . ' to ' . $fmtMwk($t2), 'role' => $r2];
    }
    if ($t2 !== null && $r3 !== '') {
        $thresholdRows[] = ['range' => 'Above ' . $fmtMwk($t2), 'role' => $r3];
    }
}

$nextPreview  = max(1, (int) $v('qt_next_number', '1'));
$paddedPrev   = str_pad((string) $nextPreview, 5, '0', STR_PAD_LEFT);
$prefixPrev   = trim($v('qt_prefix', 'QT'));
$prefixPrev   = $prefixPrev !== '' ? $prefixPrev : 'QT';
$refPreviewPhp = $yearOn ? $prefixPrev . '-' . date('Y') . '-' . $paddedPrev : $prefixPrev . '-' . $paddedPrev;

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <?php echo form_open_multipart(admin_url('quotations/save_settings'), ['id' => 'qt-settings-form']); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo html_escape($title); ?></h4>
                        <p class="text-muted mtop5">Configure quotation references, defaults, PDF branding, and related options. Only administrators can access this page.</p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <span class="tw-font-semibold">Reference format</span>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">Changing the prefix or year rule affects <strong>new</strong> references only; existing quotation references are unchanged.</p>
                        <?php echo render_input('qt_prefix', 'Prefix', $v('qt_prefix', 'QT'), 'text', ['id' => 'qt_prefix', 'autocomplete' => 'off']); ?>
                        <div class="form-group">
                            <label for="qt_year_in_ref" class="control-label">Include year in reference</label>
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="qt_year_in_ref" value="1" id="qt_year_in_ref" <?php echo $yearOn ? 'checked' : ''; ?>>
                                <label for="qt_year_in_ref">Yes — use format PREFIX-YEAR-##### when generating the next reference</label>
                            </div>
                        </div>
                        <?php echo render_input('qt_next_number', 'Next quotation number', $v('qt_next_number', '1'), 'number', ['id' => 'qt_next_number', 'min' => 1, 'step' => 1]); ?>
                        <p class="text-muted">Set to <strong>1</strong> to restart numbering (still combined with prefix and optional year).</p>
                        <div class="well well-sm mtop15">
                            <strong>Preview:</strong>
                            <span id="qt_ref_preview" class="text-success"><?php echo html_escape('Next reference will be: ' . $refPreviewPhp); ?></span>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <span class="tw-font-semibold">Financial defaults</span>
                    </div>
                    <div class="panel-body">
                        <?php echo render_input('qt_vat_rate', 'Default VAT rate (%)', $v('qt_vat_rate', '16.5'), 'number', ['step' => '0.01', 'min' => 0]); ?>
                        <?php echo render_input('qt_default_markup', 'Default markup (%)', $v('qt_default_markup', '25'), 'number', ['step' => '0.01', 'min' => 0]); ?>
                        <?php echo render_input('qt_default_contingency', 'Default contingency (%)', $v('qt_default_contingency', '0'), 'number', ['step' => '0.01', 'min' => 0]); ?>
                        <?php echo render_input('qt_default_validity_days', 'Default validity (days)', $v('qt_default_validity_days', '30'), 'number', ['min' => 1, 'step' => 1]); ?>
                        <?php echo render_input(
                            'qt_discount_requires_approval_above',
                            'Discount requiring approval above (%)',
                            $v('qt_discount_requires_approval_above', '10'),
                            'number',
                            ['step' => '0.01', 'min' => 0]
                        ); ?>
                        <p class="text-muted mtop5">Discounts above this % trigger a warning on the builder.</p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <span class="tw-font-semibold">Company details (printed on PDF)</span>
                    </div>
                    <div class="panel-body">
                        <?php echo render_input('qt_company_name', 'Company name', $v('qt_company_name', 'MW')); ?>
                        <?php echo render_textarea('qt_company_address', 'Company address', $v('qt_company_address', ''), ['rows' => 4]); ?>
                        <?php echo render_input('qt_vat_number', 'VAT registration number', $v('qt_vat_number', '')); ?>
                        <div class="form-group">
                            <label for="quotation_logo" class="control-label">Company logo</label>
                            <input type="file" name="quotation_logo" id="quotation_logo" class="form-control" accept="image/png,image/jpeg,image/gif,.png,.jpg,.jpeg,.gif">
                            <p class="text-muted mtop5">Uploaded images are saved as <code>uploads/quotation_logo.png</code> (PNG on disk).</p>
                            <?php if ($logoExists) { ?>
                                <div class="mtop10">
                                    <span class="text-muted">Current logo:</span><br>
                                    <img src="<?php echo html_escape($logoPreview); ?>" alt="Quotation logo" style="max-height:80px;max-width:220px;margin-top:6px;border:1px solid #ddd;padding:4px;background:#fff;">
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <span class="tw-font-semibold">PDF content</span>
                    </div>
                    <div class="panel-body">
                        <?php echo render_textarea('qt_terms_and_conditions', 'Terms and conditions', $v('qt_terms_and_conditions', ''), ['rows' => 10]); ?>
                        <?php echo render_input('qt_pdf_footer_text', 'PDF footer text', $v('qt_pdf_footer_text', '')); ?>
                        <?php echo render_textarea('qt_payment_terms', 'Payment terms text', $v('qt_payment_terms', ''), ['rows' => 5]); ?>
                        <p class="text-muted mtop5">Payment terms are stored for use on outputs that read this setting (ensure your PDF template uses it if you customize the PDF).</p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <span class="tw-font-semibold">Approval thresholds reference</span>
                    </div>
                    <div class="panel-body">
                        <p>Quotation approval routing is configured in the Approvals module:</p>
                        <?php if ($thresholdRows !== []) { ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Value range</th>
                                        <th>Routes to</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($thresholdRows as $row) { ?>
                                        <tr>
                                            <td><?php echo html_escape($row['range']); ?></td>
                                            <td><?php echo html_escape($row['role']); ?></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } elseif ($threshold) { ?>
                            <p class="text-muted">Thresholds exist but could not be summarised automatically. Edit them in the Approvals module.</p>
                        <?php } else { ?>
                            <p class="text-muted">No quotation thresholds found. Install or configure the Approvals module.</p>
                        <?php } ?>
                        <a href="<?php echo admin_url('approvals/settings'); ?>" class="btn btn-default mtop10">Edit thresholds →</a>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-primary"><?php echo _l('settings_save'); ?></button>
                        <a href="<?php echo admin_url('quotations'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var year = <?php echo json_encode((int) date('Y')); ?>;

    function padNum(n, len) {
        var s = String(Math.max(1, parseInt(n, 10) || 1));
        while (s.length < len) { s = '0' + s; }
        return s;
    }

    function updatePreview() {
        var prefixEl = document.getElementById('qt_prefix');
        var yearEl = document.getElementById('qt_year_in_ref');
        var nextEl = document.getElementById('qt_next_number');
        var out = document.getElementById('qt_ref_preview');
        if (!prefixEl || !yearEl || !nextEl || !out) { return; }

        var prefix = (prefixEl.value || '').trim() || 'QT';
        var includeYear = yearEl.checked;
        var padded = padNum(nextEl.value, 5);
        var ref = includeYear ? (prefix + '-' + year + '-' + padded) : (prefix + '-' + padded);
        out.textContent = 'Next reference will be: ' + ref;
    }

    ['qt_prefix', 'qt_year_in_ref', 'qt_next_number'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });
    updatePreview();
})();
</script>
<?php init_tail(); ?>
