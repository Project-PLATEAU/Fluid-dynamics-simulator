/**
 * テーブル操作周りの処理
 */


// 行の背景色
const TR_BACKGROUD_COLOR = 'table-primary';

/**
 * テーブルのレコード行の背景色をリセット
 * @param {*} tblId テーブルID
 */
function resetBgTr(tblId)
{
    let tr = tblId + " tr";
    $(tr).removeClass(TR_BACKGROUD_COLOR);
}

/**
 * テーブルのレコード行選択時に背景色を設定
 * @param {*} tr レコード行
 */
function setBgTr(tr)
{
    $(tr).addClass(TR_BACKGROUD_COLOR);
}
