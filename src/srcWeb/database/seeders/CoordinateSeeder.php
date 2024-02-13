<?php

namespace Database\Seeders;

use App\Models\Db\Coordinate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoordinateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coordinate::create([
            'coordinate_id'     =>  1,
            'coordinate_name'   =>  'I系：長崎県 鹿児島県のうち北方北緯32度南方北緯27度西方東経128度18分東方東経130度を境界線とする区域内（奄美群島は東経130度13分までを含む。)にあるすべての島、小島、環礁及び岩礁',
            'origin_latitude'   =>  33,
            'origin_longitude'  =>  129.5
        ]);
        Coordinate::create([
            'coordinate_id'     =>  2,
            'coordinate_name'   =>  'II系：福岡県　佐賀県　熊本県　大分県　宮崎県　鹿児島県（I系に規定する区域を除く。)',
            'origin_latitude'   =>  33,
            'origin_longitude'  =>  131
        ]);
        Coordinate::create([
            'coordinate_id'     =>  3,
            'coordinate_name'   =>  'III系：山口県　島根県　広島県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  132.166666666667
        ]);
        Coordinate::create([
            'coordinate_id'     =>  4,
            'coordinate_name'   =>  'IV系：香川県　愛媛県　徳島県　高知県',
            'origin_latitude'   =>  33,
            'origin_longitude'  =>  133.5
        ]);
        Coordinate::create([
            'coordinate_id'     =>  5,
            'coordinate_name'   =>  'V系：兵庫県　鳥取県　岡山県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  134.333333333333
        ]);
        Coordinate::create([
            'coordinate_id'     =>  6,
            'coordinate_name'   =>  'VI系：京都府　大阪府　福井県　滋賀県　三重県　奈良県 和歌山県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  136
        ]);
        Coordinate::create([
            'coordinate_id'     =>  7,
            'coordinate_name'   =>  'VII系：石川県　富山県　岐阜県　愛知県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  137.166666666667
        ]);
        Coordinate::create([
            'coordinate_id'     =>  8,
            'coordinate_name'   =>  'VIII系：新潟県　長野県　山梨県　静岡県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  138.5
        ]);
        Coordinate::create([
            'coordinate_id'     =>  9,
            'coordinate_name'   =>  'IX系：東京都（XIV系、XVIII系及びXIX系に規定する区域を除く。)　福島県　栃木県　茨城県　埼玉県 千葉県　群馬県　神奈川県',
            'origin_latitude'   =>  36,
            'origin_longitude'  =>  139.833333333333
        ]);
        Coordinate::create([
            'coordinate_id'     =>  10,
            'coordinate_name'   =>  'X系：青森県　秋田県　山形県　岩手県　宮城県',
            'origin_latitude'   =>  40,
            'origin_longitude'  =>  140.833333333333
        ]);
        Coordinate::create([
            'coordinate_id'     =>  11,
            'coordinate_name'   =>  'XI系：小樽市　函館市　伊達市　北斗市　北海道後志総合振興局の所管区域　北海道胆振総合振興局の所管区域のうち豊浦町、壮瞥町及び洞爺湖町　北海道渡島総合振興局の所管区域　北海道檜山振興局の所管区域',
            'origin_latitude'   =>  44,
            'origin_longitude'  =>  140.25
        ]);
        Coordinate::create([
            'coordinate_id'     =>  12,
            'coordinate_name'   =>  'XII系：北海道（XI系及びXIII系に規定する区域を除く。）',
            'origin_latitude'   =>  44,
            'origin_longitude'  =>  142.25
        ]);
        Coordinate::create([
            'coordinate_id'     =>  13,
            'coordinate_name'   =>  'XIII系：北見市　帯広市　釧路市　網走市　根室市　北海道オホーツク総合振興局の所管区域のうち美幌町、津別町、斜里町、清里町、小清水町、訓子府町、置戸町、佐呂間町及び大空町　北海道十勝総合振興局の所管区域　北海道釧路総合振興局の所管区域　北海道根室振興局の所管区域',
            'origin_latitude'   =>  44,
            'origin_longitude'  =>  144.25
        ]);
        Coordinate::create([
            'coordinate_id'     =>  14,
            'coordinate_name'   =>  'XIV系：東京都のうち北緯28度から南であり、かつ東経140度30分から東であり東経143度から西である区域',
            'origin_latitude'   =>  26,
            'origin_longitude'  =>  142
        ]);
        Coordinate::create([
            'coordinate_id'     =>  15,
            'coordinate_name'   =>  'XV系：沖縄県のうち東経126度から東であり、かつ東経130度から西である区域',
            'origin_latitude'   =>  26,
            'origin_longitude'  =>  127.5
        ]);
        Coordinate::create([
            'coordinate_id'     =>  16,
            'coordinate_name'   =>  'XVI系：沖縄県のうち東経126度から西である区域',
            'origin_latitude'   =>  26,
            'origin_longitude'  =>  124
        ]);
        Coordinate::create([
            'coordinate_id'     =>  17,
            'coordinate_name'   =>  'XVII系：沖縄県のうち東経130度から東である区域',
            'origin_latitude'   =>  26,
            'origin_longitude'  =>  131
        ]);
        Coordinate::create([
            'coordinate_id'     =>  18,
            'coordinate_name'   =>  'XVIII系：東京都のうち北緯28度から南であり、かつ東経140度30分から西である区域',
            'origin_latitude'   =>  20,
            'origin_longitude'  =>  136
        ]);
        Coordinate::create([
            'coordinate_id'     =>  19,
            'coordinate_name'   =>  'XIX系：東京都のうち北緯28度から南であり、かつ東経143度から東である区域',
            'origin_latitude'   =>  26,
            'origin_longitude'  =>  154
        ]);
    }
}
