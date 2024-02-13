-- (SSM) ステータス_シミュレーションモデル
CREATE TABLE STATUSDB_SIMULATION_MODEL
(
	-- モデルID
	id uuid NOT NULL UNIQUE,
	-- タスクID
	task_id smallint,
	-- ステータスID
	status_id smallint,
	-- 作成日時
	created_at timestamp(0),
	-- 更新日時
	updated_at timestamp(0),
	PRIMARY KEY (id)
) WITHOUT OIDS;