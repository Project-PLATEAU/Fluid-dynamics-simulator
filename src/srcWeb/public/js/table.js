/**
 * テーブル操作周りの処理
 */


// 行の背景色
const TR_BACKGROUD_COLOR = 'table-primary';

// 最大選択可能な行数
const MAX_SELECT_TR = 2;

// 選択した行数
let NUM_SELECTED_TR = 0;

/**
 * テーブルのレコード行の背景色をリセット
 * @param {*} tblId テーブルID
 * @param {*} bgColor 背景色
 */
function resetBgTr(tblId, bgColor = TR_BACKGROUD_COLOR)
{
    let tr = tblId + " tr";
    $(tr).removeClass(bgColor);
}

/**
 * テーブルのレコード行選択時に背景色を設定
 * @param {*} tr レコード行
 * @param {*} bgColor 背景色
 */
function setBgTr(tr, bgColor = TR_BACKGROUD_COLOR)
{
    $(tr).addClass(bgColor);
}

/**
 * テーブルのレコード行が既に選択されている場合は背景色を解除
 * @param {*} tr レコード行
 * @param {*} bgColor 背景色
 */
function removeBgTr(tr, bgColor = TR_BACKGROUD_COLOR) {
    $(tr).removeClass(bgColor);
}

/**
 * 選択した行に背景色が既に設定されているかを確認
 * @param {*} tr レコード行
 * @param {*} bgColor 背景色
 *
 * @return boolean
 *  既に設定されている場合、true
 *  設定されていない場合、false
 */
function isSettingBgTr(tr, bgColor = TR_BACKGROUD_COLOR) {
    return $(tr).hasClass(bgColor)
}

/**
 * 選択した行はテーブルヘッダーなのかどうかを確認
 * @param {*} table 対象テーブル
 *
 * @return boolean
 *  選択した行はテーブルヘッダーである場合、true
 *  選択した行はテーブルヘッダーでない場合、false
 */
function isHeader(tr) {
    return ($(tr).find("th").length > 0);
}
