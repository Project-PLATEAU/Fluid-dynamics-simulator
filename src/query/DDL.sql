
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

-- (CM) �s�s���f��
CREATE TABLE CITY_MODEL
(
	-- �s�s���f��ID
	city_model_id uuid NOT NULL UNIQUE,
	-- ���ʖ�
	identification_name varchar(32),
	-- �o�^���[�UID
	registered_user_id varchar(32) NOT NULL,
	-- �ŏI�X�V����
	last_update_datetime timestamp(0),
	-- �v���Z�b�g�t���O
	preset_flag boolean,
	-- URL
	url varchar(256),
	PRIMARY KEY (city_model_id)
) WITHOUT OIDS;


-- (CR) �s�s���f���Q�ƌ���
CREATE TABLE CITY_MODEL_REFERENCE_AUTHORITY
(
	-- �s�s���f��ID
	city_model_id uuid NOT NULL,
	-- ���[�UID
	user_id varchar(32) NOT NULL,
	-- �ŏI�X�V����
	last_update_datetime timestamp(0),
	PRIMARY KEY (city_model_id, user_id),
	UNIQUE (city_model_id, user_id)
) WITHOUT OIDS;


-- (PC) ���ʒ��p���W�n
CREATE TABLE COORDINATE
(
	-- ���ʒ��p���W�nID
	coordinate_id smallint NOT NULL,
	-- ���ʒ��p���W�n��
	coordinate_name varchar(256),
	-- ���_�ܓx
	origin_latitude double precision,
	-- ���_�o�x
	origin_longitude double precision,
	PRIMARY KEY (coordinate_id)
) WITHOUT OIDS;


-- (PH) ���΍���
CREATE TABLE HEIGHT
(
	-- ���΍���ID
	height_id smallint NOT NULL,
	-- ���΍���
	height float,
	PRIMARY KEY (height_id)
) WITHOUT OIDS;


-- (PP)�M�΍�{��
CREATE TABLE POLICY
(
	-- �{��ID
	policy_id smallint NOT NULL,
	-- �{����
	policy_name varchar(16),
	-- ���ˋz���������W��
	solar_absorptivity float,
	-- �r�M�ʒ����W��
	heat_removal float,
	PRIMARY KEY (policy_id)
) WITHOUT OIDS;


-- (CA) ��͑Ώےn��
CREATE TABLE REGION
(
	-- ��͑Ώےn��ID
	region_id uuid NOT NULL,
	-- �s�s���f��ID
	city_model_id uuid NOT NULL,
	-- �Ώےn�掯�ʖ�
	region_name varchar(32),
	-- ���ʒ��p���W�nID
	coordinate_id smallint NOT NULL,
	-- ��[�ܓx
	south_latitude double precision,
	-- �k�[�ܓx
	north_latitude double precision,
	-- ���[�o�x
	west_longitude double precision,
	-- ���[�o�x
	east_longitude double precision,
	-- �n�ʍ��x
	ground_altitude float,
	-- ��󍂓x
	sky_altitude float,
	PRIMARY KEY (region_id)
) WITHOUT OIDS;


-- (SM) �V�~�����[�V�������f��
CREATE TABLE SIMULATION_MODEL
(
	-- �V�~�����[�V�������f��ID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- ���ʖ�
	identification_name varchar(32),
	-- �s�s���f��ID
	city_model_id uuid NOT NULL UNIQUE,
	-- ��͑Ώےn��ID
	region_id uuid NOT NULL,
	-- �o�^���[�UID
	registered_user_id varchar(32) NOT NULL,
	-- �ŏI�X�V����
	last_update_datetime timestamp(0),
	-- �v���Z�b�g�t���O
	preset_flag boolean,
	-- �O�C��
	temperature float,
	-- ����
	wind_speed float,
	-- ������
	wind_direction smallint,
	-- ���t
	solar_altitude_date date,
	-- ���ԑ�
	solar_altitude_time smallint,
	-- ��[�ܓx
	south_latitude double precision,
	-- �k�[�ܓx
	north_latitude double precision,
	-- ���[�o�x
	west_longitude double precision,
	-- ���[�ܓx
	east_longitude double precision,
	-- �n�ʍ��x
	ground_altitude float,
	-- ��󍂓x
	sky_altitude float,
	-- �\���oID
	solver_id uuid NOT NULL,
	-- ���b�V�����x
	mesh_level smallint,
	-- ���s�X�e�[�^�X
	run_status smallint,
	-- ���s�X�e�[�^�X�ڍ�
	run_status_details varchar(1024),
	-- �M���̉�̓G���[���O�t�@�C��
	cfd_error_log_file varchar(256),
	-- �ŏI�V�~�����[�V�����J�n����
	last_sim_start_datetime timestamp(0),
	-- �ŏI�V�~�����[�V������������
	last_sim_end_datetime timestamp(0),
	-- ��ʌ��J�t���O
	disclosure_flag boolean,
	PRIMARY KEY (simulation_model_id)
) WITHOUT OIDS;


-- (SP)�V�~�����[�V�������f�����{�{��
CREATE TABLE SIMULATION_MODEL_POLICY
(
	-- �V�~�����[�V�������f��ID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- STL�t�@�C�����ID
	stl_type_id smallint NOT NULL,
	-- �{��ID
	policy_id smallint NOT NULL,
	PRIMARY KEY (simulation_model_id, stl_type_id, policy_id)
) WITHOUT OIDS;


-- (SR) �V�~�����[�V�������f���Q�ƌ���
CREATE TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY
(
	-- �V�~�����[�V�������f��ID
	simulation_model_id uuid NOT NULL,
	-- ���[�UID
	user_id varchar(32) NOT NULL,
	-- �ŏI�X�V����
	last_update_datetime timestamp(0),
	PRIMARY KEY (simulation_model_id, user_id)
) WITHOUT OIDS;


-- (SA)�V�~�����[�V�������f���M����
CREATE TABLE SOLAR_ABSORPTIVITY
(
	-- �V�~�����[�V�������f��ID
	simulation_model_id uuid NOT NULL UNIQUE,
	-- STL�t�@�C�����ID
	stl_type_id smallint NOT NULL,
	-- ���ˋz����
	solar_absorptivity float,
	-- �r�M��
	heat_removal float,
	PRIMARY KEY (simulation_model_id, stl_type_id)
) WITHOUT OIDS;


-- (SC)�M���̉�̓\���o
CREATE TABLE SOLVER
(
	-- �\���oID
	solver_id uuid NOT NULL,
	-- ���ʖ�
	solver_name varchar(64),
	-- �\���o�ꎮ���k�t�@�C��
	solver_compressed_file varchar(256),
	-- �o�^���[�UID
	user_id varchar(32),
	-- �o�^����
	upload_datetime timestamp(0),
	-- �v���Z�b�g�t���O
	preset_flag boolean,
	-- ���J�t���O
	disclosure_flag boolean,
	-- ����
	explanation varchar(1024),
	PRIMARY KEY (solver_id)
) WITHOUT OIDS;


-- (CS) STL�t�@�C��
CREATE TABLE STL_MODEL
(
	-- ��͑Ώےn��ID
	region_id uuid NOT NULL,
	-- STL�t�@�C�����ID
	stl_type_id smallint NOT NULL,
	-- STL�t�@�C��
	stl_file varchar(256),
	-- �A�b�v���[�h����
	upload_datetime timestamp(0),
	-- ���ˋz����
	solar_absorptivity float,
	-- �r�M��
	heat_removal float,
	PRIMARY KEY (region_id, stl_type_id)
) WITHOUT OIDS;


-- (PT) STL�t�@�C�����
CREATE TABLE STL_TYPE
(
	-- STL�t�@�C�����ID
	stl_type_id smallint NOT NULL,
	-- ��ʖ�
	stl_type_name varchar(32),
	-- �K�{�t���O
	required_flag boolean,
	-- �n�ʃt���O
	ground_flag boolean,
	-- ���ˋz����
	solar_absorptivity float,
	-- �r�M��
	heat_removal float,
	PRIMARY KEY (stl_type_id)
) WITHOUT OIDS;


-- (UA) ���[�U�A�J�E���g
CREATE TABLE USER_ACCOUNT
(
	-- ���[�UID
	user_id varchar(32) NOT NULL,
	-- �p�X���[�h
	password varchar(32),
	-- �\����
	display_name varchar(32),
	-- ���l
	note varchar(256),
	-- �ŏI�X�V����
	last_update_datetime timestamp(0),
	PRIMARY KEY (user_id)
) WITHOUT OIDS;


-- (SV) �����t�@�C��
CREATE TABLE VISUALIZATION
(
	-- �V�~�����[�V�������f��ID
	simulation_model_id uuid NOT NULL,
	-- �������
	visualization_type smallint NOT NULL,
	-- ���΍���ID
	height_id smallint NOT NULL,
	-- �����t�@�C��
	visualization_file varchar(256),
	-- �V�~�����[�V�������ʁiGeoJSON�j�t�@�C��
	geojson_file varchar(256),
	-- �}���[�l
	legend_label_higher varchar(16),
	-- �}�ቺ�[�l
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

COMMENT ON TABLE CITY_MODEL IS '(CM) �s�s���f��';
COMMENT ON COLUMN CITY_MODEL.city_model_id IS '�s�s���f��ID';
COMMENT ON COLUMN CITY_MODEL.identification_name IS '���ʖ�';
COMMENT ON COLUMN CITY_MODEL.registered_user_id IS '�o�^���[�UID';
COMMENT ON COLUMN CITY_MODEL.last_update_datetime IS '�ŏI�X�V����';
COMMENT ON COLUMN CITY_MODEL.preset_flag IS '�v���Z�b�g�t���O';
COMMENT ON COLUMN CITY_MODEL.url IS 'URL';
COMMENT ON TABLE CITY_MODEL_REFERENCE_AUTHORITY IS '(CR) �s�s���f���Q�ƌ���';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.city_model_id IS '�s�s���f��ID';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.user_id IS '���[�UID';
COMMENT ON COLUMN CITY_MODEL_REFERENCE_AUTHORITY.last_update_datetime IS '�ŏI�X�V����';
COMMENT ON TABLE COORDINATE IS '(PC) ���ʒ��p���W�n';
COMMENT ON COLUMN COORDINATE.coordinate_id IS '���ʒ��p���W�nID';
COMMENT ON COLUMN COORDINATE.coordinate_name IS '���ʒ��p���W�n��';
COMMENT ON COLUMN COORDINATE.origin_latitude IS '���_�ܓx';
COMMENT ON COLUMN COORDINATE.origin_longitude IS '���_�o�x';
COMMENT ON TABLE HEIGHT IS '(PH) ���΍���';
COMMENT ON COLUMN HEIGHT.height_id IS '���΍���ID';
COMMENT ON COLUMN HEIGHT.height IS '���΍���';
COMMENT ON TABLE POLICY IS '(PP)�M�΍�{��';
COMMENT ON COLUMN POLICY.policy_id IS '�{��ID';
COMMENT ON COLUMN POLICY.policy_name IS '�{����';
COMMENT ON COLUMN POLICY.solar_absorptivity IS '���ˋz���������W��';
COMMENT ON COLUMN POLICY.heat_removal IS '�r�M�ʒ����W��';
COMMENT ON TABLE REGION IS '(CA) ��͑Ώےn��';
COMMENT ON COLUMN REGION.region_id IS '��͑Ώےn��ID';
COMMENT ON COLUMN REGION.city_model_id IS '�s�s���f��ID';
COMMENT ON COLUMN REGION.region_name IS '�Ώےn�掯�ʖ�';
COMMENT ON COLUMN REGION.coordinate_id IS '���ʒ��p���W�nID';
COMMENT ON COLUMN REGION.south_latitude IS '��[�ܓx';
COMMENT ON COLUMN REGION.north_latitude IS '�k�[�ܓx';
COMMENT ON COLUMN REGION.west_longitude IS '���[�o�x';
COMMENT ON COLUMN REGION.east_longitude IS '���[�o�x';
COMMENT ON COLUMN REGION.ground_altitude IS '�n�ʍ��x';
COMMENT ON COLUMN REGION.sky_altitude IS '��󍂓x';
COMMENT ON TABLE SIMULATION_MODEL IS '(SM) �V�~�����[�V�������f��';
COMMENT ON COLUMN SIMULATION_MODEL.simulation_model_id IS '�V�~�����[�V�������f��ID';
COMMENT ON COLUMN SIMULATION_MODEL.identification_name IS '���ʖ�';
COMMENT ON COLUMN SIMULATION_MODEL.city_model_id IS '�s�s���f��ID';
COMMENT ON COLUMN SIMULATION_MODEL.region_id IS '��͑Ώےn��ID';
COMMENT ON COLUMN SIMULATION_MODEL.registered_user_id IS '�o�^���[�UID';
COMMENT ON COLUMN SIMULATION_MODEL.last_update_datetime IS '�ŏI�X�V����';
COMMENT ON COLUMN SIMULATION_MODEL.preset_flag IS '�v���Z�b�g�t���O';
COMMENT ON COLUMN SIMULATION_MODEL.temperature IS '�O�C��';
COMMENT ON COLUMN SIMULATION_MODEL.wind_speed IS '����';
COMMENT ON COLUMN SIMULATION_MODEL.wind_direction IS '������';
COMMENT ON COLUMN SIMULATION_MODEL.solar_altitude_date IS '���t';
COMMENT ON COLUMN SIMULATION_MODEL.solar_altitude_time IS '���ԑ�';
COMMENT ON COLUMN SIMULATION_MODEL.south_latitude IS '��[�ܓx';
COMMENT ON COLUMN SIMULATION_MODEL.north_latitude IS '�k�[�ܓx';
COMMENT ON COLUMN SIMULATION_MODEL.west_longitude IS '���[�o�x';
COMMENT ON COLUMN SIMULATION_MODEL.east_longitude IS '���[�ܓx';
COMMENT ON COLUMN SIMULATION_MODEL.ground_altitude IS '�n�ʍ��x';
COMMENT ON COLUMN SIMULATION_MODEL.sky_altitude IS '��󍂓x';
COMMENT ON COLUMN SIMULATION_MODEL.solver_id IS '�\���oID';
COMMENT ON COLUMN SIMULATION_MODEL.mesh_level IS '���b�V�����x';
COMMENT ON COLUMN SIMULATION_MODEL.run_status IS '���s�X�e�[�^�X';
COMMENT ON COLUMN SIMULATION_MODEL.run_status_details IS '���s�X�e�[�^�X�ڍ�';
COMMENT ON COLUMN SIMULATION_MODEL.cfd_error_log_file IS '�M���̉�̓G���[���O�t�@�C��';
COMMENT ON COLUMN SIMULATION_MODEL.last_sim_start_datetime IS '�ŏI�V�~�����[�V�����J�n����';
COMMENT ON COLUMN SIMULATION_MODEL.last_sim_end_datetime IS '�ŏI�V�~�����[�V������������';
COMMENT ON COLUMN SIMULATION_MODEL.disclosure_flag IS '��ʌ��J�t���O';
COMMENT ON TABLE SIMULATION_MODEL_POLICY IS '(SP)�V�~�����[�V�������f�����{�{��';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.simulation_model_id IS '�V�~�����[�V�������f��ID';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.stl_type_id IS 'STL�t�@�C�����ID';
COMMENT ON COLUMN SIMULATION_MODEL_POLICY.policy_id IS '�{��ID';
COMMENT ON TABLE SIMULATION_MODEL_REFERENCE_AUTHORITY IS '(SR) �V�~�����[�V�������f���Q�ƌ���';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.simulation_model_id IS '�V�~�����[�V�������f��ID';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.user_id IS '���[�UID';
COMMENT ON COLUMN SIMULATION_MODEL_REFERENCE_AUTHORITY.last_update_datetime IS '�ŏI�X�V����';
COMMENT ON TABLE SOLAR_ABSORPTIVITY IS '(SA)�V�~�����[�V�������f���M����';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.simulation_model_id IS '�V�~�����[�V�������f��ID';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.stl_type_id IS 'STL�t�@�C�����ID';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.solar_absorptivity IS '���ˋz����';
COMMENT ON COLUMN SOLAR_ABSORPTIVITY.heat_removal IS '�r�M��';
COMMENT ON TABLE SOLVER IS '(SC)�M���̉�̓\���o';
COMMENT ON COLUMN SOLVER.solver_id IS '�\���oID';
COMMENT ON COLUMN SOLVER.solver_name IS '���ʖ�';
COMMENT ON COLUMN SOLVER.solver_compressed_file IS '�\���o�ꎮ���k�t�@�C��';
COMMENT ON COLUMN SOLVER.user_id IS '�o�^���[�UID';
COMMENT ON COLUMN SOLVER.upload_datetime IS '�o�^����';
COMMENT ON COLUMN SOLVER.preset_flag IS '�v���Z�b�g�t���O';
COMMENT ON COLUMN SOLVER.disclosure_flag IS '���J�t���O';
COMMENT ON COLUMN SOLVER.explanation IS '����';
COMMENT ON TABLE STL_MODEL IS '(CS) STL�t�@�C��';
COMMENT ON COLUMN STL_MODEL.region_id IS '��͑Ώےn��ID';
COMMENT ON COLUMN STL_MODEL.stl_type_id IS 'STL�t�@�C�����ID';
COMMENT ON COLUMN STL_MODEL.stl_file IS 'STL�t�@�C��';
COMMENT ON COLUMN STL_MODEL.upload_datetime IS '�A�b�v���[�h����';
COMMENT ON COLUMN STL_MODEL.solar_absorptivity IS '���ˋz����';
COMMENT ON COLUMN STL_MODEL.heat_removal IS '�r�M��';
COMMENT ON TABLE STL_TYPE IS '(PT) STL�t�@�C�����';
COMMENT ON COLUMN STL_TYPE.stl_type_id IS 'STL�t�@�C�����ID';
COMMENT ON COLUMN STL_TYPE.stl_type_name IS '��ʖ�';
COMMENT ON COLUMN STL_TYPE.required_flag IS '�K�{�t���O';
COMMENT ON COLUMN STL_TYPE.ground_flag IS '�n�ʃt���O';
COMMENT ON COLUMN STL_TYPE.solar_absorptivity IS '���ˋz����';
COMMENT ON COLUMN STL_TYPE.heat_removal IS '�r�M��';
COMMENT ON TABLE USER_ACCOUNT IS '(UA) ���[�U�A�J�E���g';
COMMENT ON COLUMN USER_ACCOUNT.user_id IS '���[�UID';
COMMENT ON COLUMN USER_ACCOUNT.password IS '�p�X���[�h';
COMMENT ON COLUMN USER_ACCOUNT.display_name IS '�\����';
COMMENT ON COLUMN USER_ACCOUNT.note IS '���l';
COMMENT ON COLUMN USER_ACCOUNT.last_update_datetime IS '�ŏI�X�V����';
COMMENT ON TABLE VISUALIZATION IS '(SV) �����t�@�C��';
COMMENT ON COLUMN VISUALIZATION.simulation_model_id IS '�V�~�����[�V�������f��ID';
COMMENT ON COLUMN VISUALIZATION.visualization_type IS '�������';
COMMENT ON COLUMN VISUALIZATION.height_id IS '���΍���ID';
COMMENT ON COLUMN VISUALIZATION.visualization_file IS '�����t�@�C��';
COMMENT ON COLUMN VISUALIZATION.geojson_file IS '�V�~�����[�V�������ʁiGeoJSON�j�t�@�C��';
COMMENT ON COLUMN VISUALIZATION.legend_label_higher IS '�}���[�l';
COMMENT ON COLUMN VISUALIZATION.legend_label_lower IS '�}�ቺ�[�l';



