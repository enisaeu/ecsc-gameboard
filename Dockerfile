FROM debian:stable
RUN apt-get update && apt-get install -y git
RUN git clone --depth 1 https://github.com/enisaeu/ecsc-gameboard.git /var/www && cd /var/www && ./setup.sh
EXPOSE 80/tcp
CMD ["sh","-c","/usr/sbin/service mysql start && /usr/sbin/apache2ctl -D FOREGROUND"]

# Quick (run) reference:
#
# sudo docker build -t ecsc:latest .
# sudo docker run -d -p 8080:80 ecsc:latest
# sudo docker run -i -t ecsc:latest /bin/bash
