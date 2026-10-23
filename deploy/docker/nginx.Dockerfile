FROM nginx:stable-alpine
COPY backend/public /var/www/html/public
COPY deploy/nginx/backend.conf /etc/nginx/conf.d/default.conf
