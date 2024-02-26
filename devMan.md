# 環境構築手順・インストール

本書では日射や風況等に基づく温熱環境シミュレーションシステム（以下「本システム」という。）の利用環境構築手順について記載しています。
本システムの構成や仕様の詳細については以下も参考にしてください。

[技術検証レポート](https://www.mlit.go.jp/plateau/file/libraries/doc/plateau_tech_doc_0030_ver01.pdf)

# 1 事前準備・推奨条件

はじめに、本システムを利用する際は、
[動作環境](https://github.com/Project-PLATEAU/)
で示すサーバマシン内の構成を２台のマシンに分けて構成することを推奨します。また、OSはどちらのマシンもUbuntuを推奨します。

上記に伴い、本書では２台のサーバマシンを以下のように定義し、構築手順を記載します。

- コンテナ管理用マシン
    - Webコンテナ、DBコンテナ、Wrapperコンテナを管理するマシン
- シミュレータ用マシン
    - 熱流体解析シミュレーションの稼働に十分なスペックであるマシン

※　ファイルストレージについて\
  本システムのファイルストレージは、コンテナ管理用マシンにアクセスできる環境であれば、コンテナ管理用マシンのディスク、または別のNASやパブリッククラウドサービス（Amazon EFS等）のいずれを利用しても問題ございません。そのため、本書でのインストール手順の詳細は省略します。

## Dockerインストール

本システムのコンテナ管理用マシンでは、Docker Engine および Docker Composeを利用します。\
未インストールの方は以下参考にインストールから実施してください。

### Docker Engine
詳細は、
[公式サイト](https://docs.docker.com/engine/install/ubuntu/)
をご覧ください。

本書では、新しいマシンに初めてDocker エンジンをインストールすることを想定し、公式サイトでも紹介されている aptリポジトリを使用したインストール方法を記載します。\
以下手順に沿って、コマンドを実行してください。

1. Docker の'apt'リポジトリを設定

```
# Add Docker's official GPG key:
sudo apt-get update
sudo apt-get install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
```

2. Docker パッケージのインストール

```
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

3. インストール成功確認

```
sudo docker run hello-world
```

### Docker Compose
詳細は、
[公式サイト](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
をご覧ください。

本書では、Docker Engine をインストールした人が続けて Docker Compose をインストールすることを想定し、上記と同様に公式サイトでも紹介されている aptリポジトリを使用したインストール方法を記載します。\
以下手順に沿って、コマンドを実行してください。

1. パッケージインデックスの更新

```
sudo apt-get update
```

2. Docker Compose のインストール

```
sudo apt-get install docker-compose-plugin
```

3. インストール成功確認

```
docker compose version
```


# 2 動作環境
（ここにもってきたいが、ファイルをわけたい）

本システムは、利用者端末であるクライアントPCおよびネットワーク接続するサーバマシンの各ハードウェアより構成されます。サーバマシンでは複数のマシン（コンテナ）から構成され、うちWebコンテナがクライアントPC上のブラウザに対してウェブアプリをホストし、他のコンテナはWebコンテナと結合して諸機能を提供します。

![ハードウェアアーキテクチャ図](../resources/devMan/devMan00-fig33.png)


動作環境は以下のとおりです。

## クライアントPC

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| ブラウザ | JavaScript、jQuery、CesiumJS対応ブラウザ | Google Chrome　120.0以上 |
| ディスプレイ解像度 | 1024×768以上 | 1920×1080以上 |
| ネットワーク | 以下のURLを閲覧可能。 <br>・サーバマシンのWebアプリ<br>・[PLATEAU-3DTilesの配信サービス](https://github.com/Project-PLATEAU/plateau-streaming-tutorial/) | インターネット接続 |

## サーバマシン - Webコンテナ

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| OS | Ubuntu | Dockerファイルに依り立ち上げた仮想環境 |
| ネットワーク | クライアントPCとHTTPSでのネットワーク接続 | インターネット接続、ファイアウォール設置 |
| ネットワーク | DBコンテナ、ファイルストレージとのネットワーク接続 | サーバマシン内でのVPN |

## サーバマシン - DBコンテナ

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| DBMS | PostgresSQL | 同左 |
| ネットワーク | Webコンテナ、Wrapperコンテナとのネットワーク接続 | サーバマシン内でのVPN |

## サーバマシン - ファイルストレージ

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| ファイルシステム | Ubuntu（Webコンテナ、Wrapperコンテナ）がマウント可能なファイルシステム | Amazon EFSやsamba |
| ネットワーク | Webコンテナ、Wrapperコンテナとのネットワーク接続 | サーバマシン内でのVPN |

## サーバマシン - Wrapperコンテナ

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| OS | Ubuntu | Dockerファイルに依り立ち上げた仮想環境 |
| ネットワーク | DBコンテナ、ファイルストレージ、シミュレーションコンテナとのネットワーク接続 | サーバマシン内でのVPN |

## サーバマシン - シミュレーションコンテナ

| 項目 | 最小動作環境 | 推奨動作環境 |
| - | - | - |
| OS | Ubuntu | Dockerファイルに依り立ち上げた仮想環境 |
| ネットワーク | Wrapperコンテナとのネットワーク接続 | サーバマシン内でのVPN |
| CPU |i7 6コア 以上 | 同左 |
| メモリ | 32GB以上 | 64GB以上 |
| ストレージ | 1TB以上 | 2TB以上 |


# 3 ダウンロード

## Dockerコンテナの作成と起動
コンテナ管理用マシン上に、３つのコンテナの作成から起動までを実施します。\
まずは、自身でソースファイルを実行することで、コンテナを作成することができます。作成に必要なソースファイル一式は
[こちら](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
からダウンロード可能です。

GitHubからダウンロードしたソースファイルの構成は以下のようになっています。

![](../resources/devMan/tutorial_028.png)


ここでは、コンテナ管理用マシン上でコマンドを実行してコンテナを作成するまでの手順を記載します。

1. GitHub mainブランチから
[src/container](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
をコピー

```
cd /********
sudo git clone https://github.com/   /src/container
```

2. Docker コンテナの作成
（数分間かけてコンテナが作成されます。）
```
sudo docker-compose up
```

3. 作成確認およびコンテナIDの把握

```
docker ps -a
```
出力結果より、Webコンテナ、DBコンテナ、Wrapperコンテナの[STATUS]がUPになっていることを確認します。また、英数字12桁で1番左側に出力されている[CONTAINER ID]を記録しておきます。

## Webコンテナ
作成したWebコンテナへアクセスし、Webアプリの動作に必要な設定を実施します。\
Webコンテナで利用するソースファイル一式は
[こちら](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
からダウンロード可能です。

1. Webコンテナへアクセス
コンテナ管理用マシン上で、WebコンテナのコンテナIDを入力します。

```
sudo docker  exec -it  [Web-CONTAINER ID] /bin/bash
```

2. GitHub mainブランチから
[src/web](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
をコピー

```
cd /********
sudo git clone https://github.com/   /src/web
```

3. 設定ファイルの作成
サンプルファイル[.env.example]を参考に、[.env]を編集します。
（編集箇所や記入内容も記載するか相談）

サンプルファイルの確認方法
```
sudo view .env.example
```
設定ファイルの編集
```
sudo vi .env
```

次に、「APP_KEY」を生成します。
```
cd srcWeb/bridge-cfd/
php artisan key:generate
```
生成後、設定ファイルの「APP_KEY」に自動で入力されていることを確認します。
```
sudo view .env
```

4. ライブラリのインストール
最後に、ライブラリをインストールします。設定ファイルを作成後に実施します。
```
cd srcWeb/bridge-cfd/
composer install
```

## DBコンテナ
作成したDBコンテナへアクセスし、データベースを作成します。\
DBコンテナで利用するソースファイル一式は
[こちら](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
からダウンロード可能です。

1. DBコンテナへアクセス
コンテナ管理用マシン上で、DBコンテナのコンテナIDを入力します。

```
sudo docker  exec -it  [DB-CONTAINER ID] /bin/bash
```

2. GitHub mainブランチから
[src/web](https://docs.docker.com/compose/install/linux/#install-using-the-repository)
をコピー

```
cd /********
sudo git clone https://github.com/   /src/db
```

3. 設定ファイルの作成
サンプルファイル[.env.example]を参考に、[.env]を編集します。
（編集箇所や記入内容も記載するか相談）

サンプルファイルの確認方法
```
sudo view .env.example
```
設定ファイルの編集
```
sudo vi .env
```

次に、「APP_KEY」を生成します。
```
cd srcWeb/bridge-cfd/
php artisan key:generate
```
生成後、設定ファイルの「APP_KEY」に自動で入力されていることを確認します。
```
sudo view .env
```

4. ライブラリのインストール
最後に、ライブラリをインストールします。設定ファイルを作成後に実施します。
```
cd srcWeb/bridge-cfd/
composer install
```











## Wrapperコンテナ

Wrapperコンテナへアクセス

Git Clone（mainブランチのsrc/）

## シミュレーションマシン

OpenFoamInstall

# 4 ビルド手順

自身でソースファイルをダウンロードしビルドを行うことで、実行ファイルを生成することができます。\
ソースファイルは
[こちら](https://github.com/Project-PLATEAU/UC22-013-SolarPotential/)
からダウンロード可能です。

GitHubからダウンロードしたソースファイルの構成は以下のようになっています。

![](../resources/devMan/tutorial_028.png)

（1）本システムのソリューションファイル（SolarPotential.sln）をVisualStudio2019で開きます。

ソリューションファイルはSRC\\EXE\\SolarPotential-CS\\SolarPotentialに格納されています。

（2）SolarPotential.slnをVisualStudio2019で開くと、ソリューション'SolarPotential'に6つのプロジェクトが表示されます。

以下の赤枠部分のように、ソリューション構成を【Release】に、ソリューションプラットフォームを【x64】に設定します。

![](../resources/devMan/tutorial_029.png)

（3）以下の赤枠部分のように、\[ソリューションのビルド\]を選択し、ソリューション全体をビルドします。

![](../resources/devMan/tutorial_030.png)

（4）ビルドが正常に終了すると、ソリューションファイルと同じフォルダにあるbin\\Releaseフォルダに実行ファイルが生成されます。

![](../resources/devMan/tutorial_031.png)

※ダウンロードしたソリューションをビルドする際に、ビルドエラーとなり、次のメッセージが出力されるケースがあります。

「（ファイル名）を処理できませんでした。インターネットまたは制限付きゾーン内にあるか、ファイルに
Web のマークがあるためです。これらのファイルを処理するには、Web
のマークを削除してください。」

この場合は該当するファイルのプロパティを開き、全般タブ内の「セキュリティ」の項目について\[許可する\]にチェックを入れてください。

![](../resources/devMan/tutorial_032.png)

【参考】

ソースファイルの構成と機能は以下のようになっています。コードを修正する際の参考としてください。

![](../resources/devMan/tutorial_033.png)

# 5 準備物一覧

アプリケーションを利用するために以下のデータを入手します。

| | データ種別 | 機能                                                                                                                        | 用途                 | 入力方法           |
| ---------- | --------------------------------------------------------------------------------------------------------------------------- | -------------------- | ------------------ | ------------------------------------------------ |
| ①          | 3D都市モデル(CityGML)G空間情報センターから取得します。<br> https://front.geospatial.jp/                                         | 全般                 | 全般               | 格納フォルダパス指定                             |
| ②          | 月毎の可照時間国立天文台 こよみの計算Webページから取得します。<br> https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/koyomix.cgi         | 発電ポテンシャル推計 | 日射量の推計       | CSVファイルを手動作成しファイルパス指定          |
| ③          | 毎月の平均日照時間気象庁 過去の気象データ・ダウンロードから取得します。<br> https://www.data.jma.go.jp/gmd/risk/obsdl/index.php | 発電ポテンシャル推計 | 日射量の推計       | CSVファイルをダウンロードしファイルパス指定      |
| ④          | 月毎の積雪深NEDO 日射量データベース閲覧システム METPV-20から取得します。<br> https://appww2.infoc.nedo.go.jp/appww/index.html   | 発電ポテンシャル推計 | 日射量の推計       | CSVファイルを手動作成しファイルパス指定          |
| ⑤          | 3D都市モデル(DEMデータ)G空間情報センターから取得します。<br> https://front.geospatial.jp/                                       | 発電ポテンシャル推計 | 日射量の推計       | 格納フォルダパス指定                             |
| ⑥          | 制限区域データ（シェープファイル）<br>（加賀市　景観整備区域・石川県　石川県眺望計画）                                                                                          | パネル設置適地判定   | パネル設置適地判定 | シェープファイルパス指定                         |
| ⑦          | 気象関連データ（積雪）(国土数値情報の平年値（気候）メッシュ)| パネル設置適地判定   | パネル設置適地判定 | シェープファイルをダウンロードしファイルパス指定 |


本システムでは、3D都市モデルの建築物モデルの形状（LOD1、LOD2）と属性を活用します。

地形を考慮した解析を行う場合は、地形（LOD1）も活用してください。

| 地物       | 地物型            | 属性区分 | 属性名                                 | 内容                 |
| ---------- | ----------------- | -------- | -------------------------------------- | -------------------- |
| 建築物LOD2 | bldg:Building     | 空間属性 | bldg:RoofSurface                       | 建築物のLOD2の屋根面 |
|            |                   |          | bldg:WallSurface                       | 建築物のLOD2の壁面   |
|            |                   | 主題属性 | bldg:measuredHeight                    | 計測高さ             |
|            |                   |          | uro:buildingDisasterRiskAttribute      | 災害リスク           |
|            |                   |          | uro:buildingID                         | 建物ID               |
|            |                   |          | uro:buildingStructureType              | 構造種別             |
|            |                   |          | uro:buildingStructureOrgType           | 構造種別（独自）     |
|            |                   |          | uro:BuildingRiverFloodingRiskAttribute | 洪水浸水リスク       |
|            |                   |          | uro:depth                              | 浸水深               |
|            |                   |          | uro:BuildingTsunamiRiskAttribute       | 津波浸水リスク       |
|            |                   |          | uro:depth                              | 浸水深               |
|            |                   |          | uro:BuildingLandSlideRiskAttribute     | 土砂災害リスク       |
| 建築物LOD1 | bldg:Building     | 空間属性 | bldg:lod1Solid                         | 建築物のLOD1の立体   |
| 地形LOD1   | dem:ReliefFeature | 空間属性 | dem:tin                                | 地形LOD1の面         |