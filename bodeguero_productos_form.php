<?php
require_once 'conexion.php';
require_auth('Bodeguero');
?>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">Registrar Nuevo Producto</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="registrar_producto" value="1">
            <div class="mb-2">
                <label>Nombre:</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Tipo:</label>
                <input type="text" name="tipo" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Descripción:</label>
                <textarea name="descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-2">
                <label>Unidad de Medida:</label>
                <input type="text" name="unidad_medida" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Cantidad:</label>
                <input type="number" step="0.01" name="cantidad" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Fecha de Ingreso:</label>
                <input type="date" name="fecha_ingreso" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Fecha de Vencimiento:</label>
                <input type="date" name="fecha_vencimiento" class="form-control">
            </div>
            <div class="mb-2">
                <label>Observaciones:</label>
                <textarea name="observaciones" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrar Producto</button>
        </form>
    </div>
</div>
