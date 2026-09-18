# SIAP - Sistema Integrado de Anteproyecto Presupuestario

## 📋 Descripción General

**SIAP** es un sistema web desarrollado para automatizar, centralizar y optimizar la formulación y control del gasto fiscal anual en la Universidad Politécnica Territorial Andrés Eloy Blanco (UPTAEB). El sistema permite a las 109 dependencias universitarias gestionar sus solicitudes presupuestarias de manera eficiente, eliminando el procesamiento manual en hojas de cálculo y reduciendo significativamente los errores humanos.

---

## 🚀 Características Principales

- 📊 **Gestión de requerimientos presupuestarios** por dependencia y partida
- 📈 **Generación de reportes** en formato Excel y PDF
- 🗂️ **Administración de catálogos**: dependencias, ítems, partidas (401, 402, 403, 404, 407)
- 👥 **Gestión de responsables y proveedores** con contacto
- 📅 **Control de períodos y años fiscales**
- 🔐 **Autenticación de usuarios** con roles diferenciados
- 💰 **Cálculo automatizado de partidas** con tasa BCV actualizada

---

## 🛠️ Tecnologías Utilizadas

| Tecnología | Descripción |
|------------|-------------|
| **PHP** | Lenguaje de programación back-end (lado del servidor) |
| **MySQL** | Sistema de gestión de base de datos relacional |
| **Apache** | Servidor web HTTP |
| **HTML5** | Estructuración de contenido y plantillas |
| **CSS3** | Estilos y diseño de interfaz de usuario |
| **JavaScript** | Validaciones y dinamismo en el cliente |
| **POO** | Paradigma de programación orientada a objetos |

---

## 🧩 Patrón de Diseño: Modelo-Vista-Controlador (MVC)

El sistema sigue el patrón de arquitectura **MVC** para separar responsabilidades y facilitar el mantenimiento y escalabilidad.

### 📦 **Modelo**
- Gestiona la interacción con la **base de datos MySQL**
- Contiene la lógica de negocio y el acceso a datos
- Representa las entidades del sistema (Partidas, Dependencias, Requerimientos, etc.)
- Encapsula las reglas de validación y cálculos presupuestarios

### 🖥️ **Vista**
- Capa de presentación (interfaz de usuario)
- Muestra los datos al usuario de forma estructurada
- Utiliza HTML5, CSS3 y JavaScript para una experiencia intuitiva
- Se adapta al rol del usuario autenticado (dependencia o administrador)

### 🎮 **Controlador**
- Actúa como intermediario entre la Vista y el Modelo
- Recibe peticiones del usuario (HTTP requests)
- Procesa la entrada, invoca al Modelo y selecciona la Vista adecuada
- Gestiona el flujo de la aplicación (validaciones, redirecciones, mensajes de error)

### 🔄 **Flujo MVC en SIAP:**

Usuario → (Interactúa) → Vista (Formulario)
→ Controlador (Procesa petición)
→ Modelo (Consulta/actualiza BD)
→ Controlador (Prepara datos)
→ Vista (Muestra resultado)

## 📁 Estructura del Proyecto

SIAP/
├── app/
  ├── controllers/  Controladores (lógica de negocio)
  ├── models/  Modelos (acceso a BD y entidades)
  ├── views/  Plantillas HTML/CSS
  └── config/  Configuración (BD, rutas, sesiones)
├── assets/
  ├── css/  Estilos CSS3
  ├── js/  JavaScript cliente
  └── index.php Punto de entrada
└── README.md

---

## 📊 Modelo de Base de Datos

### Principales Tablas

| Tabla | Descripción |
|-------|-------------|
| `dependencias` | Unidades organizativas (109 en total) |
| `partidas` | Códigos presupuestarios (401, 402, 403, 404, 407) |
| `items_partida` | Productos, bienes y servicios solicitables |
| `requerimientos` | Solicitudes por dependencia y año fiscal |
| `detalle_req` | Detalle mensual de cantidades por ítem |
| `anio_fiscal` | Gestión de años presupuestarios |
| `periodos_entrega` | Fechas de apertura/cierre de recepción |
| `proveedores` / `contactos` | Catálogo de proveedores y teléfonos |
| `responsables` / `cargo_responsable` | Usuarios y su relación con dependencias |
| `tasa_bcv` | Histórico de tasas de cambio (USD/BS) |
| `roles` | Permisos de acceso (admin, dependencia) |

### 📌 Relaciones Clave

- `requerimientos` → `dependencias` (1:N)
- `detalle_req` → `items_partida` y `requerimientos` (N:1)
- `items_partida` → `partidas` y `proveedores` (N:1)
- `cargo_responsable` → `responsables` y `dependencias` (N:1)

---

## 🔐 Módulos Funcionales

1. **Gestión de Requerimientos** → Registro, envío, modificación e inhabilitación
2. **Generación de Reportes** → Exportación a Excel y PDF
3. **Gestión de Dependencias** → Crear, consultar, modificar e inhabilitar
4. **Gestión de Ítems** → Catálogo de productos con precios y proveedores
5. **Gestión de Partidas** → Códigos y descripción de partidas
6. **Gestión de Año Fiscal** → Creación y activación de años presupuestarios
7. **Gestión de Periodos** → Fechas de recepción de solicitudes
8. **Gestión de Proveedores** → Empresas y contactos asociados
9. **Gestión de Responsables** → Usuarios y cargos en dependencias

---

## ⚙️ Requisitos del Sistema

### Hardware
- Servidor con procesador **Pentium Dual Core 2.0 GHz o superior**
- **2 GB RAM** mínimo (recomendado 4 GB)
- **160 GB HDD** disponible
- Conexión a red local (LAN Ethernet)

### Software
- **Apache 2.4+** como servidor web
- **PHP 7.4+** (con extensiones: mysqli, mbstring, session)
- **MySQL 5.7+** o MariaDB 10.3+
- Navegadores: **Google Chrome**, **Mozilla Firefox**, **Microsoft Edge**

---

Liliannys Pastran	 Analista, Programadora, Diseñadora
Diego Flores Líder de proyecto,	Analista, Programador, Diseñador, Administrador de BD
Jeremy Montilla	Analista, Programador, Diseñador
Luis Balderrama	Analista, Programador, Diseñador, Administrador de BD
