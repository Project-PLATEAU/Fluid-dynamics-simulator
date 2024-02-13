-- 要件定義資料[III.5.③.10	平面直角座標系テーブル]
-- ウェブアプリのGUI操作で追加更新削除しないテーブル

INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (1,N'I系：長崎県 鹿児島県のうち北方北緯32度南方北緯27度西方東経128度18分東方東経130度を境界線とする区域内（奄美群島は東経130度13分までを含む。)にあるすべての島、小島、環礁及び岩礁',33,129.5);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (2,N'II系：福岡県　佐賀県　熊本県　大分県　宮崎県　鹿児島県（I系に規定する区域を除く。)',33,131);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (3,N'III系：山口県　島根県　広島県',36,132.166666666667);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (4,N'IV系：香川県　愛媛県　徳島県　高知県',33,133.5);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (5,N'V系：兵庫県　鳥取県　岡山県',36,134.333333333333);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (6,N'VI系：京都府　大阪府　福井県　滋賀県　三重県　奈良県 和歌山県',36,136);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (7,N'VII系：石川県　富山県　岐阜県　愛知県',36,137.166666666667);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (8,N'VIII系：新潟県　長野県　山梨県　静岡県',36,138.5);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (9,N'IX系：東京都（XIV系、XVIII系及びXIX系に規定する区域を除く。)　福島県　栃木県　茨城県　埼玉県 千葉県　群馬県　神奈川県',36,139.833333333333);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (10,N'X系：青森県　秋田県　山形県　岩手県　宮城県',40,140.833333333333);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (11,N'XI系：小樽市　函館市　伊達市　北斗市　北海道後志総合振興局の所管区域　北海道胆振総合振興局の所管区域のうち豊浦町、壮瞥町及び洞爺湖町　北海道渡島総合振興局の所管区域　北海道檜山振興局の所管区域',44,140.25);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (12,N'XII系：北海道（XI系及びXIII系に規定する区域を除く。）',44,142.25);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (13,N'XIII系：北見市　帯広市　釧路市　網走市　根室市　北海道オホーツク総合振興局の所管区域のうち美幌町、津別町、斜里町、清里町、小清水町、訓子府町、置戸町、佐呂間町及び大空町　北海道十勝総合振興局の所管区域　北海道釧路総合振興局の所管区域　北海道根室振興局の所管区域',44,144.25);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (14,N'XIV系：東京都のうち北緯28度から南であり、かつ東経140度30分から東であり東経143度から西である区域',26,142);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (15,N'XV系：沖縄県のうち東経126度から東であり、かつ東経130度から西である区域',26,127.5);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (16,N'XVI系：沖縄県のうち東経126度から西である区域',26,124);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (17,N'XVII系：沖縄県のうち東経130度から東である区域',26,131);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (18,N'XVIII系：東京都のうち北緯28度から南であり、かつ東経140度30分から西である区域',20,136);
INSERT INTO public.COORDINATE(coordinate_id, coordinate_name, origin_latitude, origin_longitude) VALUES (19,N'XIX系：東京都のうち北緯28度から南であり、かつ東経143度から東である区域',26,154);
