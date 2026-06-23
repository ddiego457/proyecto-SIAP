-- 1. Tablas independientes (Sin llaves foráneas)

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(70) NOT NULL
);

CREATE TABLE dependencias (
    id_dep INT AUTO_INCREMENT PRIMARY KEY,
    nom_dep VARCHAR(80) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE tasa_bcv (
    id_tasa INT AUTO_INCREMENT PRIMARY KEY,
    fecha_reg DATE NOT NULL,
    tasa_bcv_usd DECIMAL(12,2) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE anio_fiscal (
    id_aniof INT AUTO_INCREMENT PRIMARY KEY,
    anio YEAR NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE partidas (
    id_partida INT AUTO_INCREMENT PRIMARY KEY,
    cod_partida VARCHAR(10) NOT NULL,
    descripcion VARCHAR(150) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nom_prov VARCHAR(100) NOT NULL,
    descripcion VARCHAR(150),
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

-- 2. Tablas con dependencias de primer nivel

CREATE TABLE responsables (
    id_responsable INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nom_rep VARCHAR(100) NOT NULL,
    password VARCHAR(120) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

CREATE TABLE periodos_entrega (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    id_aniof INT NOT NULL,
    per_inicio DATE NOT NULL,
    per_fin DATE NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_aniof) REFERENCES anio_fiscal(id_aniof)
);

CREATE TABLE requerimientos (
    id_req INT AUTO_INCREMENT PRIMARY KEY,
    id_dep INT NOT NULL,
    id_tasa INT NOT NULL,
    id_aniof INT NOT NULL,
    estado_envio BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_env DATE NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_dep) REFERENCES dependencias(id_dep),
    FOREIGN KEY (id_tasa) REFERENCES tasa_bcv(id_tasa),
    FOREIGN KEY (id_aniof) REFERENCES anio_fiscal(id_aniof)
);

CREATE TABLE contactos (
    id_telf INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
);

CREATE TABLE items_partida (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_partida INT NOT NULL,
    nom_item VARCHAR(150) NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    FOREIGN KEY (id_partida) REFERENCES partidas(id_partida)
);

-- 3. Tablas con dependencias de segundo nivel

CREATE TABLE cargo_responsable (
    id_cargo INT AUTO_INCREMENT PRIMARY KEY,
    id_responsable INT NOT NULL,
    id_dep INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_responsable) REFERENCES responsables(id_responsable),
    FOREIGN KEY (id_dep) REFERENCES dependencias(id_dep)
);

CREATE TABLE detalle_req (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT NOT NULL,
    id_req INT NOT NULL,
    mes TINYINT NOT NULL,
    cant_mes INT NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_item) REFERENCES items_partida(id_item),
    FOREIGN KEY (id_req) REFERENCES requerimientos(id_req)
);