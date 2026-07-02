$(document).ready(function() {

    // =========================================================================
    // 1. LÓGICA DE LA TABLA PRINCIPAL (VISTA DE CONSOLIDADO Y ACTUALIZACIÓN)
    // =========================================================================
    
    // Inicializamos la tabla principal solo si existe en el DOM
    if ($('#tablaMain').length > 0) {
        const tabla = $('#tablaMain').DataTable({
            ajax: {
                url: "?url=requerimiento&type=main",
                method: 'POST',
                data: { getAll: true },
                // Extraemos el id_req de forma dinámica cuando llegan los datos del servidor
                dataSrc: function(json) {
                    if (json.data && json.data.length > 0) {
                        for (var i = 0; i < json.data.length; i++) {
                            if (json.data[i].id_req && json.data[i].id_req > 0) {
                                $('#id_req').val(json.data[i].id_req);
                                break; 
                            }
                        }
                    }
                    else{
                        $('#contenedor-acciones').hide();
                    }
                    return json.data;
                }
            },
            columns: [
                { data: 'dependencia' },
                { data: 'partida' },
                { data: 'producto' },
                { data: 'Ene', render: function(data, type, row) { return loadData(data, row, 1); }},
                { data: 'Feb', render: function(data, type, row) { return loadData(data, row, 2); }},
                { data: 'Mar', render: function(data, type, row) { return loadData(data, row, 3); }},
                { data: 'Abr', render: function(data, type, row) { return loadData(data, row, 4); }},
                { data: 'May', render: function(data, type, row) { return loadData(data, row, 5); }},
                { data: 'Jun', render: function(data, type, row) { return loadData(data, row, 6); }},
                { data: 'Jul', render: function(data, type, row) { return loadData(data, row, 7); }},
                { data: 'Ago', render: function(data, type, row) { return loadData(data, row, 8); }},
                { data: 'Sep', render: function(data, type, row) { return loadData(data, row, 9); }},
                { data: 'Oct', render: function(data, type, row) { return loadData(data, row, 10); }},
                { data: 'Nov', render: function(data, type, row) { return loadData(data, row, 11); }},
                { data: 'Dic', render: function(data, type, row) { return loadData(data, row, 12); }},
                { data: 'Total_Cantidad'},
                { data: 'precio_unit_usd', visible: esAdmin  },
                { data: 'total_usd', visible: esAdmin  },
                { data: 'total_bs', visible: esAdmin  }
            
            ],
            order: [[15, 'desc']],
            autowidth: false,
            language: {
                url: "assets/js/DataTables/spanish.json"
            }
        });

        function loadData(data, row, mes) {
            return `<input type="number" 
                           style="width: 55px; padding: 8px 5px; font-size: 14px; text-align: center; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; outline: none; transition: border-color 0.2s ease;" 
                           name="cantidades[${row.id_item}][${mes}]"
                           min="0" 
                           value="${data}">`;
        }
        console.log(esAdmin);
        console.log();
        $('#btn-enviar-final').prop('disabled', true);

        // Detectar cualquier cambio en los inputs para habilitar el botón de envío
        $('#tablaMain').on('input', 'input[type="number"]', function() {
            $('#btn-enviar-final').prop('disabled', false);
        });

        // Forzar actualización del id_req si el usuario hace clic directo en "Modificar"
        $('#tablaMain').on('click', '.btn-modificar', function() {
            var idClick = $(this).data('id');
            $('#id_req').val(idClick);
            $('#btn-enviar-final').prop('disabled', false);
        });

        // Enviar actualización matriz a la base de datos
        $('#btn-enviar-final').on('click', function(e) {
            e.preventDefault(); 

            var btn = $(this);
            var textoOriginal = btn.text();
            
            btn.prop('disabled', true).text('Guardando...');

            var datosInputs = tabla.$('input').serialize(); 
            var idReq = $('#id_req').val(); 
            var dataEnviar = datosInputs + '&actualizarMatriz=true&id_req=' + idReq;

            $.ajax({
                url: '?url=requerimiento&type=main',
                type: 'POST',
                data: dataEnviar,
                dataType: 'json',
                success: function(respuesta) {
                    if(respuesta.status === 'success') {
                        alert("Los datos han sido modificados y guardados exitosamente.");
                        tabla.ajax.reload(null, false); 
                        
                        // Volvemos a deshabilitar el botón hasta que haya un nuevo cambio
                        $('#btn-enviar-final').prop('disabled', true);
                    } else {
                        alert("Error en el servidor: " + respuesta.message);
                        btn.prop('disabled', false).text(textoOriginal);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Ocurrió un error de conexión al intentar actualizar los datos.");
                    btn.prop('disabled', false).text(textoOriginal);
                }
            });
        });
    }
    // Lógica para cambiar estado de 1 a 0
    $('#btn-cambiar-estado').on('click', function(e) {
        e.preventDefault();
        
        // Obtenemos el ID del requerimiento desde el input oculto (o donde lo tengas guardado)
        var idReq = $('#id_req').val();
        
        if(!idReq) {
            alert("No hay un requerimiento seleccionado.");
            return;
        }

        if(confirm("¿Estás seguro de enviar el requerimiento definitivamente?")) {
            $.ajax({
                url: '?url=requerimiento&type=main',
                type: 'POST',
                data: { 
                    cambiarEstado: true, 
                    id_req: idReq 
                },
                dataType: 'json',
                success: function(respuesta) {
                    if(respuesta.status === 'success') {
                        alert("Estado actualizado exitosamente.");
                        // Recargamos la tabla para que se reflejen los cambios visuales
                        $('#tablaMain').DataTable().ajax.reload(null, false);
                    } else {
                        alert("Error: " + respuesta.message);
                    }
                },
                error: function(e) {
                    alert("Ocurrió un error en la comunicación con el servidor.");
                    console.log(e);
                }
            });
        }
    });

    // =========================================================================
    // 2. LÓGICA DE LA TABLA DE REGISTRO (CREACIÓN DE NUEVOS REQUERIMIENTOS)
    // =========================================================================
    
    const currentURL = "?url=requerimiento&type=register";

    // Inicializamos tablaRegistro solo si estamos en la vista de registro
    if ($('#tabla-registro').length > 0) {
        
        const tablaRegistro = $('#tabla-registro').DataTable({
            serverSide: false,
            ajax: {
                url: currentURL,
                type: "POST",
                data: function(d) {
                    d.getProductos = true;
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

        // Guardar la partida y avanzar
        $('#btn-guardar').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Guardando partida...');

            let datosInputs = tablaRegistro.$('input').serialize(); 
            let idReq = $('#id_req').val();
            let partidaActual = $('#partida_actual').val();

            let dataEnviar = datosInputs + '&guardarPartida=true&id_req=' + idReq + '&partida_actual=' + partidaActual;

            $.ajax({
                url: currentURL,
                type: 'POST',
                data: dataEnviar,
                dataType: 'json',
                success: function(respuesta) {
                    if(respuesta.status === 'success') {
                        $('#id_req').val(respuesta.id_req);
                        
                        if(respuesta.siguiente_partida === 'FINAL') {
                            $('#btn-guardar').addClass('d-none');
                            alert("Todas las partidas han sido guardadas en borrador de manera exitosa.");
                            window.location.href = "?url=requerimiento&type=main";
                        } else {
                            $('#partida_actual').val(respuesta.siguiente_partida);
                            $('#titulo-partida').text(respuesta.siguiente_partida);
                            tablaRegistro.ajax.reload(null, false);
                        }
                    } else {
                        console.log("Error en el servidor: " + respuesta.message);
                        window.location.href = "?url=requerimiento&type=main";
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
    }
});