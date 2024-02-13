-- 要件定義資料[III.5.③.7 ユーザアカウントテーブル]
-- ウェブアプリのGUI操作で追加更新削除しないテーブル

INSERT INTO public.USER_ACCOUNT(user_id, password, display_name, note, last_update_datetime) VALUES (N'testuser',N'&ezULtAW3FYa',N'熱田　流体',N'環境構築用DMLサンプルユーザ', CURRENT_TIMESTAMP)

