<?php

namespace App\Commons;


/**
 * メッセージ定義用のクラス
 */
class Message
{
    //######################################################################################################################
    // エラーメッセージ
    //######################################################################################################################
    public static $E1 = "IDまたはパスワードは不正です。";
    public static $E2 = "対象行を選択してください。";
    public static $E3 = "対象行についてこの操作を行う権限がありません。";
    public static $E4 = "対象行についてシミュレーションが実行されていません。";
    public static $E5 = "対象行についてシミュレーションの実行ステータスが「%s」ではありません。";
    public static $E6 = "このユーザには既にこのモデルが共有されています。";
    public static $E7 = "IDは不正です。";
    public static $E8 = "このモデルはプリセットされています。";
    public static $E9 = "識別名が未入力です。";
    public static $E10 = "識別名が既存のモデルと重複しています。";
    public static $E11 = "3D都市モデルデータが選択されていません。";
    public static $E12 = "3D都市モデルの登録に失敗しました。%s" ;

    public static $E13 = "シミュレーションモデル「{0}」において温熱環境シミュレーションに必要な外力データが正しく入力されていません。<br>{1}" ;
    // public static $E14 = "この3D都市モデルについてCityGMLファイルが未登録です。" ;// 決番
    public static $E15 = "平面直角座標系が選択されていません。" ;
    public static $E16 = "STLファイルが選択されていません。" ;
    public static $E17 = "この3D都市モデルについてSTLファイルが未登録です。" ;
    public static $E18 = "解析に必要なSTLファイルが%sに登録されていません。" ;
    public static $E19 = "%sに実数値が入力されていません。" ;
    public static $E20 = "時刻を0以上23以下の整数値で入力してください。" ;
    public static $E21 = "緯度と経度の実数値を半角カンマ区切りで入力してください。" ;
    public static $E22 = "3D Tilesが選択されていません。" ;
    public static $E23 = "解析範囲の緯度の入力が不正です。<br>南端には%s以上で北端以下の値を設定してください。<br>北端には南端以上で%s以下の値を設定してください。" ;
    public static $E24 = "解析範囲の経度の入力が不正です。<br>西端には%s以上で東端以下の値を設定してください。<br>東端には西端以上で%s以下の値を設定してください。" ;
    public static $E25 = "解析範囲の高度の入力が不正です。<br>地面高度には%s以上で上空高度以下の値を設定してください。<br>上空高度には地面高度以上で%s以下の値を設定してください。" ;
    public static $E26 = "熱流体解析ソルバ一式を圧縮したTARファイルが選択されていません。" ;
    public static $E27 = "選択されている熱流体解析ソルバを使用するシミュレーションモデルが存在しているため、削除できません。<br>%s" ;
    public static $E28 = "選択されているシミュレーションモデルで試用されている熱流体解析ソルバ「%s」が公開されていません。" ;
    public static $E29 = "このシミュレーションモデルについては既にシミュレーションを実行済です。<br>外力等環境条件を変更してシミュレーションを実行したい場合には、このモデルを複製してください。" ;
    public static $E30 = "選択されているシミュレーションモデルは一般公開されていません。" ;

    //######################################################################################################################
    // 警告メッセージ
    //######################################################################################################################
    public static $W1 = "%sを削除してよろしいですか？";
    public static $W2 = "%sにこのモデルを共有しますか？";
    public static $W3 = "%sに開始したシミュレーションを中止してよろしいですか？";

    //######################################################################################################################
    // 情報メッセージ
    //######################################################################################################################
    public static $I1 = "%sに開始したシミュレーションの実行ステータスは「%s」です。<br>%s";
    public static $I2 = "シミュレーションを中止しました。";
    public static $I3 = "シミュレーションを開始しました。";
    public static $I4 = "このソルバは既に公開されています。";
    public static $I5 = "このシミュレーションモデルについては既にシミュレーションを実行しています。そのため外力等環境条件を変更することはできません。<br>外力等環境条件を変更してシミュレーションを実行したい場合には、このモデルを複製してください。";
    public static $I6 = "シミュレーションモデルを一般公開しました。<br>%s";
    public static $I7 = "このシミュレーションモデルは一般公開中です。<br>%s";
    public static $I8 = "シミュレーションモデルの一般公開を停止しました。";

    //######################################################################################################################
    // 質疑メッセージ
    //######################################################################################################################
    public static $Q1 = "%sに開始したシミュレーションが既に実行中であるため、開始できませんでした。実行中のシミュレーションを中止しますか？";
    public static $Q2 = "%sに開始したシミュレーションの実行ステータスは「%s」です。<br>%s<br>熱流体解析のエラーログファイルをダウンロードしますか？";
}