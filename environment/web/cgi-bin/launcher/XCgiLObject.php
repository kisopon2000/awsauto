<?php

require_once dirname(__FILE__)."/../lib/XCgiApi.php";

class XCgiLObject
{
    // メンバー変数
    protected $_m_cStatus;

    // コンストラクタ
    public function __construct()
    {
        $this->_m_cStatus = new XCgiStatusApi;
    }

    // デストラクタ
    public function __destruct()
    {
    }

    // エラー存在確認
    protected function isExistError()
    {
        return $this->_m_cStatus->isExistError();
    }

    // エラー情報設定
    protected function setError($in_id, $in_message, $in_file="", $in_line=0)
    {
        $this->_m_cStatus->setError($in_id, $in_message, $in_file, $in_line);
    }

    // エラーID取得
    protected function getErrorId()
    {
        return $this->_m_cStatus->getErrorId();
    }

    // エラーメッセージ取得
    protected function getErrorMessage()
    {
        return $this->_m_cStatus->getErrorMessage();
    }

    // エラー発生ファイル取得
    protected function getErrorFile()
    {
        return $this->_m_cStatus->getErrorFile();
    }

    // エラー発生ライン取得
    protected function getErrorLine()
    {
        return $this->_m_cStatus->getErrorLine();
    }

    // 外部用エラーコード取得
    protected function getExternalError($in_internalError)
    {
        return $this->_m_cStatus->getExternalError($in_internalError);
    }
}

?>
