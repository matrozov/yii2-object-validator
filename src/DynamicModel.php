<?php
namespace matrozov\yii2subObjectValidator;

use yii\helpers\Inflector;

class DynamicModel extends \yii\base\DynamicModel
{
    protected $_labels;

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (parent::__isset($name)) {
            return parent::__get($name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return true;
    }

    /**
     * @param $labels
     */
    function setAttributeLabels($labels)
    {
        $this->_labels = $labels;
    }

    /**
     * {@inheritdoc}
     */
    function attributeLabels()
    {
        return $this->_labels;
    }

    /**
     * {@inheritdoc}
     */
    function generateAttributeLabel($name)
    {
        $result = explode(SubObjectValidator::SEPARATOR, $name);

        // Support separator
        foreach ($result as $idx => $name) {
            $result[$idx] = Inflector::camel2words($name, true);
        }

        return implode(SubObjectValidator::SEPARATOR, $result);
    }
}