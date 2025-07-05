<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ApiResendService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.resend.com/emails';
    protected $fromEmail = 'onboarding@resend.dev';
    
    public function __construct()
    {
        $this->apiKey = env('RESEND_API_KEY');
        
        if (empty($this->apiKey)) {
            Log::error('Resend API key not found in .env file');
            throw new \Exception('Resend API key not configured');
        }
    }
    
    /**
     * Envía un correo electrónico usando la API de Resend
     * 
     * @param string $to Dirección de correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $htmlContent Contenido HTML del correo
     * @param string $fromEmail Correo del remitente (opcional)
     * @return array Respuesta de la API
     */
    public function sendEmail($to, $subject, $htmlContent, $fromEmail = null)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, [
                'from' => $fromEmail ?? $this->fromEmail,
                'to' => $to,
                'subject' => $subject,
                'html' => $htmlContent
            ]);
            
            $result = $response->json();
            
            if ($response->successful()) {
                Log::info('Email sent successfully via Resend API', ['to' => $to, 'subject' => $subject]);
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'data' => $result
                ];
            } else {
                Log::error('Failed to send email via Resend API', [
                    'error' => $result,
                    'status' => $response->status()
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to send email',
                    'error' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception when sending email via Resend API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Exception when sending email',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía un correo electrónico con un archivo adjunto usando la API de Resend
     * 
     * @param string $to Dirección de correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $htmlContent Contenido HTML del correo
     * @param string $attachmentContent Contenido del archivo adjunto en formato binario
     * @param string $attachmentName Nombre del archivo adjunto
     * @param string $fromEmail Correo del remitente (opcional)
     * @return array Respuesta de la API
     */
    public function sendEmailWithAttachment($to, $subject, $htmlContent, $attachmentContent, $attachmentName, $fromEmail = null)
    {
        try {
            // Codificar el contenido del archivo en base64
            $base64Content = base64_encode($attachmentContent);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, [
                'from' => $fromEmail ?? $this->fromEmail,
                'to' => $to,
                'subject' => $subject,
                'html' => $htmlContent,
                'attachments' => [
                    [
                        'content' => $base64Content,
                        'filename' => $attachmentName
                    ]
                ]
            ]);
            
            $result = $response->json();
            
            if ($response->successful()) {
                Log::info('Email with attachment sent successfully via Resend API', ['to' => $to, 'subject' => $subject, 'attachment' => $attachmentName]);
                return [
                    'success' => true,
                    'message' => 'Email with attachment sent successfully',
                    'data' => $result
                ];
            } else {
                Log::error('Failed to send email with attachment via Resend API', [
                    'error' => $result,
                    'status' => $response->status()
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to send email with attachment',
                    'error' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception when sending email with attachment via Resend API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Exception when sending email with attachment',
                'error' => $e->getMessage()
            ];
        }
    }
}