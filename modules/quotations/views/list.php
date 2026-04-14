<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI              = &get_instance();
$summary         = isset($statuses_summary) && is_array($statuses_summary) ? $statuses_summary : [];
$rows            = isset($quotations) && is_array($quotations) ? $quotations : [];
$currentStaffId  = isset($current_staff_id) ? (int) $current_staff_id : (int) get_staff_user_id();
$canCreate       = staff_can('create', 'estimates');

$tab_labels = [
    'signage'        => 'Signage',
    'installation'   => 'Installation',
    'construction'   => 'Construction',
    'retrofitting'   => 'Retrofitting',
    'promotional'    => 'Promotional',
    'additional'     => 'Additional',
];

$format_tabs_display = static function ($tabsCsv, $serviceTypeSet) use ($tab_labels) {
    if ($tabsCsv !== null && $tabsCsv !== '') {
        $parts  = array_filter(array_map('trim', explode(',', (string) $tabsCsv)));
        $labels = [];
        foreach ($parts as $t) {
            $labels[] = $tab_labels[$t] ?? ucfirst(str_replace('_', ' ', $t));
        }

        return implode(', ', $labels);
    }
    if ($serviceTypeSet !== null && $serviceTypeSet !== '') {
        $parts  = array_filter(array_map('trim', explode(',', (string) $serviceTypeSet)));
        $labels = [];
        foreach ($parts as $t) {
            $labels[] = $tab_labels[$t] ?? ucfirst(str_replace('_', ' ', $t));
        }

        return implode(', ', $labels);
    }

    return '—';
};

init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="clearfix mbot15">
                    <div class="pull-left">
                        <h4 class="page-title tw-mt-0 mbot5"><?php echo html_escape($title); ?></h4>
                    </div>
                    <div class="pull-right">
                        <?php if ($canCreate) { ?>
                            <a href="<?php echo admin_url('quotations/create'); ?>" class="btn btn-info">
                                <i class="fa fa-plus-circle"></i> New Quotation
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="row" id="qt-stat-cards">
                    <div class="col-md-1 hidden-sm hidden-xs"></div>
                    <div class="col-md-2 col-sm-6 col-xs-6 mbot15">
                        <div class="widget-stat panel panel-default tw-cursor-pointer mbot0" data-filter-status="draft" role="button" tabindex="0" style="cursor:pointer;">
                            <div class="panel-body text-center">
                                <div class="text-muted bold">Draft</div>
                                <div class="bold text-muted" style="font-size:22px;"><?php echo (int) ($summary['draft'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 col-xs-6 mbot15">
                        <div class="widget-stat panel panel-default tw-cursor-pointer mbot0" data-filter-status="submitted" role="button" tabindex="0" style="cursor:pointer;">
                            <div class="panel-body text-center">
                                <div class="text-warning bold">Submitted</div>
                                <div class="bold text-warning" style="font-size:22px;"><?php echo (int) ($summary['submitted'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 col-xs-6 mbot15">
                        <div class="widget-stat panel panel-default tw-cursor-pointer mbot0" data-filter-status="approved" role="button" tabindex="0" style="cursor:pointer;">
                            <div class="panel-body text-center">
                                <div class="text-success bold">Approved</div>
                                <div class="bold text-success" style="font-size:22px;"><?php echo (int) ($summary['approved'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 col-xs-6 mbot15">
                        <div class="widget-stat panel panel-default tw-cursor-pointer mbot0" data-filter-status="rejected" role="button" tabindex="0" style="cursor:pointer;">
                            <div class="panel-body text-center">
                                <div class="text-danger bold">Rejected</div>
                                <div class="bold text-danger" style="font-size:22px;"><?php echo (int) ($summary['rejected'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6 col-xs-6 mbot15">
                        <div class="widget-stat panel panel-default tw-cursor-pointer mbot0" data-filter-status="converted" role="button" tabindex="0" style="cursor:pointer;">
                            <div class="panel-body text-center">
                                <div class="text-info bold">Converted</div>
                                <div class="bold text-info" style="font-size:22px;"><?php echo (int) ($summary['converted'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 hidden-sm hidden-xs"></div>
                </div>

                <div class="panel_s mbot15">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-2 col-sm-6 mbot10">
                                <label class="control-label"><?php echo _l('estimate_status'); ?></label>
                                <select id="qt-filter-status" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                    <option value="">All</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="converted">Converted</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 mbot10">
                                <label class="control-label"><?php echo _l('client'); ?></label>
                                <input type="text" id="qt-filter-client" class="form-control" placeholder="Search client…" autocomplete="off">
                            </div>
                            <div class="col-md-2 col-sm-6 mbot10">
                                <label class="control-label">From</label>
                                <input type="date" id="qt-filter-date-from" class="form-control">
                            </div>
                            <div class="col-md-2 col-sm-6 mbot10">
                                <label class="control-label">To</label>
                                <input type="date" id="qt-filter-date-to" class="form-control">
                            </div>
                            <div class="col-md-2 col-sm-6 mbot10">
                                <label class="control-label">&nbsp;</label>
                                <div class="checkbox mtop10">
                                    <label>
                                        <input type="checkbox" id="qt-filter-mine" value="1">
                                        My Quotations
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-1 col-sm-12 mbot10">
                                <label class="control-label">&nbsp;</label>
                                <button type="button" id="qt-apply-filters" class="btn btn-default btn-block">Apply Filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="qt-quotations-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th><?php echo _l('client'); ?></th>
                                        <th>Service Type</th>
                                        <th class="text-right">Value</th>
                                        <th>Prepared By</th>
                                        <th>Date</th>
                                        <th>Expiry</th>
                                        <th><?php echo _l('estimate_status'); ?></th>
                                        <th class="text-right"><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($rows) === 0) { ?>
                                        <tr id="qt-empty-initial">
                                            <td colspan="9" class="text-center text-muted mtop20 mbot20">
                                                <p class="bold mtop15"><?php echo _l('no_data_found'); ?></p>
                                                <p>No quotations found.</p>
                                                <?php if ($canCreate) { ?>
                                                    <a href="<?php echo admin_url('quotations/create'); ?>" class="btn btn-info mtop10">
                                                        <i class="fa fa-plus-circle"></i> Create your first quotation
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } else {
                                        $today = date('Y-m-d');
                                        foreach ($rows as $r) {
                                            $id            = (int) ($r['id'] ?? 0);
                                            $ref           = $r['quotation_ref'] ?? '';
                                            $version       = (int) ($r['version'] ?? 1);
                                            $clientId      = (int) ($r['client_id'] ?? 0);
                                            $clientCompany = $r['client_company'] ?? '';
                                            $grand         = isset($r['grand_total']) ? (float) $r['grand_total'] : 0.0;
                                            $status        = $r['status'] ?? '';
                                            $createdAt     = $r['created_at'] ?? '';
                                            $validDays     = (int) ($r['validity_days'] ?? 30);
                                            $createdBy     = (int) ($r['created_by'] ?? 0);
                                            $creatorName   = $r['creator_name'] ?? '';
                                            $tabsDisp      = $format_tabs_display($r['tabs_with_content'] ?? '', $r['service_type'] ?? '');

                                            $createdDate = '';
                                            if ($createdAt !== '') {
                                                $createdDate = date('Y-m-d', strtotime($createdAt));
                                            }
                                            $expiryDate = '';
                                            $expired    = false;
                                            if ($createdAt !== '') {
                                                $expiryTs   = strtotime($createdAt . ' +' . max(0, $validDays) . ' days');
                                                $expiryDate = date('Y-m-d', $expiryTs);
                                                $expired    = ($expiryDate < $today && $status !== 'converted');
                                            }

                                            $valueClass = $grand > 1000000 ? 'bold' : '';
                                            ?>
                                            <tr class="qt-row"
                                                data-status="<?php echo html_escape($status); ?>"
                                                data-client="<?php echo html_escape(strtolower($clientCompany)); ?>"
                                                data-created="<?php echo html_escape($createdDate); ?>"
                                                data-created-by="<?php echo (int) $createdBy; ?>"
                                                data-grand="<?php echo htmlspecialchars((string) $grand, ENT_QUOTES, 'UTF-8'); ?>">
                                                <td>
                                                    <a href="<?php echo admin_url('quotations/view/' . $id); ?>"><?php echo html_escape($ref); ?></a>
                                                    <?php if ($version >= 2) { ?>
                                                        <span class="label label-info">v<?php echo (int) $version; ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if ($clientId > 0) { ?>
                                                        <a href="<?php echo admin_url('clients/client/' . $clientId); ?>"><?php echo html_escape($clientCompany ?: '—'); ?></a>
                                                    <?php } else {
                                                        echo html_escape($clientCompany ?: '—');
                                                    } ?>
                                                </td>
                                                <td><?php echo html_escape($tabsDisp); ?></td>
                                                <td class="text-right <?php echo $valueClass; ?>"><?php echo qt_format_mwk($grand); ?></td>
                                                <td><?php echo html_escape(trim($creatorName) !== '' ? $creatorName : get_staff_full_name($createdBy)); ?></td>
                                                <td><?php echo $createdDate !== '' ? html_escape(_d($createdDate)) : '—'; ?></td>
                                                <td class="<?php echo $expired ? 'text-danger bold' : ''; ?>">
                                                    <?php echo $expiryDate !== '' ? html_escape(_d($expiryDate)) : '—'; ?>
                                                    <?php if ($expired) { ?>
                                                        <span class="label label-danger">Expired</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo qt_get_status_label($status); ?></td>
                                                <td class="text-right">
                                                    <a href="<?php echo admin_url('quotations/view/' . $id); ?>" class="btn btn-default btn-icon" title="<?php echo _l('view'); ?>"><i class="fa fa-eye"></i></a>
                                                    <?php if ($status === 'draft') { ?>
                                                        <a href="<?php echo admin_url('quotations/edit/' . $id); ?>" class="btn btn-default btn-icon" title="<?php echo _l('edit'); ?>"><i class="fa fa-pencil"></i></a>
                                                    <?php } ?>
                                                    <a href="<?php echo admin_url('quotations/pdf/' . $id); ?>" class="btn btn-default btn-icon" title="PDF" target="_blank"><i class="fa fa-file-pdf"></i></a>
                                                    <button type="button" class="btn btn-default btn-icon qt-btn-email" data-id="<?php echo (int) $id; ?>" title="<?php echo _l('send'); ?>"><i class="fa fa-envelope"></i></button>
                                                    <?php if ($status === 'rejected') { ?>
                                                        <button type="button" class="btn btn-default btn-icon qt-btn-revise" data-id="<?php echo (int) $id; ?>" data-ref="<?php echo html_escape($ref); ?>" title="Create revision"><i class="fa fa-copy"></i></button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php }
                                    } ?>
                                    <tr id="qt-empty-filtered" style="display:none;">
                                        <td colspan="9" class="text-center text-muted mtop20 mbot20">
                                            <p class="bold">No quotations match your filters.</p>
                                            <button type="button" class="btn btn-default btn-sm" id="qt-clear-filters">Clear filters</button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="active">
                                        <td colspan="3" class="text-right bold"><?php echo _l('invoice_total'); ?> <span class="text-muted normal">(visible)</span></td>
                                        <td class="text-right bold" id="qt-total-visible"><?php echo qt_format_mwk(0); ?></td>
                                        <td colspan="5"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qt-modal-email" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?php echo _l('send'); ?> <?php echo _l('estimate'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="qt-email-quotation-id" value="">
                <div class="form-group">
                    <label><?php echo _l('clients_email'); ?></label>
                    <input type="email" id="qt-email-recipient" class="form-control" required>
                </div>
                <div id="qt-email-alert" class="hide alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-info" id="qt-email-send"><?php echo _l('send'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qt-modal-revise" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('', ['id' => 'qt-form-revise']); ?>
            <?php echo form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash()); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Create revision <span id="qt-revise-ref"></span></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Revision notes</label>
                    <textarea name="revision_notes" class="form-control" rows="3" required placeholder="Reason for this revision"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="submit" class="btn btn-info">Create revision</button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
(function($) {
    var currentStaffId = <?php echo (int) $currentStaffId; ?>;
    var qtUrlSendEmail = <?php echo json_encode(admin_url('quotations/send_email/')); ?>;
    var qtUrlReviseBase = <?php echo json_encode(admin_url('quotations/create_revision/')); ?>;

    function parseGrand(v) {
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    function applyFilters() {
        var st = $('#qt-filter-status').val() || '';
        var clientQ = ($('#qt-filter-client').val() || '').toLowerCase().trim();
        var dFrom = $('#qt-filter-date-from').val() || '';
        var dTo = $('#qt-filter-date-to').val() || '';
        var mine = $('#qt-filter-mine').is(':checked');
        var visible = 0;
        var total = 0;

        $('.qt-row').each(function() {
            var $r = $(this);
            var ok = true;
            if (st && $r.data('status') !== st) ok = false;
            if (clientQ) {
                var c = ($r.data('client') || '').toString();
                if (c.indexOf(clientQ) === -1) ok = false;
            }
            var cd = ($r.data('created') || '').toString();
            if (dFrom && (!cd || cd < dFrom)) ok = false;
            if (dTo && (!cd || cd > dTo)) ok = false;
            if (mine && parseInt($r.data('created-by'), 10) !== currentStaffId) ok = false;
            if (ok) {
                $r.show();
                visible++;
                total += parseGrand($r.data('grand'));
            } else {
                $r.hide();
            }
        });

        var rowCount = $('.qt-row').length;
        $('#qt-empty-filtered').toggle(visible === 0 && rowCount > 0);
        if (rowCount > 0) {
            $('#qt-empty-initial').hide();
        }

        var parts = total.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        $('#qt-total-visible').text('MWK ' + parts.join('.'));
    }

    $(function() {
        if ($.fn.selectpicker && $('.selectpicker').length) {
            $('.selectpicker').selectpicker();
        }
        applyFilters();

        $('#qt-apply-filters').on('click', applyFilters);
        $('#qt-filter-client').on('keyup', function() {
            applyFilters();
        });
        $('#qt-clear-filters').on('click', function() {
            $('#qt-filter-status').val('');
            if ($.fn.selectpicker && $('.selectpicker').length) {
                $('.selectpicker').selectpicker('refresh');
            }
            $('#qt-filter-client').val('');
            $('#qt-filter-date-from').val('');
            $('#qt-filter-date-to').val('');
            $('#qt-filter-mine').prop('checked', false);
            applyFilters();
        });

        $('.widget-stat[data-filter-status]').on('click keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) return;
            var s = $(this).data('filter-status');
            $('#qt-filter-status').val(s);
            if ($.fn.selectpicker && $('.selectpicker').length) {
                $('.selectpicker').selectpicker('refresh');
            }
            applyFilters();
        });

        $('.qt-btn-email').on('click', function() {
            var id = $(this).data('id');
            $('#qt-email-quotation-id').val(id);
            $('#qt-email-recipient').val('');
            $('#qt-email-alert').addClass('hide').removeClass('alert-success alert-danger').text('');
            $('#qt-modal-email').modal('show');
        });

        $('#qt-email-send').on('click', function() {
            var id = $('#qt-email-quotation-id').val();
            var email = ($('#qt-email-recipient').val() || '').trim();
            var $al = $('#qt-email-alert');
            if (!email) {
                $al.removeClass('hide').addClass('alert-danger').text('Email is required.');
                return;
            }
            var data = { recipient_email: email };
            if (typeof csrfData !== 'undefined') {
                data[csrfData.token_name] = csrfData.hash;
            }
            $.post(qtUrlSendEmail + id, data)
                .done(function(resp) {
                    try {
                        var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        if (j.success) {
                            $al.removeClass('hide').removeClass('alert-danger').addClass('alert-success').text(j.message || 'Sent.');
                            setTimeout(function() { $('#qt-modal-email').modal('hide'); }, 800);
                        } else {
                            $al.removeClass('hide').addClass('alert-danger').text(j.message || 'Failed.');
                        }
                    } catch (err) {
                        $al.removeClass('hide').addClass('alert-danger').text('Invalid response.');
                    }
                })
                .fail(function() {
                    $al.removeClass('hide').addClass('alert-danger').text('Request failed.');
                });
        });

        $('.qt-btn-revise').on('click', function() {
            var id = $(this).data('id');
            var ref = $(this).data('ref') || '';
            $('#qt-revise-ref').text(ref ? '(' + ref + ')' : '');
            var $form = $('#qt-form-revise');
            $form.attr('action', qtUrlReviseBase + id);
            $form.find('textarea[name="revision_notes"]').val('');
            $('#qt-modal-revise').modal('show');
        });
    });
})(jQuery);
</script>
</body>
</html>
