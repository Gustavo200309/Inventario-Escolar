# Instrucciones para QR con ngrok

Este proyecto puede generar QR que abren la vista publica de detalles del bien:

```text
/b/CODIGO_DEL_BIEN
```

Para que el QR funcione desde un celular sin depender de la IP local de la computadora, se usa ngrok y la variable `PUBLIC_QR_URL`.

## 1. Preparar el archivo .env

El archivo `.env` no se sube al repositorio. Para preparar una computadora nueva:

```bash
copy .env.example .env
php artisan key:generate
```

Tambien puedes revisar el archivo de ejemplo para QR:

```text
.env.example.paraqr
```

En tu `.env`, agrega o actualiza estas variables:

```env
APP_URL=http://localhost
PUBLIC_QR_URL=
SERVER_HOST=127.0.0.1
SERVER_PORT=8000
```

## 2. Arrancar Laravel

En una terminal, dentro del proyecto:

```bash
php artisan serve
```

Debe iniciar en:

```text
http://127.0.0.1:8000
```

## 3. Arrancar ngrok

En otra terminal:

```bash
ngrok http 8000
```

Ngrok mostrara una URL HTTPS parecida a esta:

```text
https://tu-url.ngrok-free.dev
```

## 4. Configurar la URL publica para los QR

Copia la URL HTTPS de ngrok y pegala en `.env`:

```env
PUBLIC_QR_URL=https://tu-url.ngrok-free.dev
```

Despues limpia la cache de Laravel:

```bash
php artisan optimize:clear
```

Ahora vuelve a abrir o imprimir los QR. Los QR nuevos apuntaran a:

```text
https://tu-url.ngrok-free.dev/b/CODIGO_DEL_BIEN
```

## 5. Importante

- `localhost` y `127.0.0.1` solo funcionan en la computadora donde corre Laravel.
- Un celular no puede abrir `localhost` de la computadora.
- La URL gratis de ngrok puede cambiar cada vez que se reinicia ngrok.
- Si cambia la URL de ngrok, actualiza `PUBLIC_QR_URL` en `.env`, ejecuta `php artisan optimize:clear` y vuelve a generar/imprimir los QR.
- No subas tu token de ngrok al repositorio.
- No subas tu archivo `.env` real al repositorio.

## 6. Advertencia de ngrok

En el plan gratis, ngrok puede mostrar una pantalla de advertencia antes de entrar al sitio.

En el celular solo hay que tocar:

```text
Visit Site
```

Despues se abre la vista publica del bien.

## 7. Comandos rapidos

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
ngrok http 8000
```

Despues de copiar la URL de ngrok en `.env`:

```bash
php artisan optimize:clear
```

