-- (SSM) �X�e�[�^�X_�V�~�����[�V�������f��
CREATE TABLE STATUSDB_SIMULATION_MODEL
(
	-- ���f��ID
	id uuid NOT NULL UNIQUE,
	-- �^�X�NID
	task_id smallint,
	-- �X�e�[�^�XID
	status_id smallint,
	-- �쐬����
	created_at timestamp(0),
	-- �X�V����
	updated_at timestamp(0),
	PRIMARY KEY (id)
) WITHOUT OIDS;