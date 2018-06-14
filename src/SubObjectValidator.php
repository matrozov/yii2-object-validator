<?php
namespace matrozov\yii2subObjectValidator;

use Yii;
use yii\base\Model;
use yii\base\DynamicModel;
use yii\validators\Validator;

class SubObjectValidator extends Validator
{
    public $rules = [];

    public $messageField = '{attribute}.{message}';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }
    }

    /**
     * @param Model  $model
     * @param string $attribute
     *
     * @return void
     * @throws
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!is_object($value) || !is_array($value) || !($value instanceof \ArrayAccess)) {
            $this->addError($model, $attribute, $this->message, []);
            return;
        }

        $attributes = [];

        foreach ($value as $key => $val) {
            $attributes[$key] = $val;
        }

        $dynModel = DynamicModel::validateData($attributes, $this->rules);

        foreach ($dynModel->errors as $errors) {
            foreach ($errors as $error) {
                $this->addError($model, $attribute, $this->messageField, ['message' => $error]);
            }
        }

        $model->$attribute = $attributes;
    }
}