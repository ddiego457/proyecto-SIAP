
$(document).ready(function() {
    
    const tabla = $('#tablaMain').DataTable({
        ajax:{
            url: "?url=requerimiento&type=main",
            method: 'POST',
            data: {getAll: true},
            datasrc: ''
        },
        columns: [
            {data: 'dependencia'},
            {data: 'partida'},
            {data: 'producto'},
            {data:'Ene',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Feb' ,
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Mar',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Abr',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'May',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Jun',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Jul',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Ago',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Sep',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Oct',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Nov',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Dic',
            render: function(data,type,row){return loadData(data, row)}},
            {data: 'Total_Cantidad'},
            {data: 'precio_unit_usd'},
            {data: 'total_usd'},
            {data: 'total_bs'}
        ],
        autowidth: false,
        language: {
            // url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });
    function loadData(data,row){
                return `<input type="number" 
                                   style='
                                    width: 55px;
                                    padding: 8px 5px;
                                    font-size: 14px;
                                    text-align: center;
                                    border: 1px solid #ccc;
                                    border-radius: 6px;
                                    box-sizing: border-box;
                                    outline: none;
                                    transition: border-color 0.2s ease;
                                    ' 
                                   min="0" 
                                   value="${data}" 
                                   data-id="${row.id}"
                                   onchange="actualizarValor(this)"></input>`;
            }

$(document).ready(function() {
const currentURL = "?url=requerimiento&type=register";

// 1. Inicializar DataTables de Registro
const tablaRegistro = $('#tabla-registro').DataTable({
    serverSide: false,
    ajax: {
        url: currentURL,
        type: "POST",
        data: function(d) {
            d.getProductos = true;
            // Enviamos dinámicamente el valor actual del input hidden del formulario
            d.partida = $('#partida_actual').val();
        }
    },
    columns: [
        { data: 'nom_item' },
        { data: "ene", render: function(data, type, row) { return crearInput(row.id_item, 1, data); }},
        { data: "feb", render: function(data, type, row) { return crearInput(row.id_item, 2, data); }},
        { data: "mar", render: function(data, type, row) { return crearInput(row.id_item, 3, data); }},
        { data: "abr", render: function(data, type, row) { return crearInput(row.id_item, 4, data); }},
        { data: "may", render: function(data, type, row) { return crearInput(row.id_item, 5, data); }},
        { data: "jun", render: function(data, type, row) { return crearInput(row.id_item, 6, data); }},
        { data: "jul", render: function(data, type, row) { return crearInput(row.id_item, 7, data); }},
        { data: "ago", render: function(data, type, row) { return crearInput(row.id_item, 8, data); }},
        { data: "sep", render: function(data, type, row) { return crearInput(row.id_item, 9, data); }},
        { data: "oct", render: function(data, type, row) { return crearInput(row.id_item, 10, data); }},
        { data: "nov", render: function(data, type, row) { return crearInput(row.id_item, 11, data); }},
        { data: "dic", render: function(data, type, row) { return crearInput(row.id_item, 12, data); }}
    ],
    ordering: false
});

function crearInput(id_item, mes, valor_actual) {
    return '<input type="number" name="cantidades['+id_item+']['+mes+']" value="'+valor_actual+'" min="0" class="form-control form-control-sm text-center" style="width: 60px;">';
}

// 2. Evento: Guardar y Siguiente vía AJAX
$('#btn-guardar').click(function() {
    var btn = $(this);
    btn.prop('disabled', true).text('Guardando partida...');

    // Convertimos todos los inputs distribuidos en las páginas de la tabla a un Objeto/String serializado
    let datosInputs = tablaRegistro.$('input').serialize(); 
    let idReq = $('#id_req').val();
    let partidaActual = $('#partida_actual').val();

    // Estructuramos la data final combinando los campos fijos y los dinámicos
    let dataEnviar = datosInputs + '&guardarPartida=true&id_req=' + idReq + '&partida_actual=' + partidaActual;

    $.ajax({
        url: currentURL,
        type: 'POST',
        data: dataEnviar, // Enviamos el string estructurado directamente
        dataType: 'json',
        success: function(respuesta) {
            if(respuesta.status === 'success') {
                // Actualizamos el id_req maestro con el generado o existente
                $('#id_req').val(respuesta.id_req);
                
                if(respuesta.siguiente_partida === 'FINAL') {
                    // Si ya no hay más partidas, ocultamos el botón de avanzar y mostramos el de envío definitivo
                    $('#btn-guardar').addClass('d-none');
                    alert("Todas las partidas han sido guardadas en borrador de manera exitosa.");
                    window.location.href = "?url=requerimiento&type=main";
                } else {
                    // Cambiamos de partida en la interfaz
                    $('#partida_actual').val(respuesta.siguiente_partida);
                    $('#titulo-partida').text(respuesta.siguiente_partida);
                    
                    // Recargamos la tabla. Al llamarse, leerá la nueva partida del input hidden
                    tablaRegistro.ajax.reload(null, false);
                }
            } else {
                alert("Error en el servidor: " + respuesta.message);
            }
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
            alert("Ocurrió un error en la comunicación de datos.");
        },
        complete: function() {
            if($('#partida_actual').val() !== 'FINAL') {
                btn.prop('disabled', false).text('Guardar y Avanzar a Siguiente Partida');
            }
        }
    });
});
});
})
