
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
                return `<input id="info" type="number" 
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
        // 1. Inicializar DataTables
        const tablaRegistro = $('#tabla-registro').DataTable({
            getProductos: true,
            serverSide: false, // Procesamos la paginación en el cliente
            ajax: {
                url: currentURL,
                type: "POST",
                // Enviamos el id_req y la partida actual cada vez que la tabla pide datos
                data: {getProductos: true}
            },
            columns: [
                { data: 'nom_item' }, // Nombre del producto
                // Columnas de los 12 meses. Usamos render para crear los inputs.
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
            language: {
                // url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            },
            ordering: false // Desactivado para no desordenar los inputs al hacer clic
        });
    
        // Función auxiliar para imprimir el HTML del input multidimensional
        function crearInput(id_item, mes, valor_actual) {
            // valor_actual vendrá con 0 si no hay registro, o con el número si ya estaba en borrador
            return '<input type="number" name="cantidades['+id_item+']['+mes+']" value="'+valor_actual+'" min="0" class="form-control form-control-sm text-center" style="width: 70px;">';
        }
    
        // 2. Evento: Guardar y Siguiente vía AJAX
        $('#btn-guardar').click(function() {
            // Desactivar el botón para evitar dobles clics
            var btn = $(this);
            btn.prop('disabled', true).text('Guardando...');
    
            // ¡EL TRUCO!: Capturar TODOS los inputs de la tabla, no solo los de la página 1
            var datosInputs = tablaRegistro.$('input').serialize(); 
            var idReq = $('#id_req').val();
            var partidaActual = $('#partida_actual').val();
    
            // Concatenar los datos del formulario
            var datosFinales = datosInputs + '&id_req=' + idReq + '&partida=' + partidaActual;
    
            $.ajax({
                url: currentURL,
                type: 'POST',
                data: {datosFinales: true},
                dataType: 'json',
                success: function(respuesta) {
                    if(respuesta.status === 'success') {
                        // Actualizar la interfaz a la siguiente partida (ej: de 401 a 402)
                        $('#partida_actual').val(respuesta.siguiente_partida);
                        $('#titulo-partida').text(respuesta.siguiente_partida);
                        
                        // Recargar DataTables para que traiga los ítems de la nueva partida
                        tablaRegistro.ajax.reload(null, false);
                    } else {
                        alert("Error al guardar: " + respuesta.message);
                    }
                },
                error: function() {
                    alert("Ocurrió un error en la conexión.");
                },
                complete: function() {
                    btn.prop('disabled', false).text('Guardar y Avanzar a Siguiente Partida');
                }
            });
        });
    });
});

