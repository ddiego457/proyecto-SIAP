const regexEmail = "/^[^\s@]+@[^\s@]+\.[^\s@]+$/";

const regexNombre = "/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{5,20}$/";

const regexContrasenia = "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/";

const regexTelf = "/^\+?[0-9][0-9\-\s]{6,}$/";

const regexNomPresupuesto = "^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{5,150}\|[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{2,30}\|$ /";

const regexCantidad = "/^\d{4}|$/";

const regexAnio = "/^\d{4}$/";

const regexPeriodo = "^([0-2][0-9]|3[0-1])(\/|-)(0[1-9]|1[0-2])\2(\d{4})$";

const regexPresupuesto = "/^\d{3,}$/";

const regexDescrPresupuesto = "/^[A-Za-záéíóúÁÉÍÓÚñÑ\s()]{5,35}$/";

const regexPrecios = "^\d+\.\d+$";