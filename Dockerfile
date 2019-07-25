FROM debian:stable
#FROM ubuntu:latest
RUN apt-get update && apt-get install -y git
COPY . /var/www
RUN cd /var/www && ./setup.sh
EXPOSE 80/tcp
CMD ["sh","-c","/usr/sbin/service mysql start && /usr/sbin/apache2ctl -D FOREGROUND"]

# Quick (run) reference:
#
# sudo docker build -t ecsc:latest github.com/enisaeu/ecsc-gameboard
# sudo docker run -d -p 8080:80 ecsc:latest
