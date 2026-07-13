<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de la API de Gemini (Google) usado para validar documentalmente
 * el certificado electoral adjunto a una solicitud, ver
 * ValidarCertificadoElectoralConIA.
 */
class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model');
    }

    /**
     * Le pide a Gemini que revise un certificado electoral (imagen o PDF) y
     * determine si es un certificado de votación colombiano auténtico y
     * vigente (últimas elecciones), aplicable a un ciudadano de Monterrey,
     * Casanare.
     *
     * @return array{valido: bool, motivo: string}
     */
    public function validarCertificadoElectoral(string $rutaAbsoluta, string $mime): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $contenido = base64_encode(file_get_contents($rutaAbsoluta));

        $prompt = <<<'PROMPT'
            Eres un validador documental para la Alcaldía de Monterrey, Casanare, Colombia.
            Analiza el documento adjunto (imagen o PDF) y determina si es un CERTIFICADO
            ELECTORAL (certificado de votación / certificado de sufragante) colombiano,
            expedido por la Registraduría Nacional del Estado Civil, auténtico y vigente,
            correspondiente a las elecciones más recientes, y aplicable a un ciudadano del
            municipio de Monterrey, Casanare.

            Verifica: (1) que el documento sea legible y tenga el formato de un certificado
            electoral oficial, (2) que la fecha de la jornada electoral corresponda a las
            elecciones vigentes más recientes (no un certificado vencido de una elección
            antigua), (3) que no muestre señales de edición, manipulación, o que sea en
            realidad un documento distinto (cédula, recibo, captura de pantalla no oficial, etc.).

            Responde ÚNICAMENTE con un JSON con esta forma exacta, sin texto adicional ni
            bloques de código:
            {"valido": true o false, "motivo": "explicación breve en español, máximo 300 caracteres"}
            PROMPT;

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => $mime, 'data' => $contenido]],
                    ],
                ]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ],
        );

        if ($response->failed()) {
            throw new RuntimeException("Gemini respondió HTTP {$response->status()}: {$response->body()}");
        }

        $texto = $response->json('candidates.0.content.parts.0.text');

        if (! $texto) {
            throw new RuntimeException('Gemini no devolvió contenido analizable: '.$response->body());
        }

        $datos = json_decode((string) $texto, true);

        if (! is_array($datos) || ! array_key_exists('valido', $datos)) {
            throw new RuntimeException("Respuesta de Gemini no tiene el formato esperado: {$texto}");
        }

        return [
            'valido' => (bool) $datos['valido'],
            'motivo' => (string) ($datos['motivo'] ?? ''),
        ];
    }
}
