FROM ubuntu:hirsute
RUN apt-get update && apt-get install -y git
COPY . /var/www
RUN cd /var/www && ./setup.sh
EXPOSE 80/tcp
CMD ["sh","-c","/usr/sbin/service mysql start && /usr/sbin/apache2ctl -D FOREGROUND"]

# (Quick reference):
#
# (Install):
# sudo docker build -t ecsc:latest github.com/enisaeu/ecsc-gameboard
#
# (Run #1 - exposing (as) host ports - for proper IP logging inside the Docker instance):
# sudo docker run -d --net=host ecsc:latest  # Note: prerequisite is that ports 80 and 3389 are free on a host itself (e.g. sudo service stop apache2; sudo service stop mysql)
#
# (Run #2)
# sudo docker run -d -p 8080:80 ecsc:latest
