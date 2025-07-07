@props([
    'title' => '',
    'subtitle' => '',
    'type' => 'default',
    'status' => null,
    'showLogo' => false,
    'class' => ''
])

@php
    use App\Helpers\ColorHelper;
    
    $cardClass = 'insurance-card ' . $class;
    $titleClass = 'titulo';
    $statusClass = $status ? ColorHelper::getStatusClass($status) : '';
    $typeClass = $type !== 'default' ? ColorHelper::getInsuranceTypeClass($type) : '';
@endphp

<div class="{{ $cardClass }}" style="{{ ColorHelper::getBorderStyle() }}">
    @if($showLogo)
        <div class="logo" style="margin-bottom: {{ ColorHelper::getSpacing('md') }};">
            <img src="{{ asset('logo.png') }}" alt="Esencia Seguros" style="max-width: 200px;">
        </div>
    @endif
    
    @if($title)
        <h3 class="{{ $titleClass }}" style="{{ ColorHelper::getTextColorStyle('text', 'primary') }}">
            {{ $title }}
        </h3>
    @endif
    
    @if($subtitle)
        <p style="{{ ColorHelper::getTextColorStyle('text', 'secondary') }}; margin-bottom: {{ ColorHelper::getSpacing('md') }};">
            {{ $subtitle }}
        </p>
    @endif
    
    @if($type !== 'default')
        <span class="{{ $typeClass }}" style="margin-bottom: {{ ColorHelper::getSpacing('sm') }}; display: inline-block;">
            {{ config("esencia.insurance.types.{$type}", ucfirst($type)) }}
        </span>
    @endif
    
    @if($status)
        <div style="margin-top: {{ ColorHelper::getSpacing('sm') }};">
            <span class="{{ $statusClass }}">
                {{ config("esencia.insurance.status.{$status}", ucfirst($status)) }}
            </span>
        </div>
    @endif
    
    <div class="card-content" style="margin-top: {{ ColorHelper::getSpacing('md') }};">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="card-footer" style="margin-top: {{ ColorHelper::getSpacing('lg') }}; padding-top: {{ ColorHelper::getSpacing('md') }}; {{ ColorHelper::getBorderStyle('light', '1px 0 0 0') }}">
            {{ $footer }}
        </div>
    @endif
</div> 