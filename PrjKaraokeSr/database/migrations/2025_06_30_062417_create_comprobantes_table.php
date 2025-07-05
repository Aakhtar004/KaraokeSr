<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->comment('Comprobantes de pago emitidos');
            $table->bigIncrements('id_comprobante');
            $table->unsignedBigInteger('id_pedido')->index('fk_comprobante_pedido')->comment('FK al pedido asociado a este comprobante');
            $table->unsignedInteger('id_usuario_cajero')->index('fk_comprobante_usuario_cajero')->comment('FK al usuario (cajero) que emitió el comprobante');
            $table->enum('tipo_documento_cliente', ['DNI', 'RUC', 'CE', 'PASAPORTE', 'SIN_DOCUMENTO'])->default('SIN_DOCUMENTO')->comment('Tipo de documento del cliente');
            $table->string('numero_documento_cliente', 20)->nullable()->index('idx_comprobante_cliente_doc')->comment('Número del documento del cliente (DNI/RUC)');
            $table->string('nombre_razon_social_cliente', 200)->comment('Nombre o razón social del cliente');
            $table->string('direccion_cliente')->nullable()->comment('Dirección fiscal del cliente');
            $table->string('serie_comprobante', 10)->comment('Serie del comprobante (ej: B001, F001)');
            $table->unsignedBigInteger('numero_correlativo_comprobante')->comment('Número correlativo del comprobante');
            $table->timestamp('fecha_emision')->nullable()->useCurrent()->index('idx_comprobante_fecha_emision')->comment('Fecha y hora de emisión del comprobante');
            $table->enum('moneda', ['PEN', 'USD'])->default('PEN')->comment('Moneda del comprobante');
            $table->decimal('subtotal_comprobante', 12)->comment('Monto antes de impuestos');
            $table->decimal('igv_aplicado_tasa', 5)->default(18)->comment('Tasa de IGV aplicada (ej: 18.00 para 18%)');
            $table->decimal('monto_igv', 12)->comment('Monto del IGV');
            $table->decimal('monto_total_comprobante', 12)->comment('Monto total a pagar');
            $table->enum('tipo_comprobante', ['BOLETA', 'FACTURA', 'NOTA_VENTA'])->comment('Tipo de comprobante emitido');
            $table->enum('metodo_pago', ['EFECTIVO', 'TARJETA', 'YAPE', 'PLIN', 'TRANSFERENCIA', 'MIXTO'])->nullable()->comment('Método de pago utilizado');
            $table->string('referencia_pago', 100)->nullable()->comment('Referencia para pagos con tarjeta, yape, plin, etc.');
            $table->enum('estado_comprobante', ['EMITIDO', 'ANULADO', 'PAGADO'])->default('EMITIDO')->index('idx_comprobante_estado')->comment('Estado del comprobante');
            $table->text('qr_code_data')->nullable()->comment('Datos para generar el QR de SUNAT (para facturación electrónica)');
            $table->string('hash_sunat')->nullable()->comment('Código Hash de SUNAT para facturación electrónica');
            $table->text('notas_comprobante')->nullable();
            $table->timestamp('fecha_anulacion')->nullable();

            $table->unique(['serie_comprobante', 'numero_correlativo_comprobante', 'tipo_comprobante'], 'uq_serie_correlativo_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
