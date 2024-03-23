FROM ubuntu:22.04


# Configuring tzdata settings
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# vim, git, npm インストール
RUN apt-get update -y && \
  apt-get install -y vim git sudo libpq-dev tzdata\
  npm -y

# pip インストール
RUN apt-get install -y python3-pip
# pyproj インストール
RUN pip3 install pyproj
# numpy インストール
RUN pip3 install numpy
# sqlalchemy インストール
RUN pip3 install sqlalchemy
# psycopg2 インストール
RUN pip3 install psycopg2
# paramiko インストール
RUN pip3 install paramiko

ENTRYPOINT ["tail", "-f", "/dev/null"]
