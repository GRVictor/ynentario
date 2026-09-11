# Ynentario

Sistema de gestión y control de inventarios multialmacén con trazabilidad completa de movimientos, Kardex físico y control de existencias en tiempo real.

Desarrollado con **Laravel 13**, **Vue 3** (Composition API, `<script setup>`), **Inertia.js**, **TypeScript** y **Tailwind CSS**.

---

## Características

- **Gestión de Productos:** Catálogo con SKU, códigos de barras, categorías, marcas, stock mínimo, stock máximo y punto de reorden.
- **Multialmacén:** Control de existencias segmentado por bodega/sucursal.
- **Movimientos:** Registro transaccional de Entradas, Salidas, Ajustes de auditoría y Transferencias entre almacenes con folios consecutivos.
- **Kardex:** Historial cronológico con cálculo de saldo resultante y exportación a CSV.
- **Carga Masiva:** Importación y exportación de productos vía CSV.
- **Control de Acceso:** Roles y permisos (Administrador, Supervisor, Operador y Consulta).
- **Dashboard:** Resumen de stock crítico, rotación y gráficas de movimientos.

---

## Stack

- **Backend:** PHP 8.3 / Laravel 13 / MySQL 8.0
- **Frontend:** Vue 3 / Inertia.js / TypeScript / Tailwind CSS
- **Contenedores:** Docker & Docker Compose

---

## Instalación con Docker

### Opción rápida

- **Windows:**
  ```powershell
  .\docker\setup.bat
  ```

- **Linux / macOS:**
  ```bash
  chmod +x docker/setup.sh
  ./docker/setup.sh
  ```

---

### Instalación manual paso a paso

1. **Levantar los contenedores:**
   ```bash
   docker compose up -d --build
   ```

2. **Instalar dependencias y configurar la base de datos:**
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate --force
   docker compose exec app php artisan migrate:fresh --seed --force
   docker compose exec app npm install
   docker compose exec app npm run build
   ```

3. **Abrir en el navegador:**  
   [http://localhost:8000](http://localhost:8000)

---

## Instalación Local (sin Docker)

Si prefieres correrlo en tu máquina con PHP y MySQL locales (Laragon, XAMPP, etc.):

```bash
cp .env.example .env
# Configura tus credenciales de MySQL en .env

composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

---

## Cuentas de Acceso (Demo)

Todas las cuentas usan la contraseña: `password`

| Rol | Correo | Permisos |
| :--- | :--- | :--- |
| **Administrador** | `admin@ynentario.local` | Acceso total y gestión de usuarios |
| **Supervisor** | `supervisor@ynentario.local` | Operaciones, almacenes, ajustes y reportes |
| **Operador** | `operador@ynentario.local` | Registro de entradas, salidas y consulta |
| **Consulta** | `consulta@ynentario.local` | Solo lectura |

---

## Pruebas Automatizadas

Para correr la suite de pruebas:

```bash
# Con Docker:
docker compose exec app php artisan test --compact

# En local:
php artisan test --compact
```
