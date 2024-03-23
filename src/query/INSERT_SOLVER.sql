-- 要件定義資料[III.5.③.22	熱流体解析ソルバテーブル]
-- ウェブアプリのGUI操作でも追加更新削除されるが、初期レコードはDMLで登録すべきテーブル
-- uuid型のsolver_idは自動生成
-- varchar(32)型のuser_idはユーザテーブル外部キーだがnull許容

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
INSERT INTO public.SOLVER(solver_id,solver_name, solver_compressed_file, upload_datetime, preset_flag, disclosure_flag,explanation) VALUES (uuid_generate_v4(),N'標準',N'compressed_solver/default/template.tar', CURRENT_TIMESTAMP, TRUE, TRUE,N'OpenFOAMのbuoyantSimpleFoamを利用');
