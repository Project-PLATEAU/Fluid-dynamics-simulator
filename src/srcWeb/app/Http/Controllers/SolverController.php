<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use App\Commons\Message;
use App\Services\SolverService;
use App\Utils\FileUtil;
use App\Utils\LogUtil;
use App\Utils\StringUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * 熱流体解析ソルバ一覧画面用のコントロール
 */
class SolverController extends BaseController
{
    /**
     * 熱流体解析ソルバ一覧画面の初期表示
     */
    public function index()
    {
        try {
            $solverList = SolverService::getSolverList(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);

            // 他画面からのリダイレクトで渡されたデータを受け取る。
            $message = session('message') ? session('message') : null;
            $solverId = session('solverId') ? session('solverId') : null;
            return view('solver.index', compact('solverList', 'message', 'solverId'));
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 熱流体解析ソルバを新規追加
     * @param Request $request リクエスト
     *
     * @return
     */
    public function store(Request $request)
    {
        try {

            $errorMessage = [];

            $solverName = $request->solver_name;
            $solverCompressedFileRq = $request->file('solver_compressed_file');
            $explanation = $request->explanation;

            if (!$solverName) {
                // 識別名が未入力
                $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
            } else if (!$solverCompressedFileRq) {
                // ソルバ一式圧縮ファイルが未選択
                $errorMessage = ["type" => "E", "code" => "E26", "msg" => Message::$E26];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('solver.index')->with(['message' => $errorMessage]);
            } else {
                // ソルバーレコードを新規追加(※アップロード処理を含む)
                SolverService::addNewSolver(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id, $solverName, $solverCompressedFileRq, $explanation);
                return redirect()->route('solver.index');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 熱流体解析ソルバを更新
     * @param Request $request リクエスト
     * @param string $id 熱流体解析ソルバID
     *
     * @return
     */
    public function update(Request $request, string $id)
    {
        try {

            $errorMessage = [];

            $solver = null;

            // 更新用の識別名
            $solverName = $request->solver_name;
            // 更新用のソルバー式圧縮ファイル
            $solverCompressedFileRq = $request->file('solver_compressed_file');
            // 更新用の説明
            $explanation = $request->explanation;


            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else {
                $solver = SolverService::getSolverById($id);
                if (!$solver) {
                    throw new Exception("熱流体解析ソルバの更新に失敗しました。ソルバID「{$id}」のレコードが存在しません。");
                }
                if ($solver->preset_flag) {
                    // プリセットフラグが有効の場合、[E8]エラー
                    $errorMessage = ["type" => "E", "code" => "E8", "msg" => Message::$E8];
                } else if (!$solverCompressedFileRq) {
                    // ソルバ一式圧縮ファイルが未選択
                    $errorMessage = ["type" => "E", "code" => "E26", "msg" => Message::$E26];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('solver.index')->with(['message' => $errorMessage]);
            } else {
                SolverService::updateSolver($solver, $solverName, $solverCompressedFileRq, $explanation);
                return redirect()->route('solver.index');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 熱流体解析ソルバを公開
     * @param Request $request リクエスト
     * @param string $id 熱流体解析ソルバID
     *
     * @return
     */
    public function public(Request $request, string $id)
    {
        try {

            $errorMessage = [];
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('solver.index')->with(['message' => $errorMessage]);
            } else {
                $solver = SolverService::getSolverById($id);
                if ($solver->disclosure_flag) {
                    // プリセットフラグが有効の場合、[I4]を表示
                    $infoMessage = ["type" => "I", "code" => "I4", "msg" => Message::$I4];
                    return redirect()->route('solver.index')->with(['message' => $infoMessage]);
                } else {
                    SolverService::publicSolver($solver);
                    return redirect()->route('solver.index');
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 熱流体解析ソルバを削除
     * @param Request $request リクエスト
     * @param string $id 熱流体解析ソルバID
     *
     * @return
     */
    public function destroy(Request $request, string $id)
    {
        try {

            $isDeleteFlg = $request->query->get('delete_flg');
            if ($isDeleteFlg) {
                SolverService::deleteSolver($id);
                return redirect()->route('solver.index');
            } else {
                $errorMessage = [];

                $solver = null;

                if ($id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else {
                    $solver = SolverService::getSolverById($id);

                    if (!$solver) {
                        throw new Exception("熱流体解析ソルバの削除に失敗しました。ソルバID「{$id}」のレコードが存在しません。");
                    }

                    $simulationIdentificationNameList = $solver->simulation_models()->get()
                        ->map(function ($item) {
                            // シミュレーションモデルの識別名の配列を取得
                            return $item->identification_name;
                        })->toArray();
                    if (count($simulationIdentificationNameList) > 0) {
                        // シミュレーションモデルに紐づいているレコードが1つ以上存在する場合、[E27]エラーを表示
                        $e27Msg = sprintf(Message::$E27, StringUtil::arrayToString($simulationIdentificationNameList));
                        $errorMessage = ["type" => "E", "code" => "E27", "msg" => $e27Msg];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('solver.index')->with(['message' => $errorMessage]);
                } else {
                    $warningMessage = ["type" => "W", "code" => "W1", "msg" => sprintf(Message::$W1, $solver->solver_name)];
                    return redirect()->route('solver.index')->with(['message' => $warningMessage, 'solverId' => $id]);
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 熱流体解析ソルバをダウンロード
     * @param Request $request リクエスト
     * @param string $id 熱流体解析ソルバID
     *
     * @return
     */
    public function download(Request $request, string $id)
    {
        try {

            $errorMessage = [];

            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('solver.index')->with(['message' => $errorMessage]);
            } else {
                $solver = SolverService::getSolverById($id);
                if ($solver && $solver->solver_compressed_file && FileUtil::isExists($solver->solver_compressed_file)) {
                    return FileUtil::download($solver->solver_compressed_file);
                } else {
                    throw new Exception("{$solver->solver_name} のソルバ一式圧縮ファイルが存在しません。");
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }
}
