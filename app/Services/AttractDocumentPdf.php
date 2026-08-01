<?php

namespace App\Services;

use App\Utils\TransactionUtil;

/**
 * Builds the branded Attract A4 PDF binary for invoice / quotation WhatsApp attach.
 */
class AttractDocumentPdf
{
    public function __construct(private TransactionUtil $transactionUtil) {}

    /**
     * @return array{binary: string, filename: string, title: string}|null
     */
    public function render(int $businessId, int $transactionId): ?array
    {
        try {
            $contents = $this->transactionUtil->getPdfContentsForGivenTransaction($businessId, $transactionId);
            $receipt_details = $contents['receipt_details'];
            $location_details = $contents['location_details'];

            $isQuotation = ! empty($receipt_details->is_quotation);
            $isProforma = ! empty($receipt_details->is_proforma) || ($receipt_details->sub_status ?? '') === 'proforma';
            if ($isProforma) {
                $document_title = 'PROFORMA INVOICE';
                $blade = 'download_proforma_pdf';
            } elseif ($isQuotation) {
                $document_title = 'QUOTATION';
                $blade = 'download_quotation_pdf';
            } else {
                $document_title = 'INVOICE';
                $blade = 'download_pdf';
            }

            $body = view('sale_pos.receipts.'.$blade)
                ->with(compact('receipt_details', 'location_details'))
                ->render();

            $mpdf = $this->makeMpdf($document_title, (string) ($receipt_details->document_brand ?? 'printworks'));
            $this->applyPaidWatermark($mpdf, $receipt_details);
            $filename = $this->buildFilenameFromReceipt(
                $receipt_details,
                $document_title,
                $transactionId
            );
            $mpdf->SetTitle($filename);
            $mpdf->WriteHTML($body);

            return [
                'binary' => $mpdf->Output('', 'S'),
                'filename' => $filename,
                'title' => $document_title,
            ];
        } catch (\Throwable $e) {
            \Log::error('AttractDocumentPdf: render failed', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Brand_TYPE_number_ClientName.pdf
     * e.g. Safetysign_QTN_655_mr_Dinuja_Dulsara.pdf
     *      Printworks_INV_825_Walk_In_Customer.pdf
     */
    public function buildFilenameFromReceipt($receipt_details, string $documentTitle, $fallbackId = null): string
    {
        $brand = strtolower(trim((string) ($receipt_details->document_brand ?? 'printworks')));
        $brandLabel = $brand === 'safetysign' ? 'Safetysign' : 'Printworks';

        $title = strtoupper(trim($documentTitle));
        if (str_contains($title, 'QUOT')) {
            $type = 'QTN';
        } elseif (str_contains($title, 'PROFORMA') || $title === 'PRO') {
            $type = 'PRO';
        } else {
            $type = 'INV';
        }

        $rawNo = trim((string) ($receipt_details->invoice_no ?? ''));
        $no = preg_replace('/^(QTN|INV|PROFORMA|PRO|PI|QUOTATION|INVOICE)[\s\-_]*/i', '', $rawNo);
        $no = preg_replace('/[^A-Za-z0-9]+/', '_', trim((string) $no));
        $no = trim((string) $no, '_');
        if ($no === '') {
            $no = (string) ($fallbackId ?: 'DOC');
        }

        $client = trim((string) (
            $receipt_details->customer_name
            ?? $receipt_details->contact_name
            ?? 'Customer'
        ));
        // Keep letters/numbers/spaces/hyphen; collapse spaces to underscores
        $client = preg_replace('/[^\p{L}\p{N}\s\-]+/u', '', $client);
        $client = preg_replace('/[\s\-]+/', '_', trim((string) $client));
        $client = trim((string) $client, '_');
        if ($client === '') {
            $client = 'Customer';
        }

        return $brandLabel.'_'.$type.'_'.$no.'_'.$client.'.pdf';
    }

    public function makeMpdf(string $document_title, string $document_brand = 'printworks'): \Mpdf\Mpdf
    {
        $document_brand = in_array($document_brand, ['printworks', 'safetysign'], true) ? $document_brand : 'printworks';
        $isSafetySign = $document_brand === 'safetysign';

        if ($isSafetySign) {
            $footerCandidates = [
                public_path('images/safety sign footer.png'),
                public_path('images/safetysign_footer.png'),
                public_path('images/safetysignfooter.png'),
            ];
            $footerPath = null;
            foreach ($footerCandidates as $candidate) {
                if (file_exists($candidate)) {
                    $footerPath = $candidate;
                    break;
                }
            }
        } else {
            $footerPath = public_path('images/footer.png');
            if (! file_exists($footerPath)) {
                $footerPath = public_path('images/footer (1).png');
            }
        }

        // Banner spans the full 210mm width, so reserve its real height at that width
        $footerImgHmm = 30;
        if ($footerPath && file_exists($footerPath) && ($fi = @getimagesize($footerPath)) && $fi[0] > 0) {
            $footerImgHmm = round(210 * $fi[1] / $fi[0], 2);
            $footerImgHmm = min(60, max(24, $footerImgHmm));
        }

        // The named HTML footer is anchored to the physical page bottom.

        $marginFooter = 0;
        // Same reserve as the browser preview ($footerReserveMm in attract_pdf_layout)
        $marginBottom = $footerImgHmm + 10;

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 10,
            'margin_bottom' => $marginBottom,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_footer' => $marginFooter,
            'format' => 'A4',
            // Arial-metric font so the PDF matches the browser preview character for character
            'default_font' => 'freesans',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetAutoPageBreak(true, $marginBottom);

        $footerHtml = view('sale_pos.receipts.partials.attract_pdf_footer', [
            'document_title' => $document_title,
            'document_brand' => $document_brand,
        ])->render();
        $mpdf->SetHTMLFooter($footerHtml);

        return $mpdf;
    }

    private function applyPaidWatermark(\Mpdf\Mpdf $mpdf, $receipt_details): void
    {
        if (! empty($receipt_details->is_quotation)) {
            return;
        }

        if (! empty($receipt_details->is_proforma) || ($receipt_details->sub_status ?? '') === 'proforma') {
            return;
        }

        $status = strtolower(trim((string) ($receipt_details->payment_status ?? '')));
        $due = $receipt_details->total_due ?? null;
        $isPaid = $status === 'paid'
            || $due === 0
            || $due === '0'
            || (is_string($due) && preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', $due));

        if (! $isPaid) {
            return;
        }

        $mpdf->SetWatermarkText('PAID', 0.15);
        $mpdf->watermark_font = 'FreeSans';
        $mpdf->showWatermarkText = true;
        $mpdf->watermarkTextAlpha = 0.15;
    }
}
