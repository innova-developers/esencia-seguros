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
        // Mapeo de columnas A-K según la estructura real del Excel
        $tipoOper = $this->cleanValue($worksheet->getCell('A' . $row)->getValue());
        $tipoEspecie = $this->cleanValue($worksheet->getCell('B' . $row)->getValue());
        $codigoEspecie = $this->cleanValue($worksheet->getCell('C' . $row)->getValue());
        $cantEspecies = $this->cleanValue($worksheet->getCell('D' . $row)->getValue());
        $codigoAfectacion = $this->cleanValue($worksheet->getCell('E' . $row)->getValue());
        $tipoValuacion = $this->cleanValue($worksheet->getCell('F' . $row)->getValue());
        $fechaMovimiento = $this->cleanValue($this->getCellValueAsString($worksheet, 'G' . $row));
        
        // Verificar si la fila tiene datos válidos
        if (empty($tipoOper) && empty($tipoEspecie) && empty($codigoEspecie)) {
            return null;
        }
        
        // Convertir fechas de DD/MM/YYYY a YYYY-MM-DD
        $fechaMovimiento = $this->convertDate($fechaMovimiento);
        
        $operation = [
            'tipo_operacion' => $operationType,
            'tipo_especie' => $tipoEspecie,
            'codigo_especie' => $codigoEspecie,
            'cant_especies' => $cantEspecies,
            'codigo_afectacion' => $codigoAfectacion,
            'tipo_valuacion' => $tipoValuacion,
            'fecha_movimiento' => $fechaMovimiento,
            'row_number' => $row
        ];
        
        // Agregar campos específicos según el tipo de operación
        if ($operationType === 'C') {
            // Para compras: H=precio_compra, I=fecha_liquidacion
            $precioCompra = $this->cleanValue($worksheet->getCell('H' . $row)->getValue());
            $fechaLiquidacion = $this->cleanValue($this->getCellValueAsString($worksheet, 'I' . $row));
            
            // Log para debuggear precio de compra
            \Log::info('Procesando precio de compra', [
                'row' => $row,
                'cell_h_raw' => $worksheet->getCell('H' . $row)->getValue(),
                'precio_compra_cleaned' => $precioCompra,
                'tipo_especie' => $tipoEspecie,
                'codigo_especie' => $codigoEspecie
            ]);
            
            $operation['precio_compra'] = $precioCompra;
            $operation['fecha_liquidacion'] = $this->convertDate($fechaLiquidacion);
        } elseif ($operationType === 'V') {
            // Para ventas: H=fecha_pase_vt, I=precio_pase_vt, J=fecha_liquidacion, K=precio_venta
            $fechaPaseVt = $this->cleanValue($this->getCellValueAsString($worksheet, 'H' . $row));
            $precioPaseVt = $this->cleanValue($worksheet->getCell('I' . $row)->getValue());
            $fechaLiquidacion = $this->cleanValue($this->getCellValueAsString($worksheet, 'J' . $row));
            $precioVenta = $this->cleanValue($worksheet->getCell('K' . $row)->getValue());
            
            $operation['fecha_pase_vt'] = $this->convertDate($fechaPaseVt);
            $operation['precio_pase_vt'] = $precioPaseVt;
            $operation['fecha_liquidacion'] = $this->convertDate($fechaLiquidacion);
            $operation['precio_venta'] = $precioVenta;
        }
        
        return $operation;
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
     * Obtener valor de celda como string, forzando la lectura como texto para fechas
     */
    private function getCellValueAsString(Worksheet $worksheet, string $cellAddress)
    {
        $cell = $worksheet->getCell($cellAddress);
        
        // Si la celda tiene formato de fecha, intentar obtener el valor como string
        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC && 
            \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            
            try {
                // Intentar obtener el valor como string formateado
                $formattedValue = $cell->getFormattedValue();
                if (!empty($formattedValue)) {
                    return $formattedValue;
                }
            } catch (\Exception $e) {
                \Log::debug("Error al obtener valor formateado de celda {$cellAddress}: " . $e->getMessage());
            }
        }
        
        // Si no se puede obtener como string formateado, usar el valor normal
        return $cell->getValue();
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
        
        // Log para debugging
        \Log::debug("ConvertDate - Valor original: '{$date}' (tipo: " . gettype($date) . ")");
        
        // Si ya está en formato YYYY-MM-DD, retornarlo
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            \Log::debug("ConvertDate - Ya está en formato YYYY-MM-DD: {$date}");
            return $date;
        }
        
        // Si está en formato DD/MM/YYYY, convertirlo
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            $parts = explode('/', $date);
            $result = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            \Log::debug("ConvertDate - Convertido de DD/MM/YYYY: {$date} → {$result}");
            return $result;
        }
        
        // Si es un número de Excel, convertirlo
        if (is_numeric($date)) {
            try {
                // Validar que el número de Excel esté en un rango razonable
                $minExcelDate = 1; // 1900-01-01
                $maxExcelDate = 73050; // 2100-01-01
                
                if ($date < $minExcelDate || $date > $maxExcelDate) {
                    \Log::warning("ConvertDate - Número de Excel fuera de rango válido: {$date}");
                    return null;
                }
                
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                $excelDate->setTimezone(new \DateTimeZone('UTC'));
                $original = $excelDate->format('Y-m-d');
                \Log::debug("ConvertDate - Fecha original de Excel (UTC): {$original}");
                $excelDate->modify('+1 day');
                $modificado = $excelDate->format('Y-m-d');
                \Log::debug("ConvertDate - Fecha tras sumar 1 día: {$modificado}");
                $result = Carbon::instance($excelDate)->format('Y-m-d');
                
                // Validar que la fecha resultante sea razonable (entre 1900 y 2100)
                $year = (int) substr($result, 0, 4);
                if ($year < 1900 || $year > 2100) {
                    \Log::warning("ConvertDate - Año resultante fuera de rango: {$result} (número Excel: {$date})");
                    return null;
                }
                
                \Log::debug("ConvertDate - Convertido de número Excel (corrigiendo desfase): {$date} → {$result}");
                return $result;
            } catch (\Exception $e) {
                \Log::error("ConvertDate - Error al convertir número Excel {$date}: " . $e->getMessage());
                return null;
            }
        }
        
        // Intentar otros formatos de fecha comunes
        $formats = [
            'd-m-Y',    // 19-06-2025
            'Y/m/d',    // 2025/06/19
            'd.m.Y',    // 19.06.2025
            'm/d/Y',    // 06/19/2025 (formato americano)
        ];
        
        foreach ($formats as $format) {
            try {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj) {
                    $result = $dateObj->format('Y-m-d');
                    \Log::debug("ConvertDate - Convertido con formato {$format}: {$date} → {$result}");
                    return $result;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        \Log::warning("ConvertDate - No se pudo convertir la fecha: '{$date}'");
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
     * Formatear fecha para SSN (DDMMYYYY)
     */
    public function formatDateForSSN(?string $date): string
    {
        if (!$date || $date === '') {
            return '';
        }
        
        try {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            // Intentar otros formatos comunes
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            // Si no se puede parsear, devolver string vacío
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Generar JSON para SSN de presentaciones mensuales
     */
    public function generateMonthlySsnJson(array $stocks, string $month): array
    {
        $ssnStocks = [];
        
        foreach ($stocks as $index => $stock) {
            try {
                // Validar que el stock tenga los campos mínimos requeridos
                if (!isset($stock['tipo']) || !isset($stock['tipo_especie']) || !isset($stock['codigo_especie'])) {
                    \Log::warning("Stock {$index} no tiene campos mínimos requeridos", $stock);
                    continue;
                }
                
                $ssnStock = [
                    'tipo' => $stock['tipo'],
                    'tipoEspecie' => $stock['tipo_especie'],
                    'codigoEspecie' => $stock['codigo_especie'],
                    'cantidadDevengadoEspecies' => (float) ($stock['cantidad_devengado_especies'] ?? 0),
                    'cantidadPercibidoEspecies' => (float) ($stock['cantidad_percibido_especies'] ?? 0),
                    'codigoAfectacion' => $stock['codigo_afectacion'] ?? '',
                    'tipoValuacion' => $stock['tipo_valuacion'] ?? '',
                    'conCotizacion' => $stock['con_cotizacion'] ?? false,
                    'libreDisponibilidad' => $stock['libre_disponibilidad'] ?? false,
                    'emisorGrupoEconomico' => $stock['emisor_grupo_economico'] ?? false,
                    'emisorArtRet' => $stock['emisor_art_ret'] ?? false,
                    'previsionDesvalorizacion' => $stock['prevision_desvalorizacion'] !== null && $stock['prevision_desvalorizacion'] !== '' ? (float) $stock['prevision_desvalorizacion'] : 0,
                    'valorContable' => $stock['valor_contable'] !== null && $stock['valor_contable'] !== '' ? (float) $stock['valor_contable'] : 0,
                    'fechaPaseVt' => $this->formatDateForSSN($stock['fecha_pase_vt'] ?? ''),
                    'precioPaseVt' => $stock['precio_pase_vt'] !== null && $stock['precio_pase_vt'] !== '' ? (float) $stock['precio_pase_vt'] : 0,
                    'enCustodia' => $stock['en_custodia'] ?? false,
                    'financiera' => $stock['financiera'] ?? false,
                    'valorFinanciero' => $stock['valor_financiero'] !== null && $stock['valor_financiero'] !== '' ? (float) $stock['valor_financiero'] : 0,
                ];
                
                $ssnStocks[] = $ssnStock;
                
            } catch (\Exception $e) {
                \Log::error("Error procesando stock {$index} para SSN", [
                    'stock' => $stock,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }
        
        return [
            'codigoCompania' => config('services.ssn.cia', '0001'),
            'cronograma' => $month,
            'tipoEntrega' => 'MENSUAL',
            'stocks' => $ssnStocks,
            'totalStocks' => count($ssnStocks)
        ];
    }

    /**
     * Generar JSON para SSN (solo para preview)
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
                'fechaMovimiento' => $this->formatDateForSSN($operation['fecha_movimiento']),
                'fechaLiquidacion' => $this->formatDateForSSN($operation['fecha_liquidacion']),
            ];
            
            // Agregar campos específicos según el tipo de operación
            if ($operation['tipo_operacion'] === 'C') {
                $ssnOperation['precioCompra'] = isset($operation['precio_compra']) ? (float) $operation['precio_compra'] : 0;
            } elseif ($operation['tipo_operacion'] === 'V') {
                $ssnOperation['precioVenta'] = isset($operation['precio_venta']) ? (float) $operation['precio_venta'] : 0;

                // Normalizar valores
                $tipoEspecie = strtoupper(trim($operation['tipo_especie'] ?? ''));
                $tipoValuacion = strtoupper(trim($operation['tipo_valuacion'] ?? ''));
                $debeIncluirPaseVT = in_array($tipoEspecie, ['TP', 'ON']) && $tipoValuacion === 'T';

                // Log para debuggear
                \Log::info('Procesando operación de venta para SSN', [
                    'tipo_especie' => $tipoEspecie,
                    'tipo_valuacion' => $tipoValuacion,
                    'debe_incluir_pase_vt' => $debeIncluirPaseVT,
                    'operation_id' => $operation['id'] ?? 'unknown'
                ]);

                // Siempre incluir los campos, pero con valores apropiados según las condiciones
                if ($debeIncluirPaseVT) {
                    $fechaPaseVT = $this->formatDateForSSN($operation['fecha_pase_vt']);
                    $ssnOperation['fechaPaseVT'] = $fechaPaseVT;
                    $ssnOperation['precioPaseVT'] = isset($operation['precio_pase_vt']) && $operation['precio_pase_vt'] !== null && $operation['precio_pase_vt'] !== "" ? (float) $operation['precio_pase_vt'] : "";
                    
                    \Log::info('Incluyendo campos PaseVT con valores', [
                        'fechaPaseVT' => $ssnOperation['fechaPaseVT'],
                        'precioPaseVT' => $ssnOperation['precioPaseVT']
                    ]);
                } else {
                    // Incluir campos con string vacío cuando no corresponda
                    $ssnOperation['fechaPaseVT'] = "";
                    $ssnOperation['precioPaseVT'] = "";
                    
                    \Log::info('Incluyendo campos PaseVT vacíos', [
                        'razon' => 'No cumple condiciones (TP/ON y T)'
                    ]);
                }
            }
            
            $ssnOperations[] = $ssnOperation;
        }
        
        return [
            'codigoCompania' => config('services.ssn.cia', '0001'),
            'cronograma' => $week,
            'tipoEntrega' => 'SEMANAL',
            'operaciones' => $ssnOperations,
            'totalOperaciones' => count($ssnOperations)
        ];
    }
} 