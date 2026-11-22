# Paquete de dependencias para subir al hosting

Este paquete deja **las dependencias de Composer listas para subir**, sin necesidad de
Composer ni SSH en el servidor (InfinityFree no ofrece ninguna de las dos cosas).

## Contenido

| Archivo | Tamaño | Descripción |
|---|---|---|
| `deploy/vendor.zip` | 47 MB | Carpeta `vendor/` completa (104 MB descomprimida), verificada |

Dentro de `vendor/` están bloqueadas estas versiones (las mismas que `composer.lock`):

- mpdf/mpdf **v8.3.1** (generación de PDF)
- phpoffice/phpspreadsheet **5.10.0** (Excel)
- phpmailer/phpmailer **v7.1.1** (correo SMTP)
- setasign/fpdi v2.6.8, psr/* , composer/*, markbaker/*, myclabs/deep-copy, paragonie/random_compat

## Requisitos del servidor

- **PHP >= 8.2** (phpoffice/phpspreadsheet 5.10 lo exige) → seleccionar versión en el panel de control.
- Extensiones: `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `openssl`, `simplexml`,
  `iconv`, `fileinfo`, `zlib`, `ctype`, `dom`, `filter`, `hash`.
- `vendor/` debe quedar en la **raíz del sitio**, al mismo nivel que `index.php`:
  ```
  htdocs/
  ├── index.php
  ├── .htaccess
  ├── app/
  ├── assets/
  └── vendor/      ← aquí (NO dentro de otra carpeta)
  ```

## Cómo subirlo

**Opción A — por FTP (la más segura):** descomprimir `vendor.zip` en local y subir la
carpeta `vendor/` completa con FileZilla (104 MB de transferencia).

**Opción B — por File Manager:** subir `vendor.zip` a la raíz y extraerlo con la función
"unzip/extract" del administrador de archivos. Si el File Manager no sabe extraer ZIPs,
usar la Opción A.

> Ojo al extraer: verifica que quede `htdocs/vendor/autoload.php`. Si quedó en
> `htdocs/vendor/vendor/autoload.php`, mueve el contenido un nivel arriba.

## Cómo probarlo

1. Subir temporalmente un archivo `prueba_vendor.php` en la raíz con:
   ```php
   <?php
   require __DIR__ . '/vendor/autoload.php';
   var_dump(class_exists('Mpdf\Mpdf'),
            class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet'),
            class_exists('PHPMailer\PHPMailer\PHPMailer'));
   phpinfo();
   ```
2. Visitar `https://tudominio/prueba_vendor.php` → los tres deben ser `bool(true)`.
3. **Borrar** `prueba_vendor.php` después de la prueba.

## Cómo regenerarlo (desde la máquina local)

```bash
cd /opt/lampp/htdocs/SIAP
composer install          # regenera vendor/ desde composer.json + composer.lock (PHP >= 8.2)
rm -f deploy/vendor.zip
zip -rq -9 deploy/vendor.zip vendor
unzip -t deploy/vendor.zip    # verifica integridad
```

Notas:

- `deploy/` está en `.gitignore`: el ZIP (47 MB) **no** se versiona.
- `composer.json` y `composer.lock` están **sincronizados** (`composer validate --check-lock`
  no reporta desfase; el `content-hash` del lock coincide con el archivo). Las 15
  dependencias del lock se instalan sin resolver nada nuevo.
- No subir al hosting: `.git/`, `.env`, `storage/`, `assets/uploads/` (archivos de
  usuarios) ni `*.sql` sueltos fuera de phpMyAdmin.
