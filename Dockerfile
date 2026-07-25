# archivo: /frontend/Dockerfile
FROM httpd:2.4

# Habilitar módulos necesarios
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /usr/local/apache2/conf/httpd.conf \
 && sed -i '/LoadModule headers_module/s/^#//g' /usr/local/apache2/conf/httpd.conf

# Permitir que .htaccess funcione
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /usr/local/apache2/conf/httpd.conf

# Copiar archivos del frontend
COPY . /usr/local/apache2/htdocs/