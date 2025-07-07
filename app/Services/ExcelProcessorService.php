<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ExcelProcessorService
{
    /**
     * Procesar archivo Excel de presentación semanal
     */
    public function processWeeklyExcel(string $filePath, string $week): array
    {
        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        // Verificar que el archivo es legible
        if (!is_readable($filePath)) {
            throw new \Exception("El archivo no es legible: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Error al cargar el archivo Excel: " . $e->getMessage());
        }
        
        $operations = collect();
        
        // Procesar cada hoja (VENTAS, COMPRAS, CANJES)
        $sheets = [
            'VENTAS' => 'V',
            'COMPRAS' => 'C', 
            'CANJES' => 'J'
        ];
        
        $processedSheets = [];
        
        foreach ($sheets as $sheetName => $operationType) {
            if ($spreadsheet->sheetNameExists($sheetName)) {
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $sheetOperations = $this->processSheet($worksheet, $operationType);
                $operations = $operations->merge($sheetOperations);
                $processedSheets[] = $sheetName;
            }
        }
        
        // Verificar que al menos una hoja fue procesada
        if (empty($processedSheets)) {
            throw new \Exception("No se encontraron hojas válidas (VENTAS, COMPRAS, CANJES) en el archivo Excel");
        }
        
        return [
            'operations' => $operations->toArray(),
            'total_operations' => $operations->count(),
            'summary' => $this->generateSummary($operations),
            'week' => $week,
            'processed_sheets' => $processedSheets
        ];
    }
    
    /**
     * Procesar archivo Excel de presentación mensual
     */
    public function processMonthlyExcel(string $filePath, string $month): array
    {
        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        // Verificar que el archivo es legible
        if (!is_readable($filePath)) {
            throw new \Exception("El archivo no es legible: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Error al cargar el archivo Excel: " . $e->getMessage());
        }
        
        $stocks = collect();
        
        // Procesar la primera hoja (asumiendo que es la única hoja con datos)
        $worksheet = $spreadsheet->getActiveSheet();
        $sheetStocks = $this->processMonthlySheet($worksheet);
        $stocks = $stocks->merge($sheetStocks);
        
        // Verificar que se procesaron datos
        if ($stocks->isEmpty()) {
            throw new \Exception("No se encontraron datos válidos en el archivo Excel. Los datos deben comenzar en la fila 29.");
        }
        
        return [
            'stocks' => $stocks->toArray(),
            'total_stocks' => $stocks->count(),
            'summary' => $this->generateMonthlySummary($stocks),
            'month' => $month
        ];
    }
    
    /**
     * Procesar una hoja específica del Excel
     */
    private function processSheet(Worksheet $worksheet, string $operationType): Collection
    {
        $operations = collect();
        
        // Los datos empiezan en la fila 8 (índice 7) - saltando los encabezados de la fila 7
        $startRow = 8;
        $maxRow = $worksheet->getHighestRow();
        
        for ($row = $startRow; $row <= $maxRow; $row++) {
            $operation = $this->extractOperationFromRow($worksheet, $row, $operationType);
            
            if ($operation && $this->isValidOperation($operation)) {
                $operations->push($operation);
            }
        }
        
        return $operations;
    }
    
    /**
     * Procesar una hoja específica del Excel mensual
     */
    private function processMonthlySheet(Worksheet $worksheet): Collection
    {
        $stocks = collect();
        
        // Los datos empiezan en la fila 29 (índice 28) - saltando los encabezados de la fila 28
        $startRow = 29;
        $maxRow = $worksheet->getHighestRow();
        
        for ($row = $startRow; $row <= $maxRow; $row++) {
            $stock = $this->extractStockFromRow($worksheet, $row);
            
            if ($stock && $this->isValidStock($stock)) {
                $stocks->push($stock);
            }
        }
        
        return $stocks;
    }
    
    /**
     * Extraer datos de una fila específica
     */
    private function extractOperationFromRow(Worksheet $worksheet, int $row, string $operationType): ?array
    {
        // Mapeo de columnas A-I
        $tipoOper = $this->cleanValue($worksheet->getCell('A' . $row)->getValue());
        $tipoEspecie = $this->cleanValue($worksheet->getCell('B' . $row)->getValue());
        $codigoEspecie = $this->cleanValue($worksheet->getCell('C' . $row)->getValue());
        $cantEspecies = $this->cleanValue($worksheet->getCell('D' . $row)->getValue());
        $codigoAfectacion = $this->cleanValue($worksheet->getCell('E' . $row)->getValue());
        $tipoValuacion = $this->cleanValue($worksheet->getCell('F' . $row)->getValue());
        $fechaMovimiento = $this->cleanValue($worksheet->getCell('G' . $row)->getValue());
        $precioCompra = $this->cleanValue($worksheet->getCell('H' . $row)->getValue());
        $fechaLiquidacion = $this->cleanValue($worksheet->getCell('I' . $row)->getValue());
        
        // Verificar si la fila tiene datos válidos
        if (empty($tipoOper) && empty($tipoEspecie) && empty($codigoEspecie)) {
            return null;
        }
        
        // Convertir fechas de DD/MM/YYYY a YYYY-MM-DD
        $fechaMovimiento = $this->convertDate($fechaMovimiento);
        $fechaLiquidacion = $this->convertDate($fechaLiquidacion);
        
        return [
            'tipo_operacion' => $operationType,
            'tipo_especie' => $tipoEspecie,
            'codigo_especie' => $codigoEspecie,
            'cant_especies' => $cantEspecies,
            'codigo_afectacion' => $codigoAfectacion,
            'tipo_valuacion' => $tipoValuacion,
            'fecha_movimiento' => $fechaMovimiento,
            'fecha_liquidacion' => $fechaLiquidacion,
            'precio_compra' => $precioCompra,
            'row_number' => $row
        ];
    }
    
    /**
     * Extraer datos de una fila específica del Excel mensual
     */
    private function extractStockFromRow(Worksheet $worksheet, int $row): ?array
    {
        // Mapeo de columnas A-S según la estructura proporcionada
        $nombre = $this->cleanValue($worksheet->getCell('A' . $row)->getValue());
        $tipoOper = $this->cleanValue($worksheet->getCell('B' . $row)->getValue());
        $tipoEspecie = $this->cleanValue($worksheet->getCell('C' . $row)->getValue());
        $codigoEspecie = $this->cleanValue($worksheet->getCell('D' . $row)->getValue());
        $cantTotalEspecSt = $this->cleanValue($worksheet->getCell('E' . $row)->getValue());
        $cantRealEspecSt = $this->cleanValue($worksheet->getCell('F' . $row)->getValue());
        $codSsnAfect = $this->cleanValue($worksheet->getCell('G' . $row)->getValue());
        $tipoValuac = $this->cleanValue($worksheet->getCell('H' . $row)->getValue());
        $conCotiz = $this->cleanValue($worksheet->getCell('I' . $row)->getValue());
        $libreDisp = $this->cleanValue($worksheet->getCell('J' . $row)->getValue());
        $enteEmisorGEc = $this->cleanValue($worksheet->getCell('K' . $row)->getValue());
        $enteEmisorArt = $this->cleanValue($worksheet->getCell('L' . $row)->getValue());
        $previsDesvalor = $this->cleanValue($worksheet->getCell('M' . $row)->getValue());
        $valorContable = $this->cleanValue($worksheet->getCell('N' . $row)->getValue());
        $fechaPaseVt = $this->cleanValue($worksheet->getCell('O' . $row)->getValue());
        $precioPaseVt = $this->cleanValue($worksheet->getCell('P' . $row)->getValue());
        $enCustodia = $this->cleanValue($worksheet->getCell('Q' . $row)->getValue());
        $financiera = $this->cleanValue($worksheet->getCell('R' . $row)->getValue());
        $valCotizTpVtFinanc = $this->cleanValue($worksheet->getCell('S' . $row)->getValue());
        
        // Verificar si la fila tiene datos válidos
        if (empty($nombre) && empty($tipoEspecie) && empty($codigoEspecie)) {
            return null;
        }
        
        // Validar tipo_especie contra ssn_species
        $validation = $this->validateTipoEspecie($tipoEspecie, $codigoEspecie);
        if ($validation && !$validation['valid']) {
            // Agregar error de validación pero continuar procesando
            \Log::warning("Validación SSN fallida en fila {$row}: " . $validation['error']);
        }
        
        // Convertir valores booleanos
        $conCotiz = $this->convertToBoolean($conCotiz);
        $libreDisp = $this->convertToBoolean($libreDisp);
        $enteEmisorGEc = $this->convertToBoolean($enteEmisorGEc);
        $enteEmisorArt = $this->convertToBoolean($enteEmisorArt);
        $enCustodia = $this->convertToBoolean($enCustodia);
        $financiera = $this->convertToBoolean($financiera);
        
        // Convertir fechas
        $fechaPaseVt = $this->convertDate($fechaPaseVt);
        
        // Determinar el tipo de stock (siempre 'S' para mensuales)
        $tipo = $this->determineStockType($tipoOper);
        
        return [
            'nombre' => $nombre,
            'tipo' => $tipo, 
            'tipo_especie' => $tipoEspecie,
            'codigo_especie' => $codigoEspecie,
            'cantidad_devengado_especies' => $cantTotalEspecSt,
            'cantidad_percibido_especies' => $cantRealEspecSt,
            'codigo_afectacion' => $codSsnAfect,
            'tipo_valuacion' => $tipoValuac,
            'con_cotizacion' => $conCotiz,
            'libre_disponibilidad' => $libreDisp,
            'emisor_grupo_economico' => $enteEmisorGEc,
            'emisor_art_ret' => $enteEmisorArt,
            'prevision_desvalorizacion' => $previsDesvalor,
            'valor_contable' => $valorContable,
            'fecha_pase_vt' => $fechaPaseVt,
            'precio_pase_vt' => $precioPaseVt,
            'en_custodia' => $enCustodia,
            'financiera' => $financiera,
            'valor_financiero' => $valCotizTpVtFinanc,
            'row_number' => $row,
            'validation_error' => $validation && !$validation['valid'] ? $validation['error'] : null
        ];
    }
    
    /**
     * Limpiar valor: convertir "VACIO", "Vacío" y "No completar" a null
     */
    private function cleanValue($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Convertir a string y limpiar
        $stringValue = trim(strval($value));
        
        // Normalizar caracteres especiales UTF-8
        $stringValue = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $stringValue);
        $stringValue = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $stringValue);
        
        // Verificar si es "VACIO", "Vacío" o "No completar"
        if (strtoupper($stringValue) === 'VACIO' || 
            strtoupper($stringValue) === 'VACÍO' || 
            strtoupper($stringValue) === 'NO COMPLETAR' ||
            strtoupper($stringValue) === 'VACIO' ||
            strtoupper($stringValue) === 'VACÍO') {
            return null;
        }
        
        // Convertir números decimales con comas a formato MySQL
        return $this->convertDecimalValue($stringValue);
    }
    
    /**
     * Convertir valores decimales con comas a formato MySQL (puntos)
     */
    private function convertDecimalValue($value)
    {
        // Si es un número con coma como separador decimal, convertirlo
        if (is_numeric(str_replace(',', '.', $value))) {
            // Reemplazar coma por punto para convertir a formato MySQL
            $converted = str_replace(',', '.', $value);
            
            // Verificar que el resultado es un número válido
            if (is_numeric($converted)) {
                return $converted;
            }
        }
        
        // Si no es un número decimal, retornar el valor original
        return $value;
    }
    
    /**
     * Convertir valor a booleano
     */
    private function convertToBoolean($value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        $stringValue = strval($value);
        return in_array(strtoupper($stringValue), ['1', 'TRUE', 'VERDADERO', 'SI', 'SÍ', 'YES']);
    }
    
    /**
     * Determinar el tipo de stock basado en el tipo de especie y validar contra ssn_species
     */
    private function determineStockType($tipoEspecie): string
    {
        // Para presentaciones mensuales, todas las operaciones son de tipo 'I' (Inversiones)
        return 'I';
    }
    
    /**
     * Validar que el tipo_especie existe en la tabla ssn_species
     */
    private function validateTipoEspecie($tipoEspecie, $codigoEspecie): ?array
    {
        if (empty($tipoEspecie) || empty($codigoEspecie)) {
            return null;
        }
        
        // Buscar en la tabla ssn_species
        $ssnSpecie = \App\Domain\Models\SsnSpecie::where('tipo_especie', $tipoEspecie)
            ->where('codigo_ssn', $codigoEspecie)
            ->where('activo', true)
            ->first();
            
        if (!$ssnSpecie) {
            return [
                'valid' => false,
                'error' => "Tipo de especie '{$tipoEspecie}' con código '{$codigoEspecie}' no encontrado en el catálogo SSN"
            ];
        }
        
        return [
            'valid' => true,
            'ssn_specie' => $ssnSpecie,
            'descripcion' => $ssnSpecie->descripcion
        ];
    }
    
    /**
     * Convertir fecha de DD/MM/YYYY a YYYY-MM-DD
     */
    private function convertDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        // Si ya está en formato YYYY-MM-DD, retornarlo
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Si está en formato DD/MM/YYYY, convertirlo
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            $parts = explode('/', $date);
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        // Si es un número de Excel, convertirlo
        if (is_numeric($date)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date))->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Validar si una operación es válida
     */
    private function isValidOperation(array $operation): bool
    {
        // Campos obligatorios que deben tener valor
        $requiredFields = ['tipo_especie', 'codigo_especie', 'cant_especies', 'fecha_movimiento'];
        
        foreach ($requiredFields as $field) {
            if (empty($operation[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validar si un stock es válido
     */
    private function isValidStock(array $stock): bool
    {
        // Campos obligatorios que deben tener valor
        $requiredFields = ['nombre', 'tipo_especie', 'codigo_especie'];
        
        foreach ($requiredFields as $field) {
            if (empty($stock[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generar resumen de operaciones
     */
    private function generateSummary(Collection $operations): array
    {
        return [
            'total_operaciones' => $operations->count(),
            'por_tipo' => [
                'compras' => $operations->where('tipo_operacion', 'C')->count(),
                'ventas' => $operations->where('tipo_operacion', 'V')->count(),
                'canjes' => $operations->where('tipo_operacion', 'J')->count(),
            ],
            'por_especie' => $operations->groupBy('tipo_especie')->map->count()->toArray(),
            'por_valuacion' => $operations->groupBy('tipo_valuacion')->map->count()->toArray(),
        ];
    }
    
    /**
     * Generar resumen de stocks mensuales
     */
    private function generateMonthlySummary(Collection $stocks): array
    {
        return [
            'total_stocks' => $stocks->count(),
            'por_tipo' => [
                'inversiones' => $stocks->where('tipo', 'I')->count(),
                'plazos_fijos' => $stocks->where('tipo', 'P')->count(),
                'otros' => $stocks->where('tipo', 'O')->count(),
            ],
            'por_especie' => $stocks->groupBy('tipo_especie')->map->count()->toArray(),
            'por_valuacion' => $stocks->groupBy('tipo_valuacion')->map->count()->toArray(),
            'valor_total_contable' => $stocks->sum('valor_contable'),
            'en_custodia' => $stocks->where('en_custodia', true)->count(),
            'financiera' => $stocks->where('financiera', true)->count(),
        ];
    }
    
    /**
     * Generar JSON para SSN
     */
    public function generateSsnJson(array $operations, string $week): array
    {
        $ssnOperations = [];
        
        foreach ($operations as $operation) {
            $ssnOperation = [
                'tipoOperacion' => $operation['tipo_operacion'],
                'tipoEspecie' => $operation['tipo_especie'],
                'codigoEspecie' => $operation['codigo_especie'],
                'cantEspecies' => (float) $operation['cant_especies'],
                'codigoAfectacion' => $operation['codigo_afectacion'],
                'tipoValuacion' => $operation['tipo_valuacion'],
                'fechaMovimiento' => $operation['fecha_movimiento'],
                'fechaLiquidacion' => $operation['fecha_liquidacion'],
            ];
            
            // Agregar campos específicos según el tipo de operación
            if ($operation['tipo_operacion'] === 'C' && !empty($operation['precio_compra'])) {
                $ssnOperation['precioCompra'] = (float) $operation['precio_compra'];
            }
            
            $ssnOperations[] = $ssnOperation;
        }
        
        return [
            'semana' => $week,
            'operaciones' => $ssnOperations,
            'totalOperaciones' => count($ssnOperations)
        ];
    }
    
    /**
     * Generar JSON para SSN de presentaciones mensuales
     */
    public function generateMonthlySsnJson(array $stocks, string $month): array
    {
        $ssnStocks = [];
        
        foreach ($stocks as $stock) {
            $ssnStock = [
                'tipo' => $stock['tipo'],
                'tipoEspecie' => $stock['tipo_especie'],
                'codigoEspecie' => $stock['codigo_especie'],
                'cantidadDevengadoEspecies' => (float) $stock['cantidad_devengado_especies'],
                'cantidadPercibidoEspecies' => (float) $stock['cantidad_percibido_especies'],
                'codigoAfectacion' => $stock['codigo_afectacion'],
                'tipoValuacion' => $stock['tipo_valuacion'],
                'conCotizacion' => $stock['con_cotizacion'],
                'libreDisponibilidad' => $stock['libre_disponibilidad'],
                'emisorGrupoEconomico' => $stock['emisor_grupo_economico'],
                'emisorArtRet' => $stock['emisor_art_ret'],
                'previsionDesvalorizacion' => $stock['prevision_desvalorizacion'],
                'valorContable' => (float) $stock['valor_contable'],
                'fechaPaseVt' => $stock['fecha_pase_vt'],
                'precioPaseVt' => $stock['precio_pase_vt'],
                'enCustodia' => $stock['en_custodia'],
                'financiera' => $stock['financiera'],
                'valorFinanciero' => $stock['valor_financiero'],
            ];
            
            $ssnStocks[] = $ssnStock;
        }
        
        return [
            'mes' => $month,
            'stocks' => $ssnStocks,
            'totalStocks' => count($ssnStocks)
        ];
    }
} 