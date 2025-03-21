# ベースイメージとしてUbuntu 22.04を使用
FROM ubuntu:22.04

# タイムゾーン設定
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 必要なパッケージをインストール
RUN set -x \
    && apt-get update \
    && apt-get install -y \
        software-properties-common \
        language-pack-ja \
        tzdata \
        vim \
        git \
        sudo \
        libpq-dev \
        npm \
        curl \
        wget \
        zip \
        unzip \
        tar \
        xz-utils \
    && add-apt-repository ppa:deadsnakes/ppa \
    && apt-get update && apt-get install -y \
        python3.12 \
        python3.12-dev \
        bash-completion \
        build-essential \
        iputils-ping \
    && apt-get -y clean \
    && rm -rf /var/lib/apt/lists/*

# Python / pip
RUN ln -s $(which python3.12) /usr/bin/python
RUN curl -sSL https://bootstrap.pypa.io/get-pip.py | python -


# Pythonパッケージのインストール
RUN python -m pip install pyproj czml sqlalchemy psycopg2 paramiko requests numpy stl numpy-stl fastapi[all]

# デフォルトのPythonバージョンをPython 3.12に設定
RUN update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.12 1

# コンテナ起動時に実行するコマンドを指定
ENTRYPOINT ["tail", "-f", "/dev/null"]
