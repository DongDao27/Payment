FROM nginx:stable-alpine
COPY deploy/nginx/edge.conf /etc/nginx/nginx.conf
