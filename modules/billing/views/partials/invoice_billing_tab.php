<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($meta)) { ?>
    <p class="text-muted">No IPMS billing metadata for this invoice.</p>
<?php } else { ?>
<div class="table-responsive">
    <table class="table table-bordered">
        <tbody>
            <tr><th width="200">DN reference</th><td><?php echo html_escape($meta->dn_ref ?? '—'); ?><?php if (!empty($meta->dn_id) && !empty($dn)) { ?> <span class="text-muted">(ID <?php echo (int) $meta->dn_id; ?>)</span><?php } ?></td></tr>
            <tr><th>Job card</th><td><?php echo html_escape($meta->jc_ref ?? '—'); ?><?php if (!empty($meta->job_card_id) && !empty($jc)) { ?> <span class="text-muted">(ID <?php echo (int) $meta->job_card_id; ?>)</span><?php } ?></td></tr>
            <tr><th>QT reference</th><td><?php echo html_escape($meta->qt_ref ?? '—'); ?></td></tr>
            <tr><th>Proforma</th><td><?php echo (int) $meta->is_proforma === 1 ? 'Yes — ' . html_escape($meta->proforma_ref ?? '') : 'No'; ?></td></tr>
            <tr><th>VAT reg (printed)</th><td><?php echo html_escape($meta->vat_registration_no ?? '—'); ?></td></tr>
            <tr><th>VAT rate / amount</th><td><?php echo html_escape((string) $meta->vat_rate); ?>% / <?php echo html_escape(app_format_money($meta->vat_amount, '')); ?></td></tr>
            <tr><th>GL posted</th><td><?php echo (int) $meta->gl_posted === 1 ? 'Yes @ ' . html_escape((string) ($meta->gl_posted_at ?? '')) : 'No'; ?></td></tr>
        </tbody>
    </table>
</div>
<?php } ?>
