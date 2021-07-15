<?php

require_once dirname(__FILE__)."/../lib/XCgiApi.php";

class XCgiLObject
{
    // �����o�[�ϐ�
    protected $_m_cStatus;

    // �R���X�g���N�^
    public function __construct()
    {
        $this->_m_cStatus = new XCgiStatusApi;
    }

    // �f�X�g���N�^
    public function __destruct()
    {
    }

    // �G���[���݊m�F
    protected function isExistError()
    {
        return $this->_m_cStatus->isExistError();
    }

    // �G���[���ݒ�
    protected function setError($in_id, $in_message, $in_file="", $in_line=0)
    {
        $this->_m_cStatus->setError($in_id, $in_message, $in_file, $in_line);
    }

    // �G���[ID�擾
    protected function getErrorId()
    {
        return $this->_m_cStatus->getErrorId();
    }

    // �G���[���b�Z�[�W�擾
    protected function getErrorMessage()
    {
        return $this->_m_cStatus->getErrorMessage();
    }

    // �G���[�����t�@�C���擾
    protected function getErrorFile()
    {
        return $this->_m_cStatus->getErrorFile();
    }

    // �G���[�������C���擾
    protected function getErrorLine()
    {
        return $this->_m_cStatus->getErrorLine();
    }

    // �O���p�G���[�R�[�h�擾
    protected function getExternalError($in_internalError)
    {
        return $this->_m_cStatus->getExternalError($in_internalError);
    }
}

?>
