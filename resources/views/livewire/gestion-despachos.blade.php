<div>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-paper-plane me-2"></i> Despachos a Sedes
            </h3>
        </div>

        <div class="card-body">
            <div class="callout callout-info">
                <h5 class="mb-2">
                    <i class="fas fa-circle-info me-2"></i> Módulo en preparación
                </h5>
                <p class="mb-0">
                    Aquí se registrarán y consultarán los despachos de inventario hacia
                    las sedes y puntos de consumo. Mientras tanto, puede administrar las
                    sedes, los productos y las entradas de mercancía desde sus módulos.
                </p>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <a href="{{ route('sedes.index') }}" class="text-decoration-none text-body">
                        <div class="info-box">
                            <span class="info-box-icon text-bg-primary">
                                <i class="fas fa-location-dot"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Sedes</span>
                                <span class="info-box-number">Puntos de consumo</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('productos.index') }}" class="text-decoration-none text-body">
                        <div class="info-box">
                            <span class="info-box-icon text-bg-success">
                                <i class="fas fa-boxes-stacked"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Productos</span>
                                <span class="info-box-number">Inventario disponible</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('lotes.index') }}" class="text-decoration-none text-body">
                        <div class="info-box">
                            <span class="info-box-icon text-bg-info">
                                <i class="fas fa-arrow-right-to-bracket"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Lotes</span>
                                <span class="info-box-number">Entradas de mercancía</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
