
/* Drop Tables */

DROP TABLE IF EXISTS CITY_MODEL_REFERENCE_AUTHORITY;
DROP TABLE IF EXISTS SIMULATION_MODEL_POLICY;
DROP TABLE IF EXISTS SIMULATION_MODEL_REFERENCE_AUTHORITY;
DROP TABLE IF EXISTS SOLAR_ABSORPTIVITY;
DROP TABLE IF EXISTS VISUALIZATION;
DROP TABLE IF EXISTS SIMULATION_MODEL;
DROP TABLE IF EXISTS STL_MODEL;
DROP TABLE IF EXISTS REGION;
DROP TABLE IF EXISTS CITY_MODEL;
DROP TABLE IF EXISTS COORDINATE;
DROP TABLE IF EXISTS HEIGHT;
DROP TABLE IF EXISTS POLICY;
DROP TABLE IF EXISTS SOLVER;
DROP TABLE IF EXISTS STL_TYPE;
DROP TABLE IF EXISTS USER_ACCOUNT;




/* Create Tables */

-- (CM) 都市モデル
CREATE TABLE CITY_MODEL
(
	-- 都市モデルID
	city_model_id uuid NOT NULL UNIQUE,
	-- 識別名
	identification_name varchar(32),
	-- 登録ユーザID
	registered_user_id varchar(32) NOT NULL,
	-- 最終更新日時
	last_update_datetime timestamp(0),
	-- プリセットフラグ
	preset_flag boolean,
	-- URL
	url varchar(256),
	PRIMARY KEY (city_model_id)
) WITHOUT OIDS;


-- (CR) 都市モデル参照権限
CREATE TABLE CITY_MODEL_REFERENCE_AUTHORITY
(
	-- 都市モデルID
	city_model_id uuid NOT NULL,
	-- ユーザID
	user_id varchar(32) NOT NULL,
	-- 最終更新日時
	last_update_datetime timestamp(0),
	PRIMARY KEY (city_model_id, user_id),
	UNIQUE (city_model_id, user_id)
) WITHOUT OIDS;


-- (PC) 平面直角座標系
CREATE TABLE COORDINATE
(
	-- 平面直角座標系ID
	coordinate_id smallint NOT NULL,
	-- 平面直角座標系名
	coordinate_name varchar(256),
	-- 原点緯度
	origin_latitude double precision,
	-- 原点経度
	origin_longitude double precision,
	PRIMARY KEY (coordinate_id)
) WITHOUT OIDS;


-- (PH) 相対高さ
CREATE TABLE HEIGHT
(
	-- 相対高さID
	height_id smallint NOT NULL,
	-- 相対高さ
	height float,
	PRIMARY KEY (height_id)
) WITHOUT OIDS;


-- (PP)熱対策施策
CREATE TABLE POLICY
(
	-- 施策ID
	policy_id smallint NOT NULL,
	-- 施策名
	policy_name varchar(16),
	-- 日射吸収率調整係数
	solar_absorptivity float,
	-- 排熱量調整係数
	heat_removal float,
	PRIMARY KEY (policy_id)
) WITHOUT OIDS;


-- (CA) 解析対象地域
CREATE TABLE REGION
(
	-- 解析対象地域ID
	region_id uuid NOT NULL,
	-- 都市モデルID
	city_model_id uuid NOT NULL,
	-- 対象地域識別名
	region_name varchar(32),
	-- 平面直角座標系ID
	coordinate_id smallint NOT NULL,
	-- 南端緯度
	south_latitude double precision,
	-- 北端緯度
	north_latitude double precision,
	-- 西端経度
	west_longitude double precision,
	-- 東端経度
	east_longitude double precision,
	-- 地面高度
	ground_altitude float,
	-- 上空高度
	sky_altitude float,
	PRIMARY KEY (region_id)
) WITHOUT OIDS;


-- (SM) シミュレーションモデル
CREATE TABLE SIMULATION_MODEL
(
	-- シミュレーションモデルID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- 識別名
	identification_name varchar(32),
	-- 都市モデルID
	city_model_id uuid NOT NULL UNIQUE,
	-- 解析対象地域ID
	region_id uuid NOT NULL,
	-- 登録ユーザID
	registered_user_id varchar(32) NOT NULL,
	-- 最終更新日時
	last_update_datetime timestamp(0),
	-- プリセットフラグ
	preset_flag boolean,
	-- 外気温
	temperature float,
	-- 風速
	wind_speed float,
	-- 風向き
	wind_direction smallint,
	-- 日付
	solar_altitude_date date,
	-- 時間帯
	solar_altitude_time smallint,
	-- 南端緯度
	south_latitude double precision,
	-- 北端緯度
	north_latitude double precision,
	-- 西端経度
	west_longitude double precision,
	-- 東端緯度
	east_longitude double precision,
	-- 地面高度
	ground_altitude float,
	-- 上空高度
	sky_altitude float,
	-- ソルバID
	solver_id uuid NOT NULL,
	-- メッシュ粒度
	mesh_level smallint,
	-- 実行ステータス
	run_status smallint,
	-- 実行ステータス詳細
	run_status_details varchar(1024),
	-- 熱流体解析エラーログファイル
	cfd_error_log_file varchar(256),
	-- 最終シミュレーション開始日時
	last_sim_start_datetime timestamp(0),
	-- 最終シミュレーション完了日時
	last_sim_end_datetime timestamp(0),
	-- 一般公開フラグ
	disclosure_flag boolean,
	PRIMARY KEY (simulation_model_id)
) WITHOUT OIDS;


-- (SP)シミュレーションモデル実施施策
CREATE TABLE SIMULATION_MODEL_POLICY
(
	-- シミュレーションモデルID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- STLファイル種別ID
	stl_type_id smallint NOT NULL,
	-- 施策ID
	policy_id smallint NOT NULL,
	PRIMARY KEY (simulation_model_id, stl_type_id, policy_id)
) WITHOUT OIDS;


-- (SR) シミュレーションモデル参照権限
CREATE TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY
(
	-- シミュレーションモデルID
	simulation_model_id uuid NOT NULL,
	-- ユーザID
	user_id varchar(32) NOT NULL,
	-- 最終更新日時
	last_update_datetime timestamp(0),
	PRIMARY KEY (simulation_model_id, user_id)
) WITHOUT OIDS;


-- (SA)シミュレーションモデル熱効率
CREATE TABLE SOLAR_ABSORPTIVITY
(
	-- シミュレーションモデルID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- STLファイル種別ID
	stl_type_id smallint NOT NULL,
	-- 日射吸収率
	solar_absorptivity float,
	-- 排熱量
	heat_removal float,
	PRIMARY KEY (simulation_model_id, stl_type_id)
) WITHOUT OIDS;


-- (SC)熱流体解析ソルバ
CREATE TABLE SOLVER
(
	-- ソルバID
	solver_id uuid NOT NULL,
	-- 識別名
	solver_name varchar(64),
	-- ソルバ一式圧縮ファイル
	solver_compressed_file varchar(256),
	-- 登録ユーザID
	user_id varchar(32),
	-- 登録日時
	upload_datetime timestamp(0),
	-- プリセットフラグ
	preset_flag boolean,
	-- 公開フラグ
	disclosure_flag boolean,
	-- 説明
	explanation varchar(1024),
	PRIMARY KEY (solver_id)
) WITHOUT OIDS;


-- (CS) STLファイル
CREATE TABLE STL_MODEL
(
	-- 解析対象地域ID
	region_id uuid NOT NULL,
	-- STLファイル種別ID
	stl_type_id smallint NOT NULL,
	-- STLファイル
	stl_file varchar(256),
	-- アップロード日時
	upload_datetime timestamp(0),
	-- 日射吸収率
	solar_absorptivity float,
	-- 排熱量
	heat_removal float,
	PRIMARY KEY (region_id, stl_type_id)
) WITHOUT OIDS;


-- (PT) STLファイル種別
CREATE TABLE STL_TYPE
(
	-- STLファイル種別ID
	stl_type_id smallint NOT NULL,
	-- 種別名
	stl_type_name varchar(32),
	-- 必須フラグ
	required_flag boolean,
	-- 地面フラグ
	ground_flag boolean,
	-- 日射吸収率
	solar_absorptivity float,
	-- 排熱量
	heat_removal float,
	PRIMARY KEY (stl_type_id)
) WITHOUT OIDS;


-- (UA) ユーザアカウント
CREATE TABLE USER_ACCOUNT
(
	-- ユーザID
	user_id varchar(32) NOT NULL,
	-- パスワード
	password varchar(32),
	-- 表示名
	display_name varchar(32),
	-- 備考
	note varchar(256),
	-- 最終更新日時
	last_update_datetime timestamp(0),
	PRIMARY KEY (user_id)
) WITHOUT OIDS;


-- (SV) 可視化ファイル
CREATE TABLE VISUALIZATION
(
	-- シミュレーションモデルID
	simulation_model_id uuid NOT NULL,
	-- 可視化種別
	visualization_type smallint NOT NULL,
	-- 相対高さID
	height_id smallint NOT NULL,
	-- 可視化ファイル
	visualization_file varchar(256),
	-- シミュレーション結果（GeoJSON）ファイル
	geojson_file varchar(256),
	-- 凡例上端値
	legend_label_higher varchar(16),
	-- 凡例下端値
	legend_label_lower varchar(16),
	PRIMARY KEY (simulation_model_id, visualization_type, height_id)
) WITHOUT OIDS;



/* Create Foreign Keys */

ALTER TABLE CITY_MODEL_REFERENCE_AUTHORITY
	ADD FOREIGN KEY (city_model_id)
	REFERENCES CITY_MODEL (city_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE REGION
	ADD FOREIGN KEY (city_model_id)
	REFERENCES CITY_MODEL (city_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL
	ADD FOREIGN KEY (city_model_id)
	REFERENCES CITY_MODEL (city_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE REGION
	ADD FOREIGN KEY (coordinate_id)
	REFERENCES COORDINATE (coordinate_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE VISUALIZATION
	ADD FOREIGN KEY (height_id)
	REFERENCES HEIGHT (height_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL_POLICY
	ADD FOREIGN KEY (policy_id)
	REFERENCES POLICY (policy_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL
	ADD FOREIGN KEY (region_id)
	REFERENCES REGION (region_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE STL_MODEL
	ADD FOREIGN KEY (region_id)
	REFERENCES REGION (region_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL_POLICY
	ADD FOREIGN KEY (simulation_model_id)
	REFERENCES SIMULATION_MODEL (simulation_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY
	ADD FOREIGN KEY (simulation_model_id)
	REFERENCES SIMULATION_MODEL (simulation_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SOLAR_ABSORPTIVITY
	ADD FOREIGN KEY (simulation_model_id)
	REFERENCES SIMULATION_MODEL (simulation_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE VISUALIZATION
	ADD FOREIGN KEY (simulation_model_id)
	REFERENCES SIMULATION_MODEL (simulation_model_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL
	ADD FOREIGN KEY (solver_id)
	REFERENCES SOLVER (solver_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL_POLICY
	ADD FOREIGN KEY (stl_type_id)
	REFERENCES STL_TYPE (stl_type_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SOLAR_ABSORPTIVITY
	ADD FOREIGN KEY (stl_type_id)
	REFERENCES STL_TYPE (stl_type_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE STL_MODEL
	ADD FOREIGN KEY (stl_type_id)
	REFERENCES STL_TYPE (stl_type_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE CITY_MODEL
	ADD FOREIGN KEY (registered_user_id)
	REFERENCES USER_ACCOUNT (user_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE CITY_MODEL_REFERENCE_AUTHORITY
	ADD FOREIGN KEY (user_id)
	REFERENCES USER_ACCOUNT (user_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL
	ADD FOREIGN KEY (registered_user_id)
	REFERENCES USER_ACCOUNT (user_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY
	ADD FOREIGN KEY (user_id)
	REFERENCES USER_ACCOUNT (user_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;


ALTER TABLE SOLVER
	ADD FOREIGN KEY (user_id)
	REFERENCES USER_ACCOUNT (user_id)
	ON UPDATE RESTRICT
	ON DELETE RESTRICT
;



/* Comments */

COMMENT ON TABLE CITY_MODEL IS '(CM) 都市モデル';
COMMENT ON COLUMN CITY_MODEL.city_model_id IS '都市モデルID';
COMMENT ON COLUMN CITY_MODEL.identification_name IS '識別名';
COMMENT ON COLUMN CITY_MODEL.registered_user_id IS '登録ユーザID';
COMMENT ON COLUMN CITY_MODEL.last_update_datetime IS '最終更新日時';
COMMENT ON COLUMN CITY_MODEL.preset_flag IS 'プリセットフラグ';
COMMENT ON COLUMN CITY_MODEL.url IS 'URL';
COMMENT ON TABLE CITY_MODEL_REFERENCE_AUTHORITY IS '(CR) 都市モデル参照権限';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.city_model_id IS '都市モデルID';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.user_id IS 'ユーザID';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.last_update_datetime IS '最終更新日時';
COMMENT ON TABLE COORDINATE IS '(PC) 平面直角座標系';
COMMENT ON COLUMN COORDINATE.coordinate_id IS '平面直角座標系ID';
COMMENT ON COLUMN COORDINATE.coordinate_name IS '平面直角座標系名';
COMMENT ON COLUMN COORDINATE.origin_latitude IS '原点緯度';
COMMENT ON COLUMN COORDINATE.origin_longitude IS '原点経度';
COMMENT ON TABLE HEIGHT IS '(PH) 相対高さ';
COMMENT ON COLUMN HEIGHT.height_id IS '相対高さID';
COMMENT ON COLUMN HEIGHT.height IS '相対高さ';
COMMENT ON TABLE POLICY IS '(PP)熱対策施策';
COMMENT ON COLUMN POLICY.policy_id IS '施策ID';
COMMENT ON COLUMN POLICY.policy_name IS '施策名';
COMMENT ON COLUMN POLICY.solar_absorptivity IS '日射吸収率調整係数';
COMMENT ON COLUMN POLICY.heat_removal IS '排熱量調整係数';
COMMENT ON TABLE REGION IS '(CA) 解析対象地域';
COMMENT ON COLUMN REGION.region_id IS '解析対象地域ID';
COMMENT ON COLUMN REGION.city_model_id IS '都市モデルID';
COMMENT ON COLUMN REGION.region_name IS '対象地域識別名';
COMMENT ON COLUMN REGION.coordinate_id IS '平面直角座標系ID';
COMMENT ON COLUMN REGION.south_latitude IS '南端緯度';
COMMENT ON COLUMN REGION.north_latitude IS '北端緯度';
COMMENT ON COLUMN REGION.west_longitude IS '西端経度';
COMMENT ON COLUMN REGION.east_longitude IS '東端経度';
COMMENT ON COLUMN REGION.ground_altitude IS '地面高度';
COMMENT ON COLUMN REGION.sky_altitude IS '上空高度';
COMMENT ON TABLE SIMULATION_MODEL IS '(SM) シミュレーションモデル';
COMMENT ON COLUMN SIMULATION_MODEL.simulation_model_id IS 'シミュレーションモデルID';
COMMENT ON COLUMN SIMULATION_MODEL.identification_name IS '識別名';
COMMENT ON COLUMN SIMULATION_MODEL.city_model_id IS '都市モデルID';
COMMENT ON COLUMN SIMULATION_MODEL.region_id IS '解析対象地域ID';
COMMENT ON COLUMN SIMULATION_MODEL.registered_user_id IS '登録ユーザID';
COMMENT ON COLUMN SIMULATION_MODEL.last_update_datetime IS '最終更新日時';
COMMENT ON COLUMN SIMULATION_MODEL.preset_flag IS 'プリセットフラグ';
COMMENT ON COLUMN SIMULATION_MODEL.temperature IS '外気温';
COMMENT ON COLUMN SIMULATION_MODEL.wind_speed IS '風速';
COMMENT ON COLUMN SIMULATION_MODEL.wind_direction IS '風向き';
COMMENT ON COLUMN SIMULATION_MODEL.solar_altitude_date IS '日付';
COMMENT ON COLUMN SIMULATION_MODEL.solar_altitude_time IS '時間帯';
COMMENT ON COLUMN SIMULATION_MODEL.south_latitude IS '南端緯度';
COMMENT ON COLUMN SIMULATION_MODEL.north_latitude IS '北端緯度';
COMMENT ON COLUMN SIMULATION_MODEL.west_longitude IS '西端経度';
COMMENT ON COLUMN SIMULATION_MODEL.east_longitude IS '東端緯度';
COMMENT ON COLUMN SIMULATION_MODEL.ground_altitude IS '地面高度';
COMMENT ON COLUMN SIMULATION_MODEL.sky_altitude IS '上空高度';
COMMENT ON COLUMN SIMULATION_MODEL.solver_id IS 'ソルバID';
COMMENT ON COLUMN SIMULATION_MODEL.mesh_level IS 'メッシュ粒度';
COMMENT ON COLUMN SIMULATION_MODEL.run_status IS '実行ステータス';
COMMENT ON COLUMN SIMULATION_MODEL.run_status_details IS '実行ステータス詳細';
COMMENT ON COLUMN SIMULATION_MODEL.cfd_error_log_file IS '熱流体解析エラーログファイル';
COMMENT ON COLUMN SIMULATION_MODEL.last_sim_start_datetime IS '最終シミュレーション開始日時';
COMMENT ON COLUMN SIMULATION_MODEL.last_sim_end_datetime IS '最終シミュレーション完了日時';
COMMENT ON COLUMN SIMULATION_MODEL.disclosure_flag IS '一般公開フラグ';
COMMENT ON TABLE SIMULATION_MODEL_POLICY IS '(SP)シミュレーションモデル実施施策';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.simulation_model_id IS 'シミュレーションモデルID';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.stl_type_id IS 'STLファイル種別ID';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.policy_id IS '施策ID';
COMMENT ON TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY IS '(SR) シミュレーションモデル参照権限';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.simulation_model_id IS 'シミュレーションモデルID';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.user_id IS 'ユーザID';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.last_update_datetime IS '最終更新日時';
COMMENT ON TABLE SOLAR_ABSORPTIVITY IS '(SA)シミュレーションモデル熱効率';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.simulation_model_id IS 'シミュレーションモデルID';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.stl_type_id IS 'STLファイル種別ID';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.solar_absorptivity IS '日射吸収率';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.heat_removal IS '排熱量';
COMMENT ON TABLE SOLVER IS '(SC)熱流体解析ソルバ';
COMMENT ON COLUMN SOLVER.solver_id IS 'ソルバID';
COMMENT ON COLUMN SOLVER.solver_name IS '識別名';
COMMENT ON COLUMN SOLVER.solver_compressed_file IS 'ソルバ一式圧縮ファイル';
COMMENT ON COLUMN SOLVER.user_id IS '登録ユーザID';
COMMENT ON COLUMN SOLVER.upload_datetime IS '登録日時';
COMMENT ON COLUMN SOLVER.preset_flag IS 'プリセットフラグ';
COMMENT ON COLUMN SOLVER.disclosure_flag IS '公開フラグ';
COMMENT ON COLUMN SOLVER.explanation IS '説明';
COMMENT ON TABLE STL_MODEL IS '(CS) STLファイル';
COMMENT ON COLUMN STL_MODEL.region_id IS '解析対象地域ID';
COMMENT ON COLUMN STL_MODEL.stl_type_id IS 'STLファイル種別ID';
COMMENT ON COLUMN STL_MODEL.stl_file IS 'STLファイル';
COMMENT ON COLUMN STL_MODEL.upload_datetime IS 'アップロード日時';
COMMENT ON COLUMN STL_MODEL.solar_absorptivity IS '日射吸収率';
COMMENT ON COLUMN STL_MODEL.heat_removal IS '排熱量';
COMMENT ON TABLE STL_TYPE IS '(PT) STLファイル種別';
COMMENT ON COLUMN STL_TYPE.stl_type_id IS 'STLファイル種別ID';
COMMENT ON COLUMN STL_TYPE.stl_type_name IS '種別名';
COMMENT ON COLUMN STL_TYPE.required_flag IS '必須フラグ';
COMMENT ON COLUMN STL_TYPE.ground_flag IS '地面フラグ';
COMMENT ON COLUMN STL_TYPE.solar_absorptivity IS '日射吸収率';
COMMENT ON COLUMN STL_TYPE.heat_removal IS '排熱量';
COMMENT ON TABLE USER_ACCOUNT IS '(UA) ユーザアカウント';
COMMENT ON COLUMN USER_ACCOUNT.user_id IS 'ユーザID';
COMMENT ON COLUMN USER_ACCOUNT.password IS 'パスワード';
COMMENT ON COLUMN USER_ACCOUNT.display_name IS '表示名';
COMMENT ON COLUMN USER_ACCOUNT.note IS '備考';
COMMENT ON COLUMN USER_ACCOUNT.last_update_datetime IS '最終更新日時';
COMMENT ON TABLE VISUALIZATION IS '(SV) 可視化ファイル';
COMMENT ON COLUMN VISUALIZATION.simulation_model_id IS 'シミュレーションモデルID';
COMMENT ON COLUMN VISUALIZATION.visualization_type IS '可視化種別';
COMMENT ON COLUMN VISUALIZATION.height_id IS '相対高さID';
COMMENT ON COLUMN VISUALIZATION.visualization_file IS '可視化ファイル';
COMMENT ON COLUMN VISUALIZATION.geojson_file IS 'シミュレーション結果（GeoJSON）ファイル';
COMMENT ON COLUMN VISUALIZATION.legend_label_higher IS '凡例上端値';
COMMENT ON COLUMN VISUALIZATION.legend_label_lower IS '凡例下端値';



