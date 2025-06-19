<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ApiConsultaService
{
    private $client;
    private $tokendni;
    private $tokenruc;
    private $baseUri = 'https://api.apis.net.pe';

    public function __construct()
    {
        $this->tokendni = env('API_DNI_TOKEN');
        $this->tokenruc = env('API_RUC_TOKEN');
        
        // ✨ CONFIGURACIÓN CORREGIDA DE GUZZLE
        $this->client = new Client([
            'verify' => false,
            'timeout' => 30,
            'connect_timeout' => 10,
            //'http_errors' => false
            'http_errors' => true,
        ]);
    }

    /**
     * Consultar datos de DNI desde la API externa
     */
    public function consultarDni($numeroDni)
    {
        try {
            // Validar formato de DNI peruano (8 dígitos)
            if (!$this->validarFormatoDni($numeroDni)) {
                return [
                    'success' => false,
                    'message' => 'Formato de DNI inválido. Debe tener exactamente 8 dígitos.'
                ];
            }

            // Log para debugging
            Log::info('Iniciando consulta DNI', [
                'dni' => $numeroDni,
                'token' => substr($this->tokendni, 0, 10) . '...'
            ]);

            // Configurar parámetros para la petición HTTP
            $response = $this->client->request('GET', $this->baseUri . '/v2/reniec/dni', [
                'query' => ['numero' => $numeroDni],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->tokendni,
                    'Accept' => 'application/json',
                    'Referer' => 'https://apis.net.pe/api-consulta-dni',
                    'User-Agent' => 'laravel/guzzle'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log de respuesta para debugging
            Log::info('Respuesta DNI API', [
                'status' => $statusCode,
                'response' => $responseBody
            ]);

            // Procesar respuesta exitosa
            if ($statusCode === 200 && isset($responseBody['nombres'])) {
                return [
                    'success' => true,
                    'data' => [
                        'dni' => $responseBody['numeroDocumento'] ?? $numeroDni,
                        'nombres' => $responseBody['nombres'] ?? '',
                        'apellido_paterno' => $responseBody['apellidoPaterno'] ?? '',
                        'apellido_materno' => $responseBody['apellidoMaterno'] ?? '',
                        'nombre_completo' => trim(
                            ($responseBody['nombres'] ?? '') . ' ' . 
                            ($responseBody['apellidoPaterno'] ?? '') . ' ' . 
                            ($responseBody['apellidoMaterno'] ?? '')
                        )
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'DNI no encontrado en RENIEC',
                    'error_code' => $statusCode
                ];
            }

        } catch (RequestException $e) {
            Log::error('Error en consulta DNI', [
                'dni' => $numeroDni,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con el servicio de consulta DNI: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error general en consulta DNI', [
                'dni' => $numeroDni,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✨ CONSULTAR DATOS DE RUC DESDE LA API EXTERNA (CORREGIDO)
     */
    public function consultarRuc($numeroRuc)
    {
        try {
            // Validar formato de RUC peruano (11 dígitos)
            if (!$this->validarFormatoRuc($numeroRuc)) {
                return [
                    'success' => false,
                    'message' => 'Formato de RUC inválido. Debe tener exactamente 11 dígitos y empezar con 10 o 20.'
                ];
            }

            // Log para debugging
            Log::info('Iniciando consulta RUC', [
                'ruc' => $numeroRuc,
                'token' => substr($this->tokenruc, 0, 10) . '...',
                'url' => $this->baseUri . '/v2/sunat/ruc'
            ]);

            // ✨ REALIZAR PETICIÓN CON CONFIGURACIÓN CORREGIDA
            $response = $this->client->request('GET', $this->baseUri . '/v2/sunat/ruc', [
                'query' => ['numero' => $numeroRuc],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->tokenruc,
                    'Accept' => 'application/json',
                    'Referer' => 'https://apis.net.pe/api-consulta-ruc',
                    'User-Agent' => 'laravel/guzzle'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log detallado para debugging
            Log::info('Respuesta RUC API', [
                'status' => $statusCode,
                'response' => $responseBody
            ]);

            // ✨ PROCESAR RESPUESTA EXITOSA
            if ($statusCode === 200 && isset($responseBody['razonSocial'])) {
                return [
                    'success' => true,
                    'data' => [
                        'ruc' => $responseBody['numeroDocumento'] ?? $numeroRuc,
                        'razon_social' => $responseBody['razonSocial'] ?? '',
                        'nombre_comercial' => $responseBody['nombreComercial'] ?? '',
                        'direccion' => $responseBody['direccion'] ?? '',
                        'estado' => $responseBody['estado'] ?? '',
                        'condicion' => $responseBody['condicion'] ?? '',
                        'tipo_empresa' => $responseBody['tipo'] ?? '',
                        'ubigeo' => $responseBody['ubigeo'] ?? '',
                        'distrito' => $responseBody['distrito'] ?? '',
                        'provincia' => $responseBody['provincia'] ?? '',
                        'departamento' => $responseBody['departamento'] ?? ''
                    ]
                ];
            } else {
                Log::warning('RUC no encontrado o respuesta inválida', [
                    'ruc' => $numeroRuc,
                    'status' => $statusCode,
                    'response' => $responseBody
                ]);

                return [
                    'success' => false,
                    'message' => 'RUC no encontrado en SUNAT',
                    'error_code' => $statusCode
                ];
            }

        } catch (RequestException $e) {
            Log::error('Error de conexión en consulta RUC', [
                'ruc' => $numeroRuc,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response'
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con el servicio de consulta RUC: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error general en consulta RUC', [
                'ruc' => $numeroRuc,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar formato de DNI peruano
     */
    private function validarFormatoDni($dni)
    {
        return preg_match('/^[0-9]{8}$/', $dni);
    }

    /**
     * Validar formato de RUC peruano
     */
    private function validarFormatoRuc($ruc)
    {
        return preg_match('/^(10|20)[0-9]{9}$/', $ruc);
    }
}