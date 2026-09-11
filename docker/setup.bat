@echo off
echo ==========================================
echo   Instalador automatico Ynentario Docker  
echo ==========================================

echo [1/5] Levantando contenedores Docker...
docker compose up -d --build
if errorlevel 1 goto error

echo [2/5] Instalando dependencias de Composer...
docker compose exec -T app composer install
if errorlevel 1 goto error

echo [3/5] Generando APP_KEY de Laravel...
docker compose exec -T app php artisan key:generate --force
if errorlevel 1 goto error

echo [4/5] Ejecutando migraciones y datos de prueba...
docker compose exec -T app php artisan migrate:fresh --seed --force
if errorlevel 1 goto error

echo [5/5] Compilando assets de frontend...
docker compose exec -T app npm install
docker compose exec -T app npm run build
if errorlevel 1 goto error

echo ==========================================
echo   Instalacion completada con exito!      
echo   Accede en: http://localhost:8000        
echo   Usuario:   admin@ynentario.local        
echo   Password:  password                     
echo ==========================================
pause
exit /b 0

:error
echo [ERROR] Ocurrio un error durante la instalacion. Revisa los mensajes anteriores.
pause
exit /b 1
