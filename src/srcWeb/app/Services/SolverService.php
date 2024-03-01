<?php


namespace App\Services;

use App\Commons\Constants;
use App\Commons\Message;
use App\Models\Db\Solver;
use App\Utils\DatetimeUtil;
use App\Utils\FileUtil;
use App\Utils\LogUtil;
use Faker\Core\Uuid;
use Illuminate\Http\Request;

/**
 * 熱流体解析ソルバサービス
 */
class SolverService extends BaseService
{

    /**
     *
     * 熱流体解析ソルバ一覧を取得
     *
     * @param string $login_user_id ログインユーザ
     * @return \App\Models\DB\Solver
     */
    public static function getSolverList($login_user_id)
    {
            $solverList = Solver::where("user_id", $login_user_id) // 登録者がログインユーザである
            ->orWhere("disclosure_flag", true) // SC7公開フラグが有効である
            ->orderBy('preset_flag', 'desc') // プリセットフラグが有効である行を上部に表示する
            ->orderBy('upload_datetime', 'asc') // プリセットフラグの値が同一の行の表示順序は登録日時の昇順
            ->get();
        return $solverList;
    }

    /**
     *
     * 公開フラグが有効とする熱流体解析ソルバリストを取得
     *
     * @param string $login_user_id ログインユーザ
     * @return \App\Models\DB\Solver
     */
    public static function getAllSolver($login_user_id)
    {
        $solverList = Solver::where("user_id", $login_user_id)
            ->orWhere("disclosure_flag", true) // 「熱流体解析ソルバテーブル]の公開フラグが有効である。
            ->orderBy('disclosure_flag', 'desc') // 公開フラグが有効である行を上部に表示する
            ->orderBy('upload_datetime', 'asc') // 公開フラグの値が同一の行の表示順序は登録日時の昇順
            ->get();
        return $solverList;
    }

    /**
     *
     * 熱流体解析ソルIDでレコード取得
     *
     * @param Uuid $id 熱流体解析ソルID
     *
     * @return \App\Models\Db\Solver
     */
    public static function getSolverById($id)
    {
        $solver = Solver::find($id);
        return $solver;
    }

    /**
     * ソルバ一式圧縮ファイルをアップロード
     * @param Uuid $solver_id 熱流体解析ソルID
     * @param Request $solver_compressed_file_rq 選択したtarファイル
     *
     * @return
     */
    public static function uploadTarFile($solver_id, $solver_compressed_file_rq)
    {
        $solverCompressedFile = $solver_compressed_file_rq->getClientOriginalName();
        // comperssed_solver/<solver_id>
        $comperssedSolverRelativePath = FileUtil::COMPRESSED_SOLVER_FOLDER . "/" . $solver_id . "/";
        FileUtil::upload($solver_compressed_file_rq, $comperssedSolverRelativePath, $solverCompressedFile);
        // comperssed_solver/<solver_id>/tarファイル名
        $tarFileRelativePath = $comperssedSolverRelativePath . $solverCompressedFile;
        return $tarFileRelativePath;
    }

    /**
     * 熱流体解析ソルの新規追加
     * @param string $user_id ログインユーザ
     * @param string $solver_name 識別名
     * @param Request $solver_compressed_file_rq 選択したソルバ一式圧縮ファイル
     * @param string $explanation 説明
     *
     * @return \App\Models\Db\Solver 熱流体解析ソルバ
     */
    public static function addNewSolver($user_id, $solver_name, $solver_compressed_file_rq, $explanation)
    {
        // 新規追加
        $solver = new Solver();
        $solver->solver_name = $solver_name;

        $solver->user_id = $user_id;
        // 登録日時を現在日時とする
        $solver->upload_datetime = DatetimeUtil::getNOW();
        $solver->preset_flag = false;
        $solver->disclosure_flag = false;
        $solver->explanation = $explanation;
        $solver->save();

        // ソルバ一式圧縮ファイルをアップロード
        $tarFileRelativePath = self::uploadTarFile($solver->solver_id, $solver_compressed_file_rq);

        // アップロードしたファイルパス保存
        $solver->solver_compressed_file = $tarFileRelativePath;
        $solver->save();
        LogUtil::i("[solver] [insert] [solver_name: {$solver_name}, solver_compressed_file: {$tarFileRelativePath}, user_id: {$user_id}, upload_datetime: {$solver->upload_datetime}, preset_flag: false, disclosure_flag: false, explanation: {$explanation}]");
        return $solver;
    }

    /**
     * 熱流体解析ソルの更新
     * @param \App\Models\Db\Solver $solver 熱流体解析ソルバ
     * @param string $solver_name 更新用の識別名
     * @param Request $solver_compressed_file_rq 選択したソルバ一式圧縮ファイル
     * @param string $explanation 更新用の説明
     *
     * @return \App\Models\Db\Solver
     */
    public static function updateSolver($solver, $solver_name, $solver_compressed_file_rq, $explanation)
    {
        // 識別名
        $solver->solver_name = $solver_name;

        // ソルバ一式圧縮ファイルをアップロード
        $tarFileRelativePath = self::uploadTarFile($solver->solver_id, $solver_compressed_file_rq);
        // アップロードしたファイルパス更新
        $solver->solver_compressed_file = $tarFileRelativePath;
        // 登録日時を現在日時とする
        $solver->upload_datetime = DatetimeUtil::getNOW();
        // 説明
        $solver->explanation = $explanation;

        $solver->save();
        LogUtil::i("[solver] [update] [solver_id: {$solver->solver_id}, , solver_name : {$solver_name}, solver_compressed_file: {$tarFileRelativePath}, upload_datetime: {$solver->upload_datetime}, explanation: {$explanation}]");
        return $solver;
    }

    /**
     * 熱流体解析ソルの公開
     * @param \App\Models\Db\Solver $solver 熱流体解析ソル
     *
     * @return \App\Models\Db\Solver
     */
    public static function publicSolver($solver)
    {
        $solver->disclosure_flag = true;
        $solver->save();
        LogUtil::i("[solver] [update] [solver_id: {$solver->solver_id}, disclosure_flag: true]");
        return $solver;
    }

    /**
     * 熱流体解析ソルの削除
     * @param Uuid $solver_id 熱流体解析ソル
     *
     * @return
     */
    public static function deleteSolver($solver_id)
    {
        $solver = self::getSolverById($solver_id);
        $solver->delete();
        LogUtil::i("[solver] [delete] [solver_id: {$solver_id}]");
    }
}
