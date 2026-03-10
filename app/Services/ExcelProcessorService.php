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
        
        // Procesar cada hoja (VENTAS, COMPRAS, CANJES, PLAZOS FIJOS)
        $sheets = [
            'VENTAS' => 'V',
            'COMPRAS' => 'C', 
            'CANJES' => 'J',
            'PLAZOS FIJOS' => 'P'
        ];
        
        $processedSheets = [];
        
        foreach ($sheets as $sheetName => $operationType) {
            if ($spreadsheet->sheetNameExists($sheetName)) {
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                
                // Procesar hoja de PLAZOS FIJOS con estructura diferente
                if ($operationType === 'P') {
                    $sheetOperations = $this->processPlazosFijosSheet($worksheet);
                } else {
                    $sheetOperations = $this->processSheet($worksheet, $operationType);
                }
                
                $operations = $operations->merge($sheetOperations);
                $processedSheets[] = $sheetName;
            }
        }
        
        // Verificar que al menos una hoja fue procesada
        if (empty($processedSheets)) {
            throw new \Exception("No se encontraron hojas válidas (VENTAS, COMPRAS, CANJES, PLAZOS FIJOS) en el archivo Excel");
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
        
        // Log de todas las hojas disponibles para debugging
        $allSheetNames = $spreadsheet->getSheetNames();
        \Log::info('Hojas disponibles en el Excel mensual', [
            'sheets' => $allSheetNames,
            'total_sheets' => count($allSheetNames)
        ]);
        
        // Procesar la hoja principal (inversiones)
        $worksheet = $spreadsheet->getActiveSheet();
        $activeSheetName = $worksheet->getTitle();
        \Log::info('Procesando hoja activa', ['sheet_name' => $activeSheetName]);
        $sheetStocks = $this->processMonthlySheet($worksheet);
        \Log::info('Stocks procesados de hoja principal', ['count' => $sheetStocks->count()]);
        $stocks = $stocks->merge($sheetStocks);
        
        // Procesar la hoja de plazos fijos si existe (buscar con diferentes variaciones de nombre)
        $plazosFijosSheetName = null;
        foreach ($allSheetNames as $sheetName) {
            $normalizedSheetName = strtolower(trim($sheetName));
            // Buscar variaciones: "plazos fijos", "plazo fijo", "plazosfijos", etc.
            if (preg_match('/plazo.*fijo/i', $normalizedSheetName)) {
                $plazosFijosSheetName = $sheetName;
                \Log::info('Hoja de plazos fijos encontrada', [
                    'original_name' => $sheetName,
                    'normalized' => $normalizedSheetName
                ]);
                break;
            }
        }
        
        if ($plazosFijosSheetName) {
            try {
                $plazosFijosWorksheet = $spreadsheet->getSheetByName($plazosFijosSheetName);
                \Log::info('Procesando hoja de plazos fijos', ['sheet_name' => $plazosFijosSheetName]);
                $plazosFijosStocks = $this->processMonthlyPlazosFijosSheet($plazosFijosWorksheet);
                \Log::info('Plazos fijos procesados', ['count' => $plazosFijosStocks->count()]);
                $stocks = $stocks->merge($plazosFijosStocks);
            } catch (\Exception $e) {
                \Log::error('Error al procesar hoja de plazos fijos', [
                    'sheet_name' => $plazosFijosSheetName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            \Log::warning('No se encontró hoja de plazos fijos', [
                'available_sheets' => $allSheetNames
            ]);
        }
        
        // Verificar que se procesaron datos
        if ($stocks->isEmpty()) {
            throw new \Exception("No se encontraron datos válidos en el archivo Excel.");
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
     * Procesar hoja de PLAZOS FIJOS con estructura especial
     * FILA 1: VACIA
     * FILA 2: ENCABEZADO DE TABLA
     * FILA 3: NÚMEROS DEL 1 AL 13 (índices de columnas)
     * FILA 4: ENCABEZADO CON LOS 13 CAMPOS
     * FILA 5 EN ADELANTE: VALORES
     */
    private function processPlazosFijosSheet(Worksheet $worksheet): Collection
    {
        $operations = collect();
        
        // Los datos empiezan en la fila 5 (índice 4) - saltando filas 1-4
        $startRow = 5;
        $maxRow = $worksheet->getHighestRow();
        
        for ($row = $startRow; $row <= $maxRow; $row++) {
            $operation = $this->extractPlazoFijoFromRow($worksheet, $row);
            
            if ($operation && $this->isValidPlazoFijo($operation)) {
                $operations->push($operation);
            }
        }
        
        return $operations;
    }
    
    /**
     * Extraer datos de un plazo fijo desde una fila específica
     * Campos: Código SSN tipo PF, BIC, CDF, Fecha constituc, Fecha vencimiento,
     * Código SSN Moneda Origen, Valor Nominal Moneda Origen, Valor Nominal Moneda Nacional,
     * Código SSN Afect, Tipo de Tasa, Tasa, Concretado con Tít Deuda Públ, Cód SSN TÍT Públ
     */
    private function extractPlazoFijoFromRow(Worksheet $worksheet, int $row): ?array
    {
        // Mapeo de columnas según los índices de la fila 3 (1-13)
        $tipoPf = $this->cleanValue($worksheet->getCell('A' . $row)->getValue());
        $bic = $this->cleanValue($worksheet->getCell('B' . $row)->getValue());
        $cdf = $this->cleanValue($worksheet->getCell('C' . $row)->getValue());
        $fechaConstitucion = $this->cleanValue($this->getCellValueAsString($worksheet, 'D' . $row));
        $fechaVencimiento = $this->cleanValue($this->getCellValueAsString($worksheet, 'E' . $row));
        $moneda = $this->cleanValue($worksheet->getCell('F' . $row)->getValue());
        $valorNominalOrigen = $this->cleanValue($worksheet->getCell('G' . $row)->getValue());
        $valorNominalNacional = $this->cleanValue($worksheet->getCell('H' . $row)->getValue());
        $codigoAfectacion = $this->cleanValue($worksheet->getCell('I' . $row)->getValue());
        $tipoTasa = $this->cleanValue($worksheet->getCell('J' . $row)->getValue());
        $tasa = $this->cleanValue($worksheet->getCell('K' . $row)->getValue());
        $tituloDeuda = $this->cleanValue($worksheet->getCell('L' . $row)->getValue());
        $codigoTitulo = $this->cleanValue($worksheet->getCell('M' . $row)->getValue());
        
        // Verificar si la fila tiene datos válidos
        if (empty($tipoPf) && empty($bic) && empty($cdf)) {
            return null;
        }
        
        // Convertir fechas de DD/MM/YYYY a YYYY-MM-DD
        $fechaConstitucion = $this->convertDate($fechaConstitucion);
        $fechaVencimiento = $this->convertDate($fechaVencimiento);
        
        // Convertir título deuda a booleano
        $tituloDeudaBool = $this->convertToBoolean($tituloDeuda);
        
        return [
            'tipo_operacion' => 'P',
            'tipo_pf' => $tipoPf,
            'bic' => $bic,
            'cdf' => $cdf,
            'fecha_constitucion' => $fechaConstitucion,
            'fecha_vencimiento' => $fechaVencimiento,
            'moneda' => $moneda,
            'valor_nominal_origen' => $valorNominalOrigen,
            'valor_nominal_nacional' => $valorNominalNacional,
            'codigo_afectacion' => $codigoAfectacion,
            'tipo_tasa' => $tipoTasa,
            'tasa' => $tasa,
            'titulo_deuda' => $tituloDeudaBool,
            'codigo_titulo' => $codigoTitulo,
            'row_number' => $row
        ];
    }
    
    /**
     * Validar si un plazo fijo es válido
     */
    private function isValidPlazoFijo(array $plazoFijo): bool
    {
        // Campos obligatorios que deben tener valor
        $requiredFields = ['tipo_pf', 'fecha_constitucion', 'fecha_vencimiento'];
        
        foreach ($requiredFields as $field) {
            if (empty($plazoFijo[$field])) {
                return false;
            }
        }
        
        return true;
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
     * Procesar hoja de PLAZOS FIJOS mensuales con estructura especial
     * FILA 1: ÍNDICES DEL 1 AL 18
     * FILA 2: ENCABEZADOS
     * FILA 3 EN ADELANTE: VALORES
     */
    private function processMonthlyPlazosFijosSheet(Worksheet $worksheet): Collection
    {
        $stocks = collect();
        
        // Los datos empiezan en la fila 3 (índice 2) - saltando filas 1-2
        $startRow = 3;
        $maxRow = $worksheet->getHighestRow();
        
        \Log::info('Procesando hoja de plazos fijos', [
            'start_row' => $startRow,
            'max_row' => $maxRow,
            'sheet_name' => $worksheet->getTitle()
        ]);
        
        $processedCount = 0;
        $validCount = 0;
        $invalidCount = 0;
        
        for ($row = $startRow; $row <= $maxRow; $row++) {
            $stock = $this->extractMonthlyPlazoFijoFromRow($worksheet, $row);
            $processedCount++;
            
            if ($stock) {
                if ($this->isValidMonthlyPlazoFijo($stock)) {
                    $stocks->push($stock);
                    $validCount++;
                } else {
                    $invalidCount++;
                    \Log::debug('Plazo fijo inválido en fila', [
                        'row' => $row,
                        'stock' => $stock
                    ]);
                }
            }
        }
        
        \Log::info('Procesamiento de plazos fijos completado', [
            'total_rows_processed' => $processedCount,
            'valid_stocks' => $validCount,
            'invalid_stocks' => $invalidCount,
            'total_stocks' => $stocks->count()
        ]);
        
        return $stocks;
    }
    
    /**
     * Extraer datos de un plazo fijo mensual desde una fila específica
     * Campos: Código SSN tipo PF, BIC, CDF, Fecha constituc, Fecha vencimiento,
     * Código SSN Moneda Origen, Valor Nominal Moneda Origen, Valor Nominal Moneda Nacional,
     * Ente Emisor de Gpo Econ, Libre Disponib, En custodia, Código SSN Afect,
     * Tipo de Tasa, Tasa, Concretado con Tít Deuda Públ, Cód SSN TÍT Públ,
     * Valor Contable, Financiera
     */
    private function extractMonthlyPlazoFijoFromRow(Worksheet $worksheet, int $row): ?array
    {
        // Mapeo de columnas según los índices de la fila 1 (1-18)
        $tipoPf = $this->cleanValue($worksheet->getCell('A' . $row)->getValue());
        $bic = $this->cleanValue($worksheet->getCell('B' . $row)->getValue());
        $cdf = $this->cleanValue($worksheet->getCell('C' . $row)->getValue());
        $fechaConstitucion = $this->cleanValue($this->getCellValueAsString($worksheet, 'D' . $row));
        $fechaVencimiento = $this->cleanValue($this->getCellValueAsString($worksheet, 'E' . $row));
        $moneda = $this->cleanValue($worksheet->getCell('F' . $row)->getValue());
        $valorNominalOrigen = $this->cleanValue($worksheet->getCell('G' . $row)->getValue());
        $valorNominalNacional = $this->cleanValue($worksheet->getCell('H' . $row)->getValue());
        $emisorGrupoEconomico = $this->cleanValue($worksheet->getCell('I' . $row)->getValue());
        $libreDisponibilidad = $this->cleanValue($worksheet->getCell('J' . $row)->getValue());
        $enCustodia = $this->cleanValue($worksheet->getCell('K' . $row)->getValue());
        $codigoAfectacion = $this->cleanValue($worksheet->getCell('L' . $row)->getValue());
        $tipoTasa = $this->cleanValue($worksheet->getCell('M' . $row)->getValue());
        $tasa = $this->cleanValue($worksheet->getCell('N' . $row)->getValue());
        $tituloDeuda = $this->cleanValue($worksheet->getCell('O' . $row)->getValue());
        $codigoTitulo = $this->cleanValue($worksheet->getCell('P' . $row)->getValue());
        $valorContable = $this->cleanValue($worksheet->getCell('Q' . $row)->getValue());
        $financiera = $this->cleanValue($worksheet->getCell('R' . $row)->getValue());
        
        // Log de primera fila para debugging
        if ($row === 3) {
            \Log::info('Primera fila de plazos fijos', [
                'row' => $row,
                'tipo_pf' => $tipoPf,
                'bic' => $bic,
                'cdf' => $cdf,
                'fecha_constitucion' => $fechaConstitucion,
                'fecha_vencimiento' => $fechaVencimiento
            ]);
        }
        
        // Verificar si la fila tiene datos válidos
        if (empty($tipoPf) && empty($bic) && empty($cdf)) {
            return null;
        }
        
        // Convertir fechas directamente a formato DDMMYYYY para la base de datos
        $fechaConstitucion = $this->convertDateToDDMMYYYY($fechaConstitucion);
        $fechaVencimiento = $this->convertDateToDDMMYYYY($fechaVencimiento);
        
        // Log después de conversión para debugging
        if ($row === 3) {
            \Log::info('Fechas después de conversión', [
                'row' => $row,
                'fecha_constitucion_original' => $this->cleanValue($this->getCellValueAsString($worksheet, 'D' . $row)),
                'fecha_constitucion_convertida' => $fechaConstitucion,
                'fecha_vencimiento_original' => $this->cleanValue($this->getCellValueAsString($worksheet, 'E' . $row)),
                'fecha_vencimiento_convertida' => $fechaVencimiento
            ]);
        }
        
        // Convertir valores booleanos
        $emisorGrupoEconomicoBool = $this->convertToBoolean($emisorGrupoEconomico);
        $libreDisponibilidadBool = $this->convertToBoolean($libreDisponibilidad);
        $enCustodiaBool = $this->convertToBoolean($enCustodia);
        $tituloDeudaBool = $this->convertToBoolean($tituloDeuda);
        $financieraBool = $this->convertToBoolean($financiera);
        
        // Generar nombre por defecto para plazos fijos (no viene en el Excel)
        $nombre = "PF {$tipoPf}";
        if ($cdf) {
            $nombre .= " - {$cdf}";
        }
        
        return [
            'nombre' => $nombre,
            'tipo' => 'P',
            'tipo_pf' => $tipoPf,
            'bic' => $bic,
            'cdf' => $cdf,
            'fecha_constitucion' => $fechaConstitucion,
            'fecha_vencimiento_pf' => $fechaVencimiento,
            'moneda' => $moneda,
            'valor_nominal_origen' => $valorNominalOrigen,
            'valor_nominal_nacional' => $valorNominalNacional,
            'emisor_grupo_economico' => $emisorGrupoEconomicoBool,
            'libre_disponibilidad' => $libreDisponibilidadBool,
            'en_custodia' => $enCustodiaBool,
            'codigo_afectacion' => $codigoAfectacion,
            'tipo_tasa' => $tipoTasa,
            'tasa' => $tasa,
            'titulo_deuda' => $tituloDeudaBool,
            'codigo_titulo' => $codigoTitulo,
            'valor_contable' => $valorContable,
            'financiera' => $financieraBool,
            'row_number' => $row
        ];
    }
    
    /**
     * Validar si un plazo fijo mensual es válido
     */
    private function isValidMonthlyPlazoFijo(array $plazoFijo): bool
    {
        // Campos obligatorios que deben tener valor
        $requiredFields = ['tipo_pf', 'fecha_constitucion', 'fecha_vencimiento_pf'];
        
        foreach ($requiredFields as $field) {
            if (empty($plazoFijo[$field])) {
                return false;
            }
        }
        
        return true;
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
     * Obtener valor de celda como string. Para celdas de fecha usa el serial de Excel
     * y lo convierte a Y-m-d, así la fecha no depende del formato de celda (DD-MM vs MM-DD).
     */
    private function getCellValueAsString(Worksheet $worksheet, string $cellAddress)
    {
        $cell = $worksheet->getCell($cellAddress);
        
        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC &&
            \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            try {
                $serial = $cell->getValue();
                if (is_numeric($serial)) {
                    $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial);
                    return $dateTime->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::debug("Error al convertir fecha Excel en celda {$cellAddress}: " . $e->getMessage());
            }
        }
        
        $value = $cell->getValue();
        return $value !== null ? (string) $value : '';
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
                'plazos_fijos' => $operations->where('tipo_operacion', 'P')->count(),
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
            'valor_total_contable' => $stocks->sum(fn ($s) => (float) ($s['valor_contable'] ?? 0)),
            'en_custodia' => $stocks->where('en_custodia', true)->count(),
            'financiera' => $stocks->where('financiera', true)->count(),
        ];
    }
    
    /**
     * Convertir boolean a formato SSN (1/0)
     */
    private function convertToSSNBoolean($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        if (is_string($value)) {
            $lowerValue = strtolower(trim($value));
            if (in_array($lowerValue, ['true', '1', 'yes', 'si', 'sí'])) {
                return 1;
            }
            if (in_array($lowerValue, ['false', '0', 'no'])) {
                return 0;
            }
        }
        
        return 0; // Default
    }

    /**
     * Convertir fecha a formato DDMMYYYY para la base de datos
     */
    private function convertDateToDDMMYYYY(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        // Limpiar el valor
        $date = trim($date);
        
        try {
            // Si ya está en formato DDMMYYYY, devolverlo tal como está
            if (preg_match('/^\d{8}$/', $date)) {
                return $date;
            }
            
            // Si está en formato YYYY-MM-DD, convertirlo a DDMMYYYY
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
                if ($dateObj) {
                    return $dateObj->format('dmY');
                }
            }
            
            // Si está en formato DD/MM/YYYY (con o sin ceros a la izquierda en día/mes)
            // Ejemplos: "10/9/2023", "01/01/2023", "1/1/2023"
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return $day . $month . $year;
            }
            
            // Intentar parsear con DateTime usando formato flexible
            $formats = [
                'd/m/Y',    // 10/09/2023
                'j/n/Y',    // 10/9/2023 (sin ceros a la izquierda)
                'd-m-Y',    // 10-09-2023
                'Y-m-d',    // 2023-09-10
            ];
            
            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj) {
                    return $dateObj->format('dmY');
                }
            }
            
            // Si es un número de Excel, convertirlo primero a YYYY-MM-DD y luego a DDMMYYYY
            if (is_numeric($date)) {
                try {
                    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                    $excelDate->setTimezone(new \DateTimeZone('UTC'));
                    $excelDate->modify('+1 day');
                    return $excelDate->format('dmY');
                } catch (\Exception $e) {
                    \Log::error("Error al convertir número Excel a DDMMYYYY: " . $e->getMessage(), [
                        'date' => $date
                    ]);
                    return null;
                }
            }
            
            \Log::warning("No se pudo convertir fecha a DDMMYYYY", [
                'date' => $date,
                'type' => gettype($date)
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error("Error al convertir fecha a DDMMYYYY: " . $e->getMessage(), [
                'date' => $date,
                'error' => $e->getTraceAsString()
            ]);
            return null;
        }
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
            
            // Si ya está en formato DDMMYYYY, devolverlo tal como está
            if (preg_match('/^\d{8}$/', $date)) {
                return $date;
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
                // Construir objeto según el tipo de stock
                if ($stock['tipo'] === 'P') {
                    // Plazos Fijos tienen estructura completamente diferente
                    // Las fechas ya están en formato DDMMYYYY en la base de datos
                    $fechaConstitucion = $stock['fecha_constitucion'] ?? '';
                    $fechaVencimiento = $stock['fecha_vencimiento_pf'] ?? '';
                    
                    // Si no están en formato DDMMYYYY, convertirlas
                    if ($fechaConstitucion && !preg_match('/^\d{8}$/', $fechaConstitucion)) {
                        $fechaConstitucion = $this->formatDateForSSN($fechaConstitucion);
                    }
                    if ($fechaVencimiento && !preg_match('/^\d{8}$/', $fechaVencimiento)) {
                        $fechaVencimiento = $this->formatDateForSSN($fechaVencimiento);
                    }
                    
                    $ssnStock = [
                        'tipo' => 'P',
                        'tipoPF' => $stock['tipo_pf'] ?? '',
                        'bic' => $stock['bic'] ?? '',
                        'cdf' => $stock['cdf'] ?? '',
                        'fechaConstitucion' => $fechaConstitucion,
                        'fechaVencimiento' => $fechaVencimiento,
                        'moneda' => $stock['moneda'] ?? '',
                        'valorNominalOrigen' => isset($stock['valor_nominal_origen']) ? (float) $stock['valor_nominal_origen'] : 0,
                        'valorNominalNacional' => isset($stock['valor_nominal_nacional']) ? (float) $stock['valor_nominal_nacional'] : 0,
                        'emisorGrupoEconomico' => $this->convertToSSNBoolean($stock['emisor_grupo_economico'] ?? false),
                        'libreDisponibilidad' => $this->convertToSSNBoolean($stock['libre_disponibilidad'] ?? false),
                        'enCustodia' => $this->convertToSSNBoolean($stock['en_custodia'] ?? false),
                        'codigoAfectacion' => $stock['codigo_afectacion'] ?? '',
                        'tipoTasa' => $stock['tipo_tasa'] ?? '',
                        'tasa' => isset($stock['tasa']) ? (float) $stock['tasa'] : 0,
                        'tituloDeuda' => $this->convertToSSNBoolean($stock['titulo_deuda'] ?? false),
                        'codigoTitulo' => $stock['codigo_titulo'] ?? '',
                        'valorContable' => isset($stock['valor_contable']) ? (float) $stock['valor_contable'] : 0,
                        'financiera' => $this->convertToSSNBoolean($stock['financiera'] ?? false),
                    ];
                } else {
                    // Inversiones y Cheques tienen estructura común
                    // Validar que el stock tenga los campos mínimos requeridos
                    if (!isset($stock['tipo']) || !isset($stock['tipo_especie']) || !isset($stock['codigo_especie'])) {
                        \Log::warning("Stock {$index} no tiene campos mínimos requeridos", $stock);
                        continue;
                    }
                    
                    // Verificar si debe incluir campos de Pase VT
                    $tipoEspecie = strtoupper(trim($stock['tipo_especie'] ?? ''));
                    $tipoValuacion = strtoupper(trim($stock['tipo_valuacion'] ?? ''));
                    $debeIncluirPaseVT = in_array($tipoEspecie, ['TP', 'ON']) && $tipoValuacion === 'T';

                    $ssnStock = [
                        'tipo' => $stock['tipo'],
                        'tipoEspecie' => $stock['tipo_especie'],
                        'codigoEspecie' => $stock['codigo_especie'],
                        'cantidadDevengadoEspecies' => (float) ($stock['cantidad_devengado_especies'] ?? 0),
                        'cantidadPercibidoEspecies' => (float) ($stock['cantidad_percibido_especies'] ?? 0),
                        'codigoAfectacion' => $stock['codigo_afectacion'] ?? '',
                        'tipoValuacion' => $stock['tipo_valuacion'] ?? '',
                        'conCotizacion' => $this->convertToSSNBoolean($stock['con_cotizacion'] ?? false),
                        'libreDisponibilidad' => $this->convertToSSNBoolean($stock['libre_disponibilidad'] ?? false),
                        'emisorGrupoEconomico' => $this->convertToSSNBoolean($stock['emisor_grupo_economico'] ?? false),
                        'emisorArtRet' => $this->convertToSSNBoolean($stock['emisor_art_ret'] ?? false),
                        'previsionDesvalorizacion' => $stock['prevision_desvalorizacion'] !== null && $stock['prevision_desvalorizacion'] !== '' ? (float) $stock['prevision_desvalorizacion'] : 0,
                        'valorContable' => $stock['valor_contable'] !== null && $stock['valor_contable'] !== '' ? (float) $stock['valor_contable'] : 0,
                        'enCustodia' => $this->convertToSSNBoolean($stock['en_custodia'] ?? false),
                        'financiera' => $this->convertToSSNBoolean($stock['financiera'] ?? false),
                        'valorFinanciero' => $stock['valor_financiero'] !== null && $stock['valor_financiero'] !== '' ? (float) $stock['valor_financiero'] : 0,
                    ];

                    // Agregar campos de Pase VT solo si corresponde
                    if ($debeIncluirPaseVT) {
                        $ssnStock['fechaPaseVt'] = $this->formatDateForSSN($stock['fecha_pase_vt'] ?? '');
                        $ssnStock['precioPaseVt'] = $stock['precio_pase_vt'] !== null && $stock['precio_pase_vt'] !== '' ? (float) $stock['precio_pase_vt'] : 0;
                    } else {
                        $ssnStock['fechaPaseVt'] = '';
                        $ssnStock['precioPaseVt'] = '';
                    }
                }
                
                // Log para debugging
                \Log::info('Stock procesado para SSN', [
                    'stock_index' => $index,
                    'tipo' => $stock['tipo'] ?? 'unknown',
                ]);
                
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
            // Construir objeto base según el tipo de operación
            if ($operation['tipo_operacion'] === 'P') {
                // Plazos Fijos tienen estructura completamente diferente
                $ssnOperation = [
                    'tipoOperacion' => $operation['tipo_operacion'],
                    'tipoPF' => $operation['tipo_pf'] ?? '',
                    'bic' => $operation['bic'] ?? '',
                    'cdf' => $operation['cdf'] ?? '',
                    'fechaConstitucion' => $this->formatDateForSSN($operation['fecha_constitucion'] ?? ''),
                    'fechaVencimiento' => $this->formatDateForSSN($operation['fecha_vencimiento'] ?? ''),
                    'moneda' => $operation['moneda'] ?? '',
                    'valorNominalOrigen' => isset($operation['valor_nominal_origen']) ? (float) $operation['valor_nominal_origen'] : 0,
                    'valorNominalNacional' => isset($operation['valor_nominal_nacional']) ? (float) $operation['valor_nominal_nacional'] : 0,
                    'codigoAfectacion' => $operation['codigo_afectacion'] ?? '',
                    'tipoTasa' => $operation['tipo_tasa'] ?? '',
                    'tasa' => isset($operation['tasa']) ? (float) $operation['tasa'] : 0,
                    'tituloDeuda' => isset($operation['titulo_deuda']) && $operation['titulo_deuda'] ? '1' : '0',
                    'codigoTitulo' => $operation['codigo_titulo'] ?? '',
                ];
            } else {
                // Compras, Ventas y Canjes tienen estructura común
                $ssnOperation = [
                    'tipoOperacion' => $operation['tipo_operacion'],
                    'tipoEspecie' => $operation['tipo_especie'] ?? '',
                    'codigoEspecie' => $operation['codigo_especie'] ?? '',
                    'cantEspecies' => isset($operation['cant_especies']) ? (float) $operation['cant_especies'] : 0,
                    'codigoAfectacion' => $operation['codigo_afectacion'] ?? '',
                    'tipoValuacion' => $operation['tipo_valuacion'] ?? '',
                    'fechaMovimiento' => $this->formatDateForSSN($operation['fecha_movimiento'] ?? ''),
                    'fechaLiquidacion' => $this->formatDateForSSN($operation['fecha_liquidacion'] ?? ''),
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
                        $fechaPaseVT = $this->formatDateForSSN($operation['fecha_pase_vt'] ?? '');
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