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
    private $baseUriDni = 'https://api.factiliza.com/v1';
    private $baseUriRuc = 'https://api.factiliza.com/v1';

    public function __construct()
    {
        // VERIFICAR QUE LAS VARIABLES EXISTAN EN EL .env
        $this->tokendni = config('services.factiliza.dni_token');
        $this->tokenruc = env('API_RUC_TOKEN');
        
        // LOG PARA DEBUGGING (REMOVER EN PRODUCCIÓN)
        Log::info('ApiConsultaService initialized', [
            'dni_token_exists' => !empty($this->tokendni),
            'ruc_token_exists' => !empty($this->tokenruc),
            'dni_token_preview' => $this->tokendni ? substr($this->tokendni, 0, 10) . '...' : 'NULL',
            'ruc_token_preview' => $this->tokenruc ? substr($this->tokenruc, 0, 10) . '...' : 'NULL'
        ]);
        
        $this->client = new Client([
            'verify' => false,
            'timeout' => 15,
            'connect_timeout' => 5,
            'http_errors' => true,
        ]);
    }

    /**
     * Consultar datos de DNI desde la API de Factiliza (YA IMPLEMENTADO)
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
            Log::info('Iniciando consulta DNI con Factiliza', [
                'dni' => $numeroDni,
                'token' => substr($this->tokendni, 0, 10) . '...',
                'url' => $this->baseUriDni . '/dni/info/' . $numeroDni
            ]);

            // CONFIGURACIÓN PARA FACTILIZA DNI
            $response = $this->client->request('GET', $this->baseUriDni . '/dni/info/' . $numeroDni, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->tokendni,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel-Karaoke-App/1.0'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log de respuesta para debugging
            Log::info('Respuesta DNI Factiliza API', [
                'status' => $statusCode,
                'response' => $responseBody
            ]);

            // PROCESAR RESPUESTA DE FACTILIZA
            if ($statusCode === 200 && isset($responseBody['success']) && $responseBody['success'] === true) {
                $data = $responseBody['data'] ?? $responseBody;
                
                // Extraer datos según la estructura de Factiliza
                $nombres = $data['nombres'] ?? $data['name'] ?? '';
                $apellidoPaterno = $data['apellido_paterno'] ?? $data['first_surname'] ?? '';
                $apellidoMaterno = $data['apellido_materno'] ?? $data['second_surname'] ?? '';
                
                return [
                    'success' => true,
                    'data' => [
                        'dni' => $data['dni'] ?? $data['document_number'] ?? $numeroDni,
                        'nombres' => $nombres,
                        'apellido_paterno' => $apellidoPaterno,
                        'apellido_materno' => $apellidoMaterno,
                        'nombre_completo' => trim($nombres . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno)
                    ]
                ];
            } elseif ($statusCode === 200 && isset($responseBody['nombres'])) {
                // Estructura alternativa de respuesta
                return [
                    'success' => true,
                    'data' => [
                        'dni' => $responseBody['dni'] ?? $numeroDni,
                        'nombres' => $responseBody['nombres'] ?? '',
                        'apellido_paterno' => $responseBody['apellido_paterno'] ?? '',
                        'apellido_materno' => $responseBody['apellido_materno'] ?? '',
                        'nombre_completo' => trim(
                            ($responseBody['nombres'] ?? '') . ' ' . 
                            ($responseBody['apellido_paterno'] ?? '') . ' ' . 
                            ($responseBody['apellido_materno'] ?? '')
                        )
                    ]
                ];
            } else {
                Log::warning('DNI no encontrado en Factiliza', [
                    'dni' => $numeroDni,
                    'status' => $statusCode,
                    'response' => $responseBody
                ]);

                return [
                    'success' => false,
                    'message' => 'DNI no encontrado en RENIEC',
                    'error_code' => $statusCode
                ];
            }

        } catch (RequestException $e) {
            Log::error('Error de conexión en consulta DNI Factiliza', [
                'dni' => $numeroDni,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response'
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con el servicio de consulta DNI: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Error general en consulta DNI Factiliza', [
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
     * Consultar datos de RUC desde la API de Factiliza (CON DEBUG INTENSIVO)
     */
    public function consultarRuc($numeroRuc)
    {
        // 🔧 DEBUGGING INICIAL
        error_log("🟦 RUC SERVICE - INICIO - RUC: " . $numeroRuc);
        
        try {
            // 🔧 VERIFICAR MÉTODO EXISTS
            if (!method_exists($this, 'validarFormatoRuc')) {
                error_log("❌ RUC SERVICE - MÉTODO validarFormatoRuc NO EXISTE");
                throw new \Exception("Método validarFormatoRuc no encontrado");
            }
            
            error_log("🟨 RUC SERVICE - VALIDANDO FORMATO");
            $validationResult = $this->validarFormatoRuc($numeroRuc);
            error_log("✅ Validation result: " . ($validationResult ? 'VÁLIDO' : 'INVÁLIDO'));

            if (!$validationResult) {
                error_log("⚠️ RUC SERVICE - FORMATO INVALIDO");
                return [
                    'success' => false,
                    'message' => 'Formato de RUC inválido. Debe tener exactamente 11 dígitos y empezar con 10 o 20.'
                ];
            }

            // 🔧 VERIFICAR TOKENS
            error_log("🟪 RUC SERVICE - VERIFICANDO TOKENS");
            error_log("🔑 Token RUC: " . (empty($this->tokenruc) ? 'VACÍO' : 'EXISTE'));
            
            if (empty($this->tokenruc)) {
                error_log("❌ RUC SERVICE - TOKEN VACÍO");
                return [
                    'success' => false,
                    'message' => 'Token de API no configurado para consultas RUC.'
                ];
            }

            // 🔧 VERIFICAR CLIENTE GUZZLE
            if (!$this->client) {
                error_log("❌ RUC SERVICE - CLIENTE GUZZLE NO INICIALIZADO");
                throw new \Exception("Cliente HTTP no inicializado");
            }

            $url = $this->baseUriRuc . '/ruc/info/' . $numeroRuc;
            error_log("🚀 RUC SERVICE - URL: " . $url);

            // 🔧 DEBUGGING ANTES DE LA PETICIÓN
            error_log("📡 RUC SERVICE - HACIENDO PETICION HTTP");
            error_log("🔧 Client type: " . get_class($this->client));
            error_log("🔧 Token preview: " . substr($this->tokenruc, 0, 15) . "...");
            
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->tokenruc,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel-Karaoke-App/1.0'
                ],
                'timeout' => 30,
                'connect_timeout' => 10
            ]);

            error_log("✅ RUC SERVICE - PETICIÓN COMPLETADA");

            $statusCode = $response->getStatusCode();
            $responseContent = $response->getBody()->getContents();
            
            error_log("📊 Status code: " . $statusCode);
            error_log("📏 Response size: " . strlen($responseContent) . " bytes");
            
            $responseBody = json_decode($responseContent, true);
            
            error_log("🔍 JSON valid: " . (json_last_error() === JSON_ERROR_NONE ? 'SÍ' : 'NO'));
            error_log("❓ JSON error: " . json_last_error_msg());

            // 🔧 VERIFICAR ESTRUCTURA DE RESPUESTA
            if (!is_array($responseBody)) {
                error_log("❌ RUC SERVICE - RESPUESTA NO ES ARRAY");
                error_log("📄 Raw response: " . $responseContent);
                throw new \Exception("Respuesta de API no es JSON válido");
            }

            error_log("🔑 Response keys: " . implode(', ', array_keys($responseBody)));
            error_log("✅ Has success key: " . (isset($responseBody['success']) ? 'SÍ' : 'NO'));
            error_log("✅ Success value: " . ($responseBody['success'] ?? 'NO_KEY'));

            // ✅ PROCESAR RESPUESTA
            $successValue = $responseBody['success'] ?? false;
            $isSuccess = ($successValue === true || $successValue === 1 || $successValue === "1");
            
            if ($statusCode === 200 && isset($responseBody['success']) && $isSuccess) {
                error_log("🟩 RUC SERVICE - RESPUESTA EXITOSA");
                
                $data = $responseBody['data'] ?? $responseBody;
                
                if (!is_array($data)) {
                    error_log("❌ RUC SERVICE - DATA NO ES ARRAY");
                    throw new \Exception("Estructura de datos inválida en respuesta");
                }
                
                $razonSocial = $data['nombre_o_razon_social'] ?? '';
                $direccionCompleta = $data['direccion_completa'] ?? $data['direccion'] ?? '';
                
                $resultado = [
                    'success' => true,
                    'data' => [
                        'ruc' => $data['numero'] ?? $numeroRuc,
                        'razon_social' => $razonSocial,
                        'nombre_comercial' => '',
                        'direccion' => $direccionCompleta,
                        'estado' => $data['estado'] ?? '',
                        'condicion' => $data['condicion'] ?? '',
                        'tipo_empresa' => $data['tipo_contribuyente'] ?? '',
                        'ubigeo' => $data['ubigeo_sunat'] ?? '',
                        'distrito' => $data['distrito'] ?? '',
                        'provincia' => $data['provincia'] ?? '',
                        'departamento' => $data['departamento'] ?? ''
                    ]
                ];
                
                error_log("✅ RUC SERVICE - RESULTADO GENERADO CORRECTAMENTE");
                return $resultado;
                
            } else {
                error_log("⚠️ RUC SERVICE - RUC NO ENCONTRADO O CONDICIONES NO CUMPLIDAS");
                error_log("📄 Full response: " . $responseContent);
                
                return [
                    'success' => false,
                    'message' => 'RUC no encontrado en SUNAT',
                    'error_code' => $statusCode
                ];
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log("🔴 RUC SERVICE - GUZZLE REQUEST EXCEPTION");
            error_log("❌ Error message: " . $e->getMessage());
            error_log("❌ Error code: " . $e->getCode());
            
            if ($e->hasResponse()) {
                error_log("❌ Response status: " . $e->getResponse()->getStatusCode());
                error_log("❌ Response body: " . $e->getResponse()->getBody()->getContents());
            }

            return [
                'success' => false,
                'message' => 'Error de conexión con el servicio de consulta RUC: ' . $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            error_log("🔴 RUC SERVICE - EXCEPTION GENERAL");
            error_log("❌ Error message: " . $e->getMessage());
            error_log("❌ Error class: " . get_class($e));
            error_log("❌ Error file: " . $e->getFile());
            error_log("❌ Error line: " . $e->getLine());
            error_log("❌ Stack trace: " . $e->getTraceAsString());

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