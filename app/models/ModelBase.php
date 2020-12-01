<?php

/**
 * 模型基础类
 */
abstract class ModelBase extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->setup([
            'notNullValidations' => false, // 禁止非空验证
            'exceptionOnFailedSave' => true // 保存失败时输出异常
        ]);
    }
}
?>