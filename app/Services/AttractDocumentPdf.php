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
            $document_title = $isQuotation ? 'QUOTATION' : 'INVOICE';
            $blade = $isQuotation ? 'download_quotation_pdf' : 'download_pdf';

            $body = view('sale_pos.receipts.'.$blade)
                ->with(compact('receipt_details', 'location_details'))
                ->render();

            $mpdf = $this->makeMpdf($document_title);
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

    private function makeMpdf(string $document_title): \Mpdf\Mpdf
    {
        $footerPath = public_path('images/footer.png');
        if (! file_exists($footerPath)) {
            $footerPath = public_path('images/footer (1).png');
        }

        $footerImgHmm = 34;
        if (file_exists($footerPath) && ($fi = @getimagesize($footerPath)) && $fi[0] > 0) {
            $footerImgHmm = round(210 * $fi[1] / $fi[0], 2);
            $footerImgHmm = min(42, max(28, $footerImgHmm + 1));
        }

        $signZoneHmm = 32;
        $marginBottom = $footerImgHmm + $signZoneHmm;

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 0,
            'margin_bottom' => $marginBottom,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_footer' => 0,
            'format' => 'A4',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetAutoPageBreak(true, $marginBottom);

        $footerHtml = view('sale_pos.receipts.partials.attract_pdf_footer', [
            'document_title' => $document_title,
        ])->render();
        $mpdf->SetHTMLFooter($footerHtml);

        return $mpdf;
    }
}
