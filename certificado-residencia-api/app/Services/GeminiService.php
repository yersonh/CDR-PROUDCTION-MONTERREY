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
        $hoy = now()->translatedFormat('d \d\e F \d\e Y');

        // Sin la fecha de hoy explícita, el modelo no tiene forma de saber
        // qué elección es "la más reciente" ni de distinguir una fecha
        // pasada de una futura — ya nos pasó que calificó una jornada
        // anterior a hoy como "elección futura" por razonar a ciegas.
        $prompt = <<<PROMPT
            Eres un validador documental para la Alcaldía de Monterrey, Casanare, Colombia.
            La fecha de hoy es {$hoy}.

            Analiza el documento adjunto (imagen o PDF) y determina si es un CERTIFICADO
            ELECTORAL (certificado de votación / certificado de sufragante) colombiano,
            expedido por la Registraduría Nacional del Estado Civil, auténtico y vigente,
            correspondiente a las elecciones más recientes ANTERIORES a la fecha de hoy, y
            aplicable a un ciudadano del municipio de Monterrey, Casanare.

            Verifica: (1) que el documento sea legible y tenga el formato de un certificado
            electoral oficial, (2) que la fecha de la jornada electoral sea anterior a hoy y
            corresponda a la elección vigente más reciente (ni un certificado vencido de una
            elección demasiado antigua, ni una fecha posterior a hoy que no pudo haber ocurrido
            todavía), (3) que no muestre señales de edición, manipulación, o que sea en
            realidad un documento distinto (cédula, recibo, captura de pantalla no oficial, etc.).

            Responde ÚNICAMENTE con un JSON con esta forma exacta, sin texto adicional ni
            bloques de código. El campo "motivo" es una explicación breve en español (máximo
            300 caracteres) dirigida a un funcionario de la Alcaldía — no menciones el nombre
            de ningún modelo o proveedor de IA, refiérete a ti mismo simplemente como "la
            validación automática":
            {"valido": true o false, "motivo": "explicación breve en español"}
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

    /**
     * Redacta una observación breve para la validación SISBEN a partir del
     * resultado que el funcionario ya seleccionó en el formulario — le
     * ahorra escribirla a mano, no decide el resultado (eso lo elige el
     * funcionario en el combo antes de pedir la redacción).
     */
    public function redactarObservacionSisben(string $resultado, string $ciudadano, string $tipoCertificado): string
    {
        if (! $this->apiKey) {
            throw new RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $textoResultado = $resultado === 'cumple'
            ? 'SÍ cumple con la antigüedad de residencia requerida'
            : 'NO cumple con la antigüedad de residencia requerida';

        $prompt = <<<PROMPT
            Eres un asistente de redacción para un funcionario de SISBEN de la Alcaldía de
            Monterrey, Casanare, Colombia, que revisa solicitudes de certificado de residencia.

            El funcionario ya verificó la certificación de antigüedad SISBEN y determinó que el
            ciudadano "{$ciudadano}", quien solicita un "{$tipoCertificado}", {$textoResultado}
            según el Sistema de Identificación de Potenciales Beneficiarios (SISBEN).

            Redacta la observación que el funcionario dejará registrada, en español, breve
            (máximo 280 caracteres), profesional, en tercera persona, sin comillas ni markdown.
            No inventes datos específicos (fechas, direcciones, números) que no se te dieron —
            limítate a explicar el resultado de forma genérica y verificable.

            Responde ÚNICAMENTE con un JSON con esta forma exacta, sin texto adicional:
            {"observacion": "texto de la observación"}
            PROMPT;

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [['parts' => [['text' => $prompt]]]],
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

        if (! is_array($datos) || ! array_key_exists('observacion', $datos)) {
            throw new RuntimeException("Respuesta de Gemini no tiene el formato esperado: {$texto}");
        }

        return (string) $datos['observacion'];
    }
}
