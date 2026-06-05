// ===== MODALES =====
function abrirModal(id){
    const modal = document.getElementById(id);
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Evita scroll detrás del modal
}

function cerrarModal(id){
    const modal = document.getElementById(id);
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar al hacer clic fuera del contenido
window.onclick = function(event){
    ['modalCultivo','modalLote','modalPlaga'].forEach(id=>{
        const modal = document.getElementById(id);
        if(event.target == modal) cerrarModal(id);
    });
}