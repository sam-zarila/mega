<?php

defined('BASEPATH') or exit('No direct script access allowed');
$pdf_logo_url = pdf_logo_url();

$proposal_info = '<div class="pr-org-info">' . format_organization_info() . '</div>';
$client_details = '<div class="pr-bill-label">' . _l('proposal_to') . '</div>';
$client_details .= '<div class="pr-bill-body">' . format_proposal_info($proposal, 'pdf') . '</div>';

$proposal_date = _l('proposal_date') . ': ' . _d($proposal->date);
$open_till     = '';
$open_till_meta = '';
$project_meta   = '';

if (! empty($proposal->open_till)) {
    $open_till = _l('proposal_open_till') . ': ' . _d($proposal->open_till) . '<br />';
    $open_till_meta = _l('proposal_open_till') . ': ' . _d($proposal->open_till);
}

$project = '';
if ($proposal->project_id != '' && get_option('show_project_on_proposal') == 1) {
    $project .= _l('project') . ': ' . get_project_name_by_id($proposal->project_id) . '<br />';
    $project_meta = _l('project') . ': ' . get_project_name_by_id($proposal->project_id);
}

$qty_heading = _l('estimate_table_quantity_heading', '', false);

if ($proposal->show_quantity_as == 2) {
    $qty_heading = _l($this->type . '_table_hours_heading', '', false);
} elseif ($proposal->show_quantity_as == 3) {
    $qty_heading = _l('estimate_table_quantity_heading', '', false) . '/' . _l('estimate_table_hours_heading', '', false);
}

// The items table
$items = get_items_table_data($proposal, 'proposal', 'pdf')
    ->set_headings('estimate');

$items_html = $items->table();

$items_html .= '<br /><br />';
$items_html .= '';
$items_html .= '<table cellpadding="6" class="pr-totals-table">';

$items_html .= '
<tr>
    <td align="right" width="72%"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td align="right" width="28%">' . app_format_money($proposal->subtotal, $proposal->currency_name) . '</td>
</tr>';

if (is_sale_discount_applied($proposal)) {
    $items_html .= '
    <tr>
        <td align="right" width="72%"><strong>' . _l('estimate_discount');
    if (is_sale_discount($proposal, 'percent')) {
        $items_html .= ' (' . app_format_number($proposal->discount_percent, true) . '%)';
    }
    $items_html .= '</strong>';
    $items_html .= '</td>';
    $items_html .= '<td align="right" width="28%">-' . app_format_money($proposal->discount_total, $proposal->currency_name) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $items_html .= '<tr>
    <td align="right" width="72%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="28%">' . app_format_money($tax['total_tax'], $proposal->currency_name) . '</td>
</tr>';
}

if ((int) $proposal->adjustment != 0) {
    $items_html .= '<tr>
    <td align="right" width="72%"><strong>' . _l('estimate_adjustment') . '</strong></td>
    <td align="right" width="28%">' . app_format_money($proposal->adjustment, $proposal->currency_name) . '</td>
</tr>';
}
$items_html .= '
<tr class="pr-grand-total">
    <td align="right" width="72%"><strong>' . _l('estimate_total') . '</strong></td>
    <td align="right" width="28%">' . app_format_money($proposal->total, $proposal->currency_name) . '</td>
</tr>';
$items_html .= '</table>';

if (get_option('total_to_words_enabled') == 1) {
    $items_html .= '<br /><br /><br />';
    $items_html .= '<strong style="text-align:center;">' . _l('num_word') . ': ' . $CI->numberword->convert($proposal->total, $proposal->currency_name) . '</strong>';
}

$proposal->content = str_replace('{proposal_items}', $items_html, $proposal->content);

// Get the proposals css
// Theese lines should aways at the end of the document left side. Dont indent these lines
$html = <<<EOF
<style>
.proposal-sheet{width:675px;color:#1a1a1a;font-family:Helvetica,Arial,sans-serif;font-size:11px;}
.proposal-header-table,.proposal-meta-table{width:100%;border-collapse:collapse;}
.proposal-header-table td,.proposal-meta-table td{vertical-align:top;}
.pr-logo-wrap img{max-width:170px;max-height:58px;height:auto;}
.pr-org-info{margin-top:8px;line-height:1.35;color:#333;}
.pr-title{font-size:44px;line-height:0.95;font-weight:700;letter-spacing:-0.02em;text-align:center;}
.pr-meta-line{margin-bottom:3px;}
.pr-meta-label{display:inline-block;width:88px;color:#666;}
.pr-meta-value{font-weight:600;}
.pr-bill-label{margin-bottom:2px;}
.pr-bill-body{color:#222;line-height:1.45;}
.pr-body-wrap table{width:100%;border-collapse:collapse;}
.pr-body-wrap th{background:#2e67ad;color:#fff;border:1px solid #2a5c9a;padding:6px 5px;font-size:10px;line-height:1.15;}
.pr-body-wrap td{border:1px solid #d8dce2;padding:7px 5px;background:#fff;}
.pr-body-wrap tr:nth-child(even) td{background:#f4f6fa;}
.pr-totals-table{margin-top:8px;font-size:11px;}
.pr-totals-table td{border:1px solid #d8dce2;padding:5px 6px;background:#f7f8fa;}
.pr-totals-table tr.pr-grand-total td{background:#e6f0ff;font-weight:700;}
.pr-subject{font-size:14px;font-weight:600;margin:8px 0 12px;}
.pr-terms{text-align:center;font-size:10px;font-weight:700;margin-top:10px;}
</style>
<div class="proposal-sheet">
  <table class="proposal-header-table">
    <tr>
      <td width="54%">
        <div class="pr-logo-wrap">{$pdf_logo_url}</div>
        {$proposal_info}
      </td>
      <td width="46%" style="vertical-align:middle;">
        <div class="pr-title">Proposal</div>
      </td>
    </tr>
  </table>
  <div class="pr-subject"># {$number}<br />{$proposal->subject}</div>
  <table class="proposal-meta-table">
    <tr>
      <td width="54%">
        {$client_details}
      </td>
      <td width="46%">
        <div class="pr-meta-line">{$proposal_date}</div>
        <div class="pr-meta-line">{$open_till_meta}</div>
        <div class="pr-meta-line">{$project_meta}</div>
      </td>
    </tr>
  </table>
  <div class="pr-body-wrap">
    {$proposal->content}
  </div>
  <div class="pr-terms">Terms and conditions apply</div>
</div>
EOF;

$pdf->writeHTML($html, true, false, true, false, '');
