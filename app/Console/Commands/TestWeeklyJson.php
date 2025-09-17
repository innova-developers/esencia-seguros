<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Models\Presentation;
use App\Domain\Models\WeeklyOperation;

class TestWeeklyJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-weekly-json {presentation_id? : ID de la presentación a probar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test weekly presentation JSON generation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Weekly Presentation JSON Generation...');
        
        $presentationId = $this->argument('presentation_id');
        
        if ($presentationId) {
            $this->testSpecificPresentation($presentationId);
        } else {
            $this->testAllPresentations();
        }
        
        return 0;
    }
    
    private function testSpecificPresentation($presentationId)
    {
        $this->info("📋 Testing presentation ID: {$presentationId}");
        
        try {
            $presentation = Presentation::find($presentationId);
            
            if (!$presentation) {
                $this->error("❌ Presentation not found: {$presentationId}");
                return;
            }
            
            $this->line("Presentation found:");
            $this->line("  ID: {$presentation->id}");
            $this->line("  Cronograma: {$presentation->cronograma}");
            $this->line("  Tipo: {$presentation->tipo_entrega}");
            $this->line("  Estado: {$presentation->estado}");
            
            // Test individual operations
            $this->testOperations($presentation);
            
            // Test full JSON
            $this->testFullJson($presentation);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
    
    private function testAllPresentations()
    {
        $this->info('📋 Testing all weekly presentations...');
        
        $presentations = Presentation::where('tipo_entrega', 'SEMANAL')
            ->with('weeklyOperations')
            ->get();
            
        $this->line("Found {$presentations->count()} weekly presentations");
        
        foreach ($presentations as $presentation) {
            $this->line("\n--- Presentation ID: {$presentation->id} ---");
            $this->line("Cronograma: {$presentation->cronograma}");
            $this->line("Estado: {$presentation->estado}");
            $this->line("Operations: {$presentation->weeklyOperations->count()}");
            
            if ($presentation->weeklyOperations->count() > 0) {
                $this->testOperations($presentation);
            }
        }
    }
    
    private function testOperations($presentation)
    {
        $this->info('🔧 Testing individual operations...');
        
        foreach ($presentation->weeklyOperations as $index => $operation) {
            $this->line("\nOperation " . ($index + 1) . ":");
            $this->line("  ID: {$operation->id}");
            $this->line("  Tipo: {$operation->tipo_operacion}");
            $this->line("  Especie: {$operation->tipo_especie} - {$operation->codigo_especie}");
            $this->line("  Fecha Movimiento (DB): '{$operation->fecha_movimiento}'");
            $this->line("  Fecha Liquidación (DB): '{$operation->fecha_liquidacion}'");
            
            // Test formatDateForSSN method
            $reflection = new \ReflectionClass($operation);
            $method = $reflection->getMethod('formatDateForSSN');
            $method->setAccessible(true);
            
            $formattedMovimiento = $method->invoke($operation, $operation->fecha_movimiento);
            $formattedLiquidacion = $method->invoke($operation, $operation->fecha_liquidacion);
            
            $this->line("  Fecha Movimiento (SSN): '{$formattedMovimiento}'");
            $this->line("  Fecha Liquidación (SSN): '{$formattedLiquidacion}'");
            
            // Test getSsnJson method
            $ssnJson = $operation->getSsnJson();
            $this->line("  SSN JSON fechaMovimiento: '{$ssnJson['fechaMovimiento']}'");
            $this->line("  SSN JSON fechaLiquidacion: '{$ssnJson['fechaLiquidacion']}'");
        }
    }
    
    private function testFullJson($presentation)
    {
        $this->info('📄 Testing full JSON generation...');
        
        try {
            $json = $presentation->getSsnJson();
            
            $this->line("Full JSON:");
            $this->line(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Check specific fields
            if (isset($json['operaciones']) && count($json['operaciones']) > 0) {
                $firstOp = $json['operaciones'][0];
                $this->line("\nFirst operation fields:");
                $this->line("  fechaMovimiento: '{$firstOp['fechaMovimiento']}'");
                $this->line("  fechaLiquidacion: '{$firstOp['fechaLiquidacion']}'");
                
                if (empty($firstOp['fechaMovimiento']) || empty($firstOp['fechaLiquidacion'])) {
                    $this->error("❌ Empty date fields detected!");
                } else {
                    $this->info("✅ Date fields are properly formatted");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error generating JSON: " . $e->getMessage());
        }
    }
}
