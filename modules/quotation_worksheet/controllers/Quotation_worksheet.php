<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quotation_worksheet extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_staff_logged_in()) {
            return;
        }
        $this->load->helper('quotation_worksheet/quotation_worksheet');
        $this->lang->load('quotation_worksheet/quotation_worksheet', $GLOBALS['language'] ?? 'english');
    }

    /**
     * HTML fragment for proposal worksheet panel (AJAX).
     *
     * @param int $proposal_id
     */
    public function get_panel($proposal_id = 0)
    {
        $proposal_id = (int) $proposal_id;

        if ($proposal_id > 0 && !qt_can_view_proposal($proposal_id)) {
            echo '<div class="alert alert-danger">Access denied.</div>';

            return;
        }

        if ($proposal_id <= 0) {
            $data = [
                'proposal_id' => 0,
                'proposal'    => null,
                'worksheet'   => (object) [
                    'qt_ref'               => '',
                    'validity_days'        => (int) qt_setting('qt_default_validity_days', 30),
                    'terms'                => qt_setting('qt_terms_and_conditions', ''),
                    'internal_notes'       => '',
                    'contingency_percent'  => 0,
                    'discount_percent'     => 0,
                    'total_cost'           => 0,
                    'total_sell'           => 0,
                    'contingency_amount'   => 0,
                    'discount_amount'      => 0,
                    'vat_amount'           => 0,
                    'grand_total'          => 0,
                    'qt_status'            => 'draft',
                ],
                'lines_by_tab' => [],
                'can_see_margins' => qt_can_see_margins(),
            ];
            $this->load->view('quotation_worksheet/worksheet_panel', $data);

            return;
        }

        $this->db->where('id', $proposal_id);
        $proposal = $this->db->get(db_prefix() . 'proposals')->row();
        if (!$proposal) {
            echo '<div class="alert alert-danger">Proposal not found.</div>';

            return;
        }

        $ws = qt_get_or_create_worksheet($proposal_id);
        if (isset($proposal->qt_status) && $proposal->qt_status) {
            $ws->qt_status = $proposal->qt_status;
        }

        $data = [
            'proposal_id'     => $proposal_id,
            'proposal'        => $proposal,
            'worksheet'       => $ws,
            'lines_by_tab'    => qt_get_lines_by_tab($proposal_id),
            'can_see_margins' => qt_can_see_margins(),
        ];
        $this->load->view('quotation_worksheet/worksheet_panel', $data);
    }

    public function save_line()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid proposal']);
            return;
        }

        $tab = $this->input->post('tab');
        if (!is_string($tab) || $tab === '') {
            echo json_encode(['success' => false, 'message' => 'Missing tab']);
            return;
        }

        $line = [
            'proposal_id'      => $proposal_id,
            'tab'              => $tab,
            'section_name'     => $this->input->post('section_name'),
            'description'      => $this->input->post('description'),
            'quantity'         => $this->input->post('quantity'),
            'unit'             => $this->input->post('unit'),
            'width_m'          => $this->input->post('width_m'),
            'height_m'         => $this->input->post('height_m'),
            'size_based'       => ($tab === 'signage' && $this->input->post('width_m') && $this->input->post('height_m')) ? 1 : 0,
            'cost_price'       => $this->input->post('cost_price'),
            'markup_percent'   => $this->input->post('markup_percent'),
            'sell_price'       => $this->input->post('sell_price'),
            'is_taxable'       => $this->input->post('is_taxable') !== null ? (int) $this->input->post('is_taxable') : 1,
            'item_code'        => $this->input->post('item_code'),
            'commodity_id'     => (int) $this->input->post('commodity_id'),
            'substrate'        => $this->input->post('substrate'),
            'print_type'       => $this->input->post('print_type'),
            'activity_type'    => $this->input->post('activity_type'),
            'rate_type'        => $this->input->post('rate_type'),
            'rate_value'       => $this->input->post('rate_value'),
            'duration'         => $this->input->post('duration'),
            'stock_qty'        => $this->input->post('stock_qty'),
        ];

        if ($tab === 'installation') {
            $rv = (float) ($line['rate_value'] ?? 0);
            $du = (float) ($line['duration'] ?? $line['quantity'] ?? 1);
            if ($du <= 0) {
                $du = 1;
            }
            $line['cost_price'] = $rv * $du;
            $line['quantity']   = 1;
        }

        if (in_array($tab, ['construction', 'retrofitting'], true)) {
            $qty  = (float) ($line['quantity'] ?? 1);
            $sell = (float) ($line['sell_price'] ?? 0);
            $mk   = (float) ($line['markup_percent'] ?? qt_setting('qt_default_markup', 25));
            $cost = $sell / (1 + ($mk / 100));
            if ($cost < 0) {
                $cost = 0;
            }
            $line['cost_price']      = round($cost, 4);
            $line['line_total_cost'] = round($cost * $qty, 4);
            $line['line_total_sell'] = round($sell * $qty, 4);
        } elseif ($tab === 'additional') {
            $line['quantity']        = 1;
            $line['cost_price']      = 0;
            $line['markup_percent']  = 0;
            $sell                    = (float) ($line['sell_price'] ?? 0);
            $line['line_total_cost'] = 0;
            $line['line_total_sell'] = round($sell, 4);
        } else {
            $line = qt_calculate_line($line);
            $line['line_total_cost'] = $line['line_total_cost'] ?? 0;
            $line['line_total_sell'] = $line['line_total_sell'] ?? 0;
        }

        $line_id = (int) $this->input->post('id');
        $now     = date('Y-m-d H:i:s');

        if ($line_id > 0) {
            $this->db->where('id', $line_id);
            $this->db->where('proposal_id', $proposal_id);
            unset($line['proposal_id']);
            $line['updated_at'] = $now;
            $this->db->update(db_prefix() . 'ipms_qt_lines', $line);
        } else {
            $this->db->select_max('line_order');
            $this->db->where('proposal_id', $proposal_id);
            $this->db->where('tab', $tab);
            $ordRow = $this->db->get(db_prefix() . 'ipms_qt_lines')->row();
            $maxOrder = ($ordRow && isset($ordRow->line_order)) ? (int) $ordRow->line_order : 0;
            $line['line_order'] = $maxOrder + 1;
            $line['created_at'] = $now;
            $line['updated_at'] = $now;
            $this->db->insert(db_prefix() . 'ipms_qt_lines', $line);
            $line_id = (int) $this->db->insert_id();
        }

        $totals = qt_recalculate_totals($proposal_id);
        $row    = $this->db->get_where(db_prefix() . 'ipms_qt_lines', ['id' => $line_id])->row_array();

        echo json_encode([
            'success'    => true,
            'id'         => $line_id,
            'line'       => $row,
            'totals'     => $totals,
            'html'       => qt_render_line_row($tab, $row),
        ]);
    }

    public function delete_line()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        $line_id     = (int) $this->input->post('id');
        if ($proposal_id < 1 || $line_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        $this->db->where('id', $line_id);
        $this->db->where('proposal_id', $proposal_id);
        $this->db->delete(db_prefix() . 'ipms_qt_lines');

        $totals = qt_recalculate_totals($proposal_id);
        echo json_encode(['success' => true, 'totals' => $totals]);
    }

    public function get_lines()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        $tab = $this->input->post('tab');
        echo json_encode([
            'success' => true,
            'lines'   => qt_get_lines($proposal_id, is_string($tab) ? $tab : ''),
        ]);
    }

    public function update_totals_config()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        $_POST['qt_contingency'] = $this->input->post('contingency_percent');
        $_POST['qt_discount']    = $this->input->post('discount_percent');
        $totals                  = qt_sync_worksheet_to_proposal($proposal_id, $_POST);
        echo json_encode(['success' => true, 'totals' => $totals]);
    }

    public function save_worksheet_meta()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        $ws = qt_get_or_create_worksheet($proposal_id);
        $this->db->where('id', $ws->id);
        $this->db->update(db_prefix() . 'ipms_qt_worksheets', [
            'validity_days'  => (int) $this->input->post('validity_days'),
            'terms'          => $this->input->post('terms'),
            'internal_notes' => $this->input->post('internal_notes'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => true]);
    }

    public function reorder_lines()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        $order = $this->input->post('order');
        if (is_string($order)) {
            $decoded = json_decode($order, true);
            $order   = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($order)) {
            echo json_encode(['success' => false]);
            return;
        }

        $i = 0;
        foreach ($order as $lineId) {
            $lineId = (int) $lineId;
            if ($lineId < 1) {
                continue;
            }
            $this->db->where('id', $lineId);
            $this->db->where('proposal_id', $proposal_id);
            $this->db->update(db_prefix() . 'ipms_qt_lines', ['line_order' => ++$i]);
        }

        echo json_encode(['success' => true]);
    }

    public function get_totals()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->get('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false]);
            return;
        }

        qt_get_or_create_worksheet($proposal_id);
        echo json_encode(['success' => true, 'totals' => qt_recalculate_totals($proposal_id)]);
    }

    public function search_inventory()
    {
        if (!is_staff_logged_in()) {
            show_404();
        }

        $q = $this->input->get('q');
        $q = is_string($q) ? trim($q) : '';
        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        $p   = db_prefix();
        $tbl = $p . 'items';
        $cols = ['id', 'description', 'rate'];
        if ($this->db->field_exists('commodity_code', $tbl)) {
            $cols[] = 'commodity_code';
        }
        if ($this->db->field_exists('unit_id', $tbl)) {
            $cols[] = 'unit_id';
        }
        if ($this->db->field_exists('purchase_price', $tbl)) {
            $cols[] = 'purchase_price';
        }
        $this->db->select(implode(', ', $cols));
        $this->db->from($tbl);
        $this->db->group_start();
        if ($this->db->field_exists('commodity_code', $tbl)) {
            $this->db->like('commodity_code', $q);
            $this->db->or_like('description', $q);
        } else {
            $this->db->like('description', $q);
        }
        $this->db->group_end();
        $this->db->limit(25);
        $rows = $this->db->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $code = isset($r['commodity_code']) ? $r['commodity_code'] : '';
            $pp   = isset($r['purchase_price']) ? (float) $r['purchase_price'] : 0;
            $rate = (float) $r['rate'];
            $out[] = [
                'id'    => (int) $r['id'],
                'code'  => $code,
                'label' => $r['description'],
                'unit'  => isset($r['unit_id']) ? (string) $r['unit_id'] : '',
                'rate'  => $rate,
                'cost'  => $pp > 0 ? $pp : $rate,
            ];
        }

        echo json_encode($out);
    }

    public function get_item_wac()
    {
        if (!is_staff_logged_in()) {
            show_404();
        }

        $id = (int) $this->input->get('id');
        if ($id < 1) {
            echo json_encode(['wac' => 0]);
            return;
        }

        $row = $this->db->get_where(db_prefix() . 'items', ['id' => $id])->row();
        if (!$row) {
            echo json_encode(['wac' => 0]);
            return;
        }

        $wac = (float) ($row->purchase_price ?: $row->rate);
        echo json_encode(['wac' => $wac, 'rate' => (float) $row->rate, 'unit' => (string) $row->unit_id]);
    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('settings');
        }

        if ($this->input->post()) {
            $keys = [
                'qt_prefix',
                'qt_default_markup',
                'qt_vat_rate',
                'qt_default_validity_days',
                'qt_discount_approval_threshold',
                'qt_terms_and_conditions',
                'qt_company_name',
                'qt_company_address',
                'qt_tin',
                'qt_vat_number',
                'qt_pdf_footer',
            ];
            foreach ($keys as $key) {
                if ($this->input->post($key) !== null) {
                    $this->db->where('setting_key', $key);
                    $this->db->update(db_prefix() . 'ipms_qt_settings', [
                        'setting_value' => $this->input->post($key),
                    ]);
                }
            }
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('quotation_worksheet/settings'));
        }

        $data['title'] = _l('quotation_worksheet') . ' — ' . _l('settings');
        $this->load->view('quotation_worksheet/settings', $data);
    }

    public function submit_for_approval()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = (int) $this->input->post('proposal_id');
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $path = FCPATH . 'modules/approvals/libraries/ApprovalService.php';
        if (!is_file($path)) {
            echo json_encode(['success' => false, 'message' => 'Approvals module not available']);
            return;
        }

        require_once $path;
        $svc = new ApprovalService();

        $proposal = $this->db->get_where(db_prefix() . 'proposals', ['id' => $proposal_id])->row();
        if (!$proposal) {
            echo json_encode(['success' => false, 'message' => 'Proposal not found']);
            return;
        }

        $ref = function_exists('format_proposal_number')
            ? format_proposal_number($proposal_id)
            : ('PRO-' . str_pad((string) $proposal_id, 6, '0', STR_PAD_LEFT));

        qt_recalculate_totals($proposal_id);
        $proposal = $this->db->get_where(db_prefix() . 'proposals', ['id' => $proposal_id])->row();
        $grand      = $proposal ? (float) $proposal->total : 0;

        $rid = $svc->submit(
            'quotation',
            $proposal_id,
            $ref,
            $grand,
            (int) get_staff_user_id(),
            'Submitted from quotation worksheet.'
        );

        if ($rid) {
            $this->db->where('id', $proposal_id);
            $this->db->update(db_prefix() . 'proposals', ['qt_status' => 'pending_approval']);
            echo json_encode(['success' => true, 'approval_request_id' => $rid]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Could not queue approval']);
    }

    public function pdf($proposal_id = 0)
    {
        $proposal_id = (int) $proposal_id;
        if ($proposal_id < 1 || !qt_can_view_proposal($proposal_id)) {
            show_404();
        }

        $proposal = $this->db->get_where(db_prefix() . 'proposals', ['id' => $proposal_id])->row();
        if (!$proposal) {
            show_404();
        }

        $ws    = qt_get_or_create_worksheet($proposal_id);
        $lines = qt_get_lines($proposal_id);
        $tabs  = qt_get_tab_labels();

        ob_start();
        ?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#222;}
h1{font-size:14pt;margin:0 0 8px;}
table{border-collapse:collapse;width:100%;margin-bottom:12px;}
th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}
th{background:#f5f5f5;}
.tot{float:right;width:280px;}
.small{color:#666;font-size:9pt;}
</style></head><body>
<h1><?php echo _l('quotation_worksheet'); ?></h1>
<p class="small"><?php echo e($ws->qt_ref); ?> — <?php echo _l('proposal'); ?> #<?php echo (int) $proposal_id; ?></p>
<?php foreach ($tabs as $tabKey => $tabLabel) :
    $tabLines = array_filter($lines, function ($l) use ($tabKey) {
        return ($l['tab'] ?? '') === $tabKey;
    });
    if (empty($tabLines)) {
        continue;
    }
    ?>
<h2><?php echo e($tabLabel); ?></h2>
<table><thead><tr><th>#</th><th>Description</th><th>Qty</th><th>Unit price</th><th>Line</th></tr></thead><tbody>
<?php
    $n = 0;
    foreach ($tabLines as $l) {
        ++$n;
        ?>
<tr>
  <td><?php echo $n; ?></td>
  <td><?php echo e($l['description']); ?></td>
  <td><?php echo e(number_format((float) $l['quantity'], 2)); ?></td>
  <td><?php echo e(number_format((float) $l['sell_price'], 2)); ?></td>
  <td><?php echo e(number_format((float) $l['line_total_sell'], 2)); ?></td>
</tr>
        <?php
    }
    ?>
</tbody></table>
<?php endforeach; ?>
<div class="tot">
<table>
<tr><th colspan="2">Totals</th></tr>
<tr><td>Subtotal</td><td><?php echo e(number_format((float) $ws->total_sell, 2)); ?></td></tr>
<tr><td>VAT</td><td><?php echo e(number_format((float) $ws->vat_amount, 2)); ?></td></tr>
<tr><th>Grand</th><th><?php echo e(number_format((float) $ws->grand_total, 2)); ?></th></tr>
</table>
</div>
<p class="small"><?php echo nl2br(e($ws->terms)); ?></p>
</body></html>
        <?php
        $html = ob_get_clean();

        if (!class_exists(\Mpdf\Mpdf::class)) {
            @include_once APPPATH . 'vendor/autoload.php';
        }

        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);
        $fn = 'QT-' . preg_replace('/[^a-z0-9_-]+/i', '_', (string) $ws->qt_ref) . '.pdf';
        $dest = class_exists(\Mpdf\Output\Destination::class) ? \Mpdf\Output\Destination::INLINE : 'I';
        $mpdf->Output($fn, $dest);
    }
}
