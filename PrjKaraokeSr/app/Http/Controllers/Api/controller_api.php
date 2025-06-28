<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use App\Services\ApiConsultaService;

class controller_api extends Controller
{
    protected $apiService;

    public function __construct(ApiConsultaService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * ✨ ENDPOINT PARA CONSULTAR DNI (CORREGIDO)
     */
    public function consultarDni(Request $request)
    {
        error_log("🟦 DNI ENDPOINT - INICIO");
        
        try {
            error_log("🟨 DNI ENDPOINT - VALIDANDO");
            
            $request->validate([
                'dni' => 'required|string|size:8|regex:/^[0-9]{8}$/'
            ]);

            error_log("🟩 DNI ENDPOINT - VALIDACION EXITOSA");
            
            $resultado = $this->apiService->consultarDni($request->dni);
            
            error_log("✅ DNI ENDPOINT - COMPLETADO");
            
            return response()->json($resultado);

        } catch (\Exception $e) {
            error_log("🔴 DNI ENDPOINT - ERROR: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * ✨ ENDPOINT PARA CONSULTAR RUC (CON ERROR_LOG PARA LOGS)
     */
    public function consultarRuc(Request $request)
    {
        error_log("🟦 RUC ENDPOINT - INICIO");
        error_log("📋 Request data: " . json_encode($request->all()));

        try {
            error_log("🟨 RUC ENDPOINT - PRE-VALIDACION");
            
            $request->validate([
                'ruc' => 'required|string|size:11|regex:/^(10|20)[0-9]{9}$/'
            ], [
                'ruc.required' => 'El RUC es obligatorio',
                'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
                'ruc.regex' => 'El RUC debe empezar con 10 o 20 y contener solo números'
            ]);

            error_log("🟩 RUC ENDPOINT - VALIDACION EXITOSA");
            error_log("✅ RUC validado: " . $request->ruc);

            // 🔧 VERIFICAR SERVICIO
            if (!$this->apiService) {
                error_log("❌ RUC ENDPOINT - SERVICIO NO DISPONIBLE");
                throw new \Exception("Servicio API no disponible");
            }

            error_log("🚀 RUC ENDPOINT - LLAMANDO AL SERVICIO");
            $resultado = $this->apiService->consultarRuc($request->ruc);
            
            error_log("📦 RUC ENDPOINT - RESULTADO OBTENIDO");
            error_log("✅ Success: " . ($resultado['success'] ?? 'NO_KEY'));
            
            if (!is_array($resultado)) {
                error_log("❌ RUC ENDPOINT - RESULTADO NO ES ARRAY");
                throw new \Exception("Resultado del servicio no es válido");
            }

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            error_log("🔴 RUC ENDPOINT - ERROR DE VALIDACION");
            error_log("❌ Validation errors: " . json_encode($e->errors()));

            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors()))
            ], 422);

        } catch (\Exception $e) {
            error_log("🔴 RUC ENDPOINT - EXCEPTION");
            error_log("❌ Error message: " . $e->getMessage());
            error_log("❌ Error class: " . get_class($e));
            error_log("❌ Error file: " . $e->getFile());
            error_log("❌ Error line: " . $e->getLine());
            error_log("❌ Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}