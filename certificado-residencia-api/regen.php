<?php
$cert = \App\Models\Certificado::with('solicitud','firmadoPor')->latest('id')->first();
$pdf = app(\App\Services\CertificadoService::class)->renderPdf($cert);
file_put_contents('C:/Users/solan/AppData/Local/Temp/claude/C--Users-solan-Documents-Trabajo-CERTIFICADO-DE-RESIDENCIA/6ac6d371-4339-4fad-a5a0-e942733459ce/scratchpad/certificado2.pdf', $pdf);
echo 'ok '.strlen($pdf);
