<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Branded IPMS quotation PDF (MW) using mPDF.
 */
class Quotation_pdf
{
    private const COLOR_NAVY   = '#0D1B2A';

    private const COLOR_ACCENT = '#1E6FBF';

    /** @var CI_Controller */
    protected $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * @param string $output mPDF destination: D download, I inline, S string, F file
     *
     * @return string|void Returns PDF string when $output is S
     */
    public function generate($quotation_id, $output = 'D')
    {
        $quotation_id = (int) $quotation_id;
        $output       = (string) $output;
        if (!in_array($output, ['D', 'I', 'S', 'F'], true)) {
            $output = 'D';
        }

        $data = $this->loadPdfData($quotation_id);
        if ($data === null) {
            show_404();
        }

        $filePath = $output === 'F' ? $this->absolute_pdf_storage_path($data['quotation']) : null;
        if ($output === 'F') {
            $this->ensureDirectory(dirname((string) $filePath));
        }

        $mpdf = $this->newMpdf();
        $this->applyHeaderFooter($mpdf, $data);

        $mpdf->WriteHTML($this->buildCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($this->buildBodyHtml($data), \Mpdf\HTMLParserMode::HTML_BODY);

        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $data['quotation']->quotation_ref . '_v' . (int) $data['quotation']->version) . '.pdf';

        if ($output === 'S') {
            $bin = $mpdf->Output('', 'S');
            $this->markLastPdfGenerated($quotation_id);

            return $bin;
        }

        $mpdf->Output($output === 'F' ? (string) $filePath : $downloadName, $output);
        $this->markLastPdfGenerated($quotation_id);
    }

    /**
     * Save under uploads/quotations/{ref}_v{version}.pdf and return the full path.
     *
     * @return string|false
     */
    public function get_pdf_path($quotation_id)
    {
        $quotation_id = (int) $quotation_id;
        $data         = $this->loadPdfData($quotation_id);
        if ($data === null) {
            return false;
        }

        $path = $this->absolute_pdf_storage_path($data['quotation']);
        $this->ensureDirectory(dirname($path));

        $mpdf = $this->newMpdf();
        $this->applyHeaderFooter($mpdf, $data);
        $mpdf->WriteHTML($this->buildCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($this->buildBodyHtml($data), \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output($path, 'F');
        $this->markLastPdfGenerated($quotation_id);

        return $path;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadPdfData($quotation_id)
    {
        $this->ci->load->model('quotations/quotations_model');
        $this->ci->load->model('estimates_model');
        $this->ci->load->helper('quotations');
        $this->ci->load->helper('sales');

        $summary = $this->ci->quotations_model->get_quotation_summary_for_pdf($quotation_id);
        if (empty($summary['quotation'])) {
            return null;
        }

        /** @var object $q */
        $q = $summary['quotation'];
        $estimate = $this->ci->estimates_model->get((int) $q->estimate_id);
        if (!$estimate) {
            return null;
        }

        $settings = $this->ci->quotations_model->get_all_settings();
        $totals   = qt_recalculate_totals($quotation_id);

        return [
            'quotation' => $q,
            'client'    => $summary['client'] ?? null,
            'estimate'  => $estimate,
            'settings'  => $settings,
            'totals'    => $totals,
        ];
    }

    protected function newMpdf(): \Mpdf\Mpdf
    {
        $this->ensureMpdfLoaded();

        return new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 36,
            'margin_bottom' => 24,
            'margin_header' => 4,
            'margin_footer' => 6,
            'default_font'  => 'dejavusans',
        ]);
    }

    protected function ensureMpdfLoaded(): void
    {
        if (class_exists(\Mpdf\Mpdf::class, false)) {
            return;
        }
        $paths = [
            APPPATH . 'third_party/mPDF/vendor/autoload.php',
            APPPATH . 'vendor/autoload.php',
        ];
        foreach ($paths as $p) {
            if (is_file($p)) {
                require_once $p;
                break;
            }
        }
        if (!class_exists(\Mpdf\Mpdf::class, false)) {
            throw new RuntimeException('mPDF is not installed. Add mpdf/mpdf to application/composer.json and run composer update.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function applyHeaderFooter(\Mpdf\Mpdf $mpdf, array $data): void
    {
        $q        = $data['quotation'];
        $settings = $data['settings'];

        $coName = isset($settings['qt_company_name']) && (string) $settings['qt_company_name'] !== ''
            ? (string) $settings['qt_company_name']
            : (string) get_option('companyname');
        $coAddr = isset($settings['qt_company_address']) ? (string) $settings['qt_company_address'] : '';
        $coVat  = isset($settings['qt_vat_number']) ? (string) $settings['qt_vat_number'] : '';

        $logoPath = FCPATH . 'uploads/quotation_logo.png';
        $logoHtml = '';
        if (is_file($logoPath)) {
            $src      = str_replace('\\', '/', $logoPath);
            $logoHtml = '<img class="qt-logo" src="' . html_escape($src) . '" alt="" />';
        }

        $header = '<table class="qt-hdr-table" width="100%"><tr>
<td class="qt-hdr-left" style="width:58%;vertical-align:top;">
<div class="qt-co-name">' . html_escape($coName) . '</div>
<div class="qt-co-meta">' . nl2br(html_escape($coAddr)) . '</div>';
        if ($coVat !== '') {
            $header .= '<div class="qt-co-meta">VAT: ' . html_escape($coVat) . '</div>';
        }
        $header .= '</td>
<td class="qt-hdr-right" style="width:42%;text-align:right;vertical-align:top;">' . $logoHtml . '</td>
</tr></table><div class="qt-hr"></div>';

        $footerText = isset($settings['qt_pdf_footer_text']) ? (string) $settings['qt_pdf_footer_text'] : '';
        $refVer     = html_escape($q->quotation_ref) . ' v' . (int) $q->version;
        $footer     = '<table class="qt-ft-table" width="100%"><tr>
<td style="width:34%;font-size:8pt;vertical-align:top;">' . html_escape($footerText) . '</td>
<td style="width:32%;text-align:center;font-size:8pt;vertical-align:top;">Page {PAGENO} of {nbpg}</td>
<td style="width:34%;text-align:right;font-size:8pt;vertical-align:top;">' . $refVer . '</td>
</tr></table>';

        $mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLFooter($footer);
    }

    protected function buildCss(): string
    {
        $navy   = self::COLOR_NAVY;
        $accent = self::COLOR_ACCENT;

        return <<<CSS
body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #222; }
.qt-hdr-table { width: 100%; border-collapse: collapse; }
.qt-co-name { font-size: 16pt; font-weight: bold; color: {$navy}; }
.qt-co-meta { font-size: 9pt; margin-top: 2px; color: #333; }
.qt-hr { border-bottom: 1px solid {$navy}; margin-top: 4px; margin-bottom: 2px; }
.qt-logo { max-height: 22mm; max-width: 50mm; }
.qt-title { font-size: 22pt; font-weight: bold; text-transform: uppercase; color: {$navy}; margin: 8px 0 4px 0; }
.qt-meta-row td { font-size: 9pt; padding: 2px 8px 2px 0; vertical-align: top; }
.qt-meta-label { color: #555; }
.qt-client-box { background: #e9ecef; padding: 8px 10px; margin: 10px 0; border-radius: 2px; }
.qt-client-box .label { font-weight: bold; color: {$navy}; margin-bottom: 4px; }
.qt-scope { margin: 10px 0; }
.qt-scope ul { margin: 4px 0 0 16px; padding: 0; }
.qt-section-title { background: {$navy}; color: #fff; font-weight: bold; font-size: 11pt; padding: 5px 6px; margin-top: 10px; }
.qt-items { width: 100%; border-collapse: collapse; margin-top: 2px; }
.qt-items th { background: {$navy}; color: #fff; font-size: 9pt; padding: 4px 4px; text-align: left; }
.qt-items td { padding: 4px 4px; border-bottom: 1px solid #dee2e6; vertical-align: top; }
.qt-items tr.alt td { background: #f8f9fa; }
.qt-num, .qt-qty, .qt-money { text-align: right; }
.qt-subrow td { font-weight: bold; background: #eef2f7; text-align: right; }
.qt-totals { width: 100%; margin-top: 12px; }
.qt-totals-inner { width: 52%; margin-left: auto; border-collapse: collapse; font-size: 9pt; }
.qt-totals-inner td { padding: 3px 0; }
.qt-totals-inner .lbl { text-align: right; padding-right: 10px; color: #333; }
.qt-totals-inner .amt { text-align: right; white-space: nowrap; }
.qt-totals-inner .disc { color: #c0392b; }
.qt-totals-inner .grand td { border-top: 2px solid {$navy}; font-weight: bold; font-size: 10pt; padding-top: 6px; }
.qt-paybox { border: 1px solid #ced4da; padding: 8px 10px; margin-top: 12px; background: #fafbfc; font-size: 9pt; }
.qt-paybox .ph { font-weight: bold; color: {$navy}; margin-bottom: 4px; }
.qt-terms { margin-top: 12px; font-size: 8pt; color: #333; }
.qt-terms.cols { column-count: 2; column-gap: 8mm; }
.qt-sig { width: 100%; margin-top: 16px; border-collapse: collapse; }
.qt-sig td { width: 50%; vertical-align: top; font-size: 9pt; padding: 4px 8px 4px 0; }
.qt-sig .sh { font-weight: bold; color: {$navy}; margin-bottom: 6px; }
.link-accent { color: {$accent}; }
CSS;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function buildBodyHtml(array $data): string
    {
        $q         = $data['quotation'];
        $estimate  = $data['estimate'];
        $totals    = $data['totals'];
        $settings  = $data['settings'];
        $tabLabels = qt_tab_labels();

        $quoteDate = !empty($estimate->date) ? $estimate->date : date('Y-m-d', strtotime($q->created_at));
        $validUntil = !empty($estimate->expirydate)
            ? $estimate->expirydate
            : date('Y-m-d', strtotime($q->created_at . ' +' . (int) $q->validity_days . ' days'));

        $titleBlock = '<div class="qt-title">QUOTATION</div>';
        $titleBlock .= '<table class="qt-meta-row" width="100%"><tr>
<td><span class="qt-meta-label">Reference:</span> ' . html_escape($q->quotation_ref) . '</td>
<td><span class="qt-meta-label">Version:</span> v' . (int) $q->version . '</td>
</tr><tr>
<td><span class="qt-meta-label">Date:</span> ' . html_escape(_d($quoteDate)) . '</td>
<td><span class="qt-meta-label">Valid Until:</span> ' . html_escape(_d($validUntil)) . '</td>
</tr></table>';

        $clientBox = $this->buildClientBox($estimate, $q);

        $linesByTab = isset($q->lines_by_tab) && is_array($q->lines_by_tab) ? $q->lines_by_tab : [];
        $scopeTabs  = [];
        foreach ($tabLabels as $key => $label) {
            if (!empty($linesByTab[$key])) {
                $scopeTabs[] = $label;
            }
        }
        $scopeHtml = '<div class="qt-scope"><div>This quotation covers the following scope of work:</div>';
        if ($scopeTabs !== []) {
            $scopeHtml .= '<ul>';
            foreach ($scopeTabs as $lbl) {
                $scopeHtml .= '<li>' . html_escape($lbl) . '</li>';
            }
            $scopeHtml .= '</ul>';
        } else {
            $scopeHtml .= '<p><em>No line items.</em></p>';
        }
        $scopeHtml .= '</div>';

        $sectionsHtml = '';
        $secNum       = 0;
        foreach ($tabLabels as $tabKey => $tabTitle) {
            $rows = isset($linesByTab[$tabKey]) ? $linesByTab[$tabKey] : [];
            if ($rows === []) {
                continue;
            }
            $secNum++;
            $sectionsHtml .= '<div class="qt-section-title">' . (int) $secNum . '. ' . strtoupper(html_escape($tabTitle)) . '</div>';
            $sectionsHtml .= '<table class="qt-items"><thead><tr>
<th style="width:6%;">No</th>
<th style="width:40%;">Description</th>
<th style="width:10%;">Unit</th>
<th style="width:10%;" class="qt-qty">Qty</th>
<th style="width:17%;" class="qt-money">Unit Price (MWK)</th>
<th style="width:17%;" class="qt-money">Amount (MWK)</th>
</tr></thead><tbody>';

            $sub = 0.0;
            $n   = 0;
            foreach ($rows as $ln) {
                $n++;
                $alt    = ($n % 2) === 0 ? ' class="alt"' : '';
                $desc   = isset($ln['description']) ? (string) $ln['description'] : '';
                $unit   = isset($ln['unit']) ? (string) $ln['unit'] : '';
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0.0;
                $sell   = isset($ln['sell_price']) ? (float) $ln['sell_price'] : 0.0;
                $amt    = isset($ln['line_total_sell']) ? (float) $ln['line_total_sell'] : 0.0;
                $sub   += $amt;

                $sectionsHtml .= '<tr' . $alt . '><td class="qt-num">' . $n . '</td>';
                $sectionsHtml .= '<td>' . nl2br(html_escape($desc)) . '</td>';
                $sectionsHtml .= '<td>' . html_escape($unit) . '</td>';
                $sectionsHtml .= '<td class="qt-qty">' . html_escape($this->formatQty($qty)) . '</td>';
                $sectionsHtml .= '<td class="qt-money">' . html_escape(qt_format_mwk($sell)) . '</td>';
                $sectionsHtml .= '<td class="qt-money">' . html_escape(qt_format_mwk($amt)) . '</td></tr>';
            }
            $sectionsHtml .= '<tr class="qt-subrow"><td colspan="5">Section subtotal</td>';
            $sectionsHtml .= '<td class="qt-money">' . html_escape(qt_format_mwk($sub)) . '</td></tr>';
            $sectionsHtml .= '</tbody></table>';
        }

        $subtotalLines = (float) ($totals['subtotal_sell'] ?? 0);
        $subAfterCont  = (float) ($totals['sub_after_contingency'] ?? 0);
        $contingencyAmt = max(0.0, $subAfterCont - $subtotalLines);
        $contPct       = isset($q->contingency_percent) ? (float) $q->contingency_percent : 0.0;
        $discApplied   = (float) ($totals['discount_applied'] ?? 0);
        $subFinal      = (float) ($totals['sub_final'] ?? 0);
        $vatAmt        = (float) ($totals['vat_amount'] ?? 0);
        $grandTotal    = (float) ($totals['grand_total'] ?? 0);
        $vatRate       = qt_get_vat_rate();

        $totalsHtml = '<table class="qt-totals"><tr><td><table class="qt-totals-inner">';
        $totalsHtml .= '<tr><td class="lbl">Subtotal:</td><td class="amt">' . html_escape(qt_format_mwk($subtotalLines)) . '</td></tr>';

        if ($contPct > 0 && $contingencyAmt > 0.00001) {
            $totalsHtml .= '<tr><td class="lbl">Contingency (' . html_escape($this->formatPercentLabel($contPct)) . '%):</td><td class="amt">' . html_escape(qt_format_mwk($contingencyAmt)) . '</td></tr>';
        }

        if ($discApplied > 0.00001) {
            if (isset($q->discount_amount) && (float) $q->discount_amount > 0) {
                $discLabel = 'Discount:';
            } elseif (isset($q->discount_percent) && (float) $q->discount_percent > 0) {
                $discLabel = 'Discount (' . html_escape($this->formatPercentLabel((float) $q->discount_percent)) . '%):';
            } else {
                $discLabel = 'Discount:';
            }
            $totalsHtml .= '<tr><td class="lbl">' . $discLabel . '</td><td class="amt disc">(' . html_escape(qt_format_mwk($discApplied)) . ')</td></tr>';
        }

        $totalsHtml .= '<tr><td class="lbl">Net Amount:</td><td class="amt">' . html_escape(qt_format_mwk($subFinal)) . '</td></tr>';
        $totalsHtml .= '<tr><td class="lbl">VAT (' . html_escape($this->formatPercentLabel($vatRate)) . '%):</td><td class="amt">' . html_escape(qt_format_mwk($vatAmt)) . '</td></tr>';
        $totalsHtml .= '<tr class="grand"><td class="lbl">TOTAL:</td><td class="amt">' . html_escape(qt_format_mwk($grandTotal)) . '</td></tr>';
        $totalsHtml .= '</table></td></tr></table>';

        $payBox = '<div class="qt-paybox"><div class="ph">Payment terms</div>
<div>50% deposit required upon order confirmation.</div>
<div>Balance payable upon completion/delivery.</div>
<div>Payment methods: Cash, Bank Transfer (EFT), Mobile Money</div></div>';

        $termsRaw = isset($settings['qt_terms_and_conditions']) ? (string) $settings['qt_terms_and_conditions'] : '';
        $termsRaw = trim($termsRaw);
        $termsCls = strlen($termsRaw) > 700 ? 'qt-terms cols' : 'qt-terms';
        $termsHtml = '<div class="qt-section-title" style="margin-top:12px;">Terms and conditions</div>';
        $termsHtml .= '<div class="' . $termsCls . '" style="margin-top:6px;">' . nl2br(html_escape($termsRaw)) . '</div>';

        $preparer = get_staff_full_name((int) $q->created_by);
        $coShort  = isset($settings['qt_company_name']) && (string) $settings['qt_company_name'] !== ''
            ? (string) $settings['qt_company_name']
            : (string) get_option('companyname');

        $sigDate = html_escape(_d($quoteDate));
        $sig     = '<table class="qt-sig"><tr><td><div class="sh">Accepted by (Client):</div>
<div>Name: ________________________________</div>
<div style="margin-top:4px;">Signature: _____________________________</div>
<div style="margin-top:4px;">Date: __________________________________</div>
<div style="margin-top:4px;">Company stamp: _________________________</div>
</td><td><div class="sh">Prepared by:</div>
<div>Name: ' . html_escape($preparer) . '</div>
<div style="margin-top:4px;">Signature: _____________________________</div>
<div style="margin-top:4px;">Date: ' . $sigDate . '</div>
<div style="margin-top:4px;">' . html_escape($coShort) . '</div>
</td></tr></table>';

        return $titleBlock . $clientBox . $scopeHtml . $sectionsHtml . $totalsHtml . $payBox . $termsHtml . $sig;
    }

    protected function buildClientBox(object $estimate, object $q): string
    {
        $this->ci->load->helper('sales');
        $billing = '<div class="label">Quote prepared for:</div>';
        $billing .= '<div>' . format_customer_info($estimate, 'estimate', 'billing') . '</div>';

        $tin = '';
        if (isset($estimate->client->vat) && (string) $estimate->client->vat !== '') {
            $tin = '<div style="margin-top:4px;">TIN / VAT: ' . html_escape((string) $estimate->client->vat) . '</div>';
        }

        $attn = '';
        if (!empty($estimate->acceptance_firstname) || !empty($estimate->acceptance_lastname)) {
            $attn = trim((string) $estimate->acceptance_firstname . ' ' . (string) $estimate->acceptance_lastname);
        }
        if ($attn === '') {
            $pid = get_primary_contact_user_id((int) $q->client_id);
            if ($pid) {
                $attn = get_contact_full_name($pid);
            }
        }
        $attnHtml = $attn !== '' ? '<div style="margin-top:6px;"><strong>Attention:</strong> ' . html_escape($attn) . '</div>' : '';

        return '<div class="qt-client-box">' . $billing . $tin . $attnHtml . '</div>';
    }

    protected function formatQty(float $qty): string
    {
        $dec = ((float) (int) $qty === $qty) ? 0 : 3;

        return rtrim(rtrim(number_format($qty, $dec, '.', ','), '0'), '.') ?: '0';
    }

    protected function formatPercentLabel(float $v): string
    {
        $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        return $s === '' ? '0' : $s;
    }

    protected function absolute_pdf_storage_path(object $q): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $q->quotation_ref);

        return rtrim(FCPATH, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'quotations' . DIRECTORY_SEPARATOR . $safe . '_v' . (int) $q->version . '.pdf';
    }

    protected function ensureDirectory(string $dir): void
    {
        if ($dir !== '' && !is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    protected function markLastPdfGenerated(int $quotation_id): void
    {
        $t = db_prefix() . 'ipms_quotations';
        if (!$this->ci->db->table_exists($t) || !$this->ci->db->field_exists('last_pdf_generated', $t)) {
            return;
        }
        $this->ci->db->where('id', $quotation_id);
        $this->ci->db->update($t, ['last_pdf_generated' => date('Y-m-d H:i:s')]);
    }
}
