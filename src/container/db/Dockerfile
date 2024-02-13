FROM postgres:15.4-bullseye

# docker-entrypoint-initdb.d にあるSQLファイルがコンテナ起動時に実行される
COPY ./init.d /docker-entrypoint-initdb.d