-- �v����`����[III.5.�B.22	�M���̉�̓\���o�e�[�u��]
-- �E�F�u�A�v����GUI����ł��ǉ��X�V�폜����邪�A�������R�[�h��DML�œo�^���ׂ��e�[�u��
-- uuid�^��solver_id�͎�������
-- varchar(32)�^��user_id�̓��[�U�e�[�u���O���L�[����null���e

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
INSERT INTO public.SOLVER(solver_id,solver_name, solver_compressed_file, upload_datetime, preset_flag, disclosure_flag,explanation) VALUES (uuid_generate_v4(),N'�W��',N'compressed_solver/default/template.tar', CURRENT_TIMESTAMP, TRUE, TRUE,N'OpenFOAM��buoyantSimpleFoam�𗘗p');
