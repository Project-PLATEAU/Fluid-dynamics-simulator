-- 要件定義資料[III.5.③.17	熱対策施策テーブル]
-- ウェブアプリのGUI操作で追加更新削除しないテーブル

INSERT INTO public.POLICY(policy_id, policy_name, solar_absorptivity, heat_removal) VALUES (1,N'打ち水',0,-100);
INSERT INTO public.POLICY(policy_id, policy_name, solar_absorptivity, heat_removal) VALUES (2,N'屋上緑化',-0.2,0);
INSERT INTO public.POLICY(policy_id, policy_name, solar_absorptivity, heat_removal) VALUES (3,N'壁面緑化',-0.2,0);
INSERT INTO public.POLICY(policy_id, policy_name, solar_absorptivity, heat_removal) VALUES (4,N'敷地内植栽',-0.2,0);