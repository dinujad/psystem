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
            if ($isQuotation) {
                $document_title = 'QUOTATION';
                $blade = 'download_quotation_pdf';
            } elseif ($isProforma) {
                $document_title = 'PROFORMA INVOICE';
                $blade = 'download_proforma_pdf';
            } else {
                $document_title = 'INVOICE';
                $blade = 'download_pdf';
            }

            $body = view('sale_pos.receipts.'.$blade)
                ->with(compact('receipt_details', 'location_details'))
                ->render();

            $mpdf = $this->makeMpdf($document_title);
            $this->applyPaidWatermark($mpdf, $receipt_details);
            $filename = $document_title.'-'.($receipt_details->invoice_no ?? $transactionId).'.pdf';
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

    public function makeMpdf(string $document_title): \Mpdf\Mpdf
    {
        $footerPath = public_path('images/footer.png');
        if (! file_exists($footerPath)) {
            $footerPath = public_path('images/footer (1).png');
        }

        $footerImgHmm = 30;
        if (file_exists($footerPath) && ($fi = @getimagesize($footerPath)) && $fi[0] > 0) {
            $footerImgHmm = round(210 * $fi[1] / $fi[0], 2);
            $footerImgHmm = min(38, max(24, $footerImgHmm));
        }

        $marginBottom = $footerImgHmm + 16;

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
            'margin_footer' => 0,
            'format' => 'A4',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetAutoPageBreak(true, $marginBottom);
        $mpdf->setAutoBottomMargin = 'stretch';

        $footerHtml = view('sale_pos.receipts.partials.attract_pdf_footer', [
            'document_title' => $document_title,
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
        $mpdf->watermark_font = 'DejaVuSansCondensed';
        $mpdf->showWatermarkText = true;
        $mpdf->watermarkTextAlpha = 0.15;
    }
}
