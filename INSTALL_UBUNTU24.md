# Guia de instalacion local - Ubuntu 24.04

Esta guia instala el Sistema Juridico Ecuador desde cero en Ubuntu 24.04,
con Apache2, MySQL y phpMyAdmin, de modo que la aplicacion quede disponible
en:

```
http://localhost/sistema_juridico_ec/
```

> Nota tecnica: la aplicacion original generaba enlaces y assets con rutas
> absolutas desde la raiz (`/normas`, `/assets/css/app.css`, etc.), lo cual
> rompia la navegacion al servirse bajo una subcarpeta. Esto ya fue
> corregido en el codigo (etiqueta `<base href>` dinamica + rutas
> relativas + prefijo automatico en las redirecciones), por lo que la URL
> de arriba funciona sin configuracion adicional.

## 0. Requisitos previos

Esta guia asume Ubuntu 24.04 LTS limpio o con Apache/MySQL/phpMyAdmin ya
instalados; en ese caso puede saltar directamente a la seccion 4.

## 1. Instalar Apache2

```bash
sudo apt update
sudo apt install -y apache2
sudo systemctl enable --now apache2
```

Verifique en el navegador `http://localhost/` (debe mostrar la pagina
por defecto de Apache).

## 2. Instalar PHP 8.3 y extensiones requeridas

El proyecto requiere PHP >= 8.3 con las extensiones `pdo_mysql`,
`mbstring`, `openssl`, `json`.

```bash
sudo apt install -y php8.3 libapache2-mod-php8.3 php8.3-cli \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    unzip git
```

Verifique la version:

```bash
php -v
```

Reinicie Apache para que cargue el modulo de PHP:

```bash
sudo systemctl restart apache2
```

## 3. Instalar MySQL y phpMyAdmin

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
```

Asegure la instalacion (contrasena de root, eliminar usuarios anonimos,
etc.):

```bash
sudo mysql_secure_installation
```

Instale phpMyAdmin (el instalador pregunta por el servidor web -
seleccione `apache2` con la tecla espacio y luego Enter/Aceptar- y si
desea configurar la base de datos con `dbconfig-common` - responda `Si`
y defina una contrasena para la app de phpMyAdmin):

```bash
sudo apt install -y phpmyadmin
```

Si el paso anterior no habilito el `.conf` de phpMyAdmin automaticamente:

```bash
sudo ln -s /etc/phpmyadmin/apache.conf /etc/apache2/conf-enabled/phpmyadmin.conf
sudo systemctl restart apache2
```

Pruebe `http://localhost/phpmyadmin/` con el usuario `root` (o el usuario
de aplicacion que cree en el paso 5) y la contrasena definida.

## 4. Instalar Composer

```bash
sudo apt install -y composer
composer --version
```

## 5. Clonar el repositorio

Clone el proyecto en una ruta **fuera** del `DocumentRoot` por defecto de
Apache (no dentro de `/var/www/html`), por ejemplo en su `home`:

```bash
cd ~
git clone https://github.com/dopio2018ec/sistema_juridico_ec.git
cd sistema_juridico_ec
```

Instale las dependencias (solo autoload PSR-4 y PHPUnit, sin dependencias
de produccion adicionales):

```bash
composer install
```

## 6. Configurar el entorno (`.env`)

```bash
cp .env.example .env
```

Edite `.env` y ajuste al menos:

- `APP_URL=http://localhost/sistema_juridico_ec`
- `APP_KEY`: una cadena aleatoria de al menos 32 caracteres, por ejemplo:
  ```bash
  php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
  ```
- `DB_DATABASE=sistema_juridico_ec`
- `DB_USERNAME` / `DB_PASSWORD`: las credenciales del usuario de
  aplicacion que creara en el paso 7 (no usar `root` en produccion).

## 7. Crear la base de datos y el usuario de aplicacion

Conectese como root:

```bash
sudo mysql -u root -p
```

Dentro de la consola de MySQL:

```sql
CREATE DATABASE sistema_juridico_ec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sistema_juridico_app'@'localhost' IDENTIFIED BY 'una_contrasena_fuerte';
GRANT SELECT, INSERT, UPDATE, DELETE ON sistema_juridico_ec.* TO 'sistema_juridico_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Use esa misma contrasena en `DB_PASSWORD` dentro de `.env`.

Importe el esquema y las migraciones, en orden (desde la carpeta del
proyecto):

```bash
mysql -u root -p sistema_juridico_ec < database/migrations/0000_schema_base.sql
mysql -u root -p sistema_juridico_ec < database/migrations/0001_add_auth_columns.sql
mysql -u root -p sistema_juridico_ec < database/migrations/0002_add_revision_normas.sql
```

(Tambien puede importar `0000_schema_base.sql` desde phpMyAdmin: pestana
`Importar` sobre la base de datos `sistema_juridico_ec`, repitiendo con
las migraciones `0001` y `0002` en ese orden).

## 8. Publicar la aplicacion en `http://localhost/sistema_juridico_ec/`

El unico directorio que debe exponer el servidor web es `public/`. Para
lograr que la URL final sea `http://localhost/sistema_juridico_ec/` sin
exponer el resto del repositorio (`.env`, `vendor/`, `database/`), cree un
enlace simbolico dentro del `DocumentRoot` por defecto de Apache que
apunte unicamente a la carpeta `public/` del proyecto:

```bash
sudo ln -s ~/sistema_juridico_ec/public /var/www/html/sistema_juridico_ec
```

(ajuste `~/sistema_juridico_ec` a la ruta real donde clono el repositorio
en el paso 5).

Habilite `mod_rewrite` (requerido por el `.htaccess` del proyecto) y
permita que Apache siga enlaces simbolicos dentro de `/var/www/html`:

```bash
sudo a2enmod rewrite
```

Edite la configuracion del sitio por defecto para permitir `.htaccess` y
simlinks:

```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

Agregue este bloque dentro de `<VirtualHost *:80> ... </VirtualHost>`
(antes de `</VirtualHost>`):

```apache
<Directory /var/www/html/sistema_juridico_ec>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Guarde y reinicie Apache:

```bash
sudo systemctl restart apache2
```

Verifique permisos de lectura para el usuario de Apache (`www-data`)
sobre el proyecto clonado:

```bash
sudo chown -R $USER:www-data ~/sistema_juridico_ec
sudo find ~/sistema_juridico_ec -type d -exec chmod 750 {} \;
sudo find ~/sistema_juridico_ec -type f -exec chmod 640 {} \;
sudo chmod -R 770 ~/sistema_juridico_ec/storage
```

## 9. Crear el primer usuario administrador

No existe un instalador web: el primer administrador se crea solo por
linea de comandos, desde la carpeta del proyecto:

```bash
cd ~/sistema_juridico_ec
php bin/create-admin.php "Nombre Completo" admin@dominio.ec "ContrasenaSegura_de_al_menos_12_caracteres"
```

## 10. Verificar

Abra en el navegador:

```
http://localhost/sistema_juridico_ec/
```

Debe redirigir a la pantalla de login (`/sistema_juridico_ec/login`).
Ingrese con el correo y contrasena creados en el paso 9.

## 11. (Opcional) Ejecutar las pruebas

```bash
cd ~/sistema_juridico_ec
composer test
```

Para que tambien corran las pruebas de integracion (requieren base de
datos), cree una base separada y configure `.env.testing`:

```sql
CREATE DATABASE sistema_juridico_ec_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Solucion de problemas comunes

- **Pagina en blanco o error 500**: revise
  `~/sistema_juridico_ec/storage/logs/app.log` y los logs de Apache
  (`sudo tail -f /var/log/apache2/error.log`).
- **404 en todas las rutas salvo `/`**: falta `mod_rewrite` o
  `AllowOverride All` en el `<Directory>` del paso 8.
- **Error de conexion a MySQL**: confirme `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` en `.env` y que el usuario
  de aplicacion tenga privilegios sobre `sistema_juridico_ec`.
- **Permisos denegados en `storage/`**: repita los `chmod`/`chown` del
  paso 8; Apache (`www-data`) necesita escritura en `storage/logs`.
