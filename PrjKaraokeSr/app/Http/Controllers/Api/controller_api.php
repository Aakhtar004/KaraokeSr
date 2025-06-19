<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ApiConsultaService;

class controller_api extends Controller
{
    private $apiService;

    /**
     * Inyección de dependencia del servicio API
     */
    public function __construct(ApiConsultaService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Endpoint para consultar DNI
     */
    public function consultarDni(Request $request)
    {
        try {
            // Validar entrada con mensajes personalizados
            $request->validate([
                'dni' => 'required|string|size:8|regex:/^[0-9]{8}$/'
            ], [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
                'dni.regex' => 'El DNI solo debe contener números'
            ]);

            // Llamar al servicio y retornar respuesta JSON
            $resultado = $this->apiService->consultarDni($request->dni);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar DNI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✨ ENDPOINT COMPLETO PARA CONSULTAR RUC
     */
    public function consultarRuc(Request $request)
    {
        try {
            // Validar entrada con mensajes personalizados
            $request->validate([
                'ruc' => 'required|string|size:11|regex:/^(10|20)[0-9]{9}$/'
            ], [
                'ruc.required' => 'El RUC es obligatorio',
                'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
                'ruc.regex' => 'El RUC debe empezar con 10 o 20 y contener solo números'
            ]);

            // ✨ LLAMAR AL SERVICIO Y RETORNAR RESPUESTA JSON
            $resultado = $this->apiService->consultarRuc($request->ruc);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar RUC: ' . $e->getMessage()
            ], 500);
        }
    }
}