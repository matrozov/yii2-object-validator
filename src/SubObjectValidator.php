<?php
namespace matrozov\yii2subObjectValidator;

use Yii;
use yii\base\Model;
use yii\base\DynamicModel;
use yii\validators\Validator;

/**
 * Class SubObjectValidator
 * @package matrozov\yii2subObjectValidator
 */
class SubObjectValidator extends Validator
{
    const SEPARATOR = '·';

    public $rules = [];

    public $strictObject = false;

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

        if (($this->strictObject && !is_object($value)) || (!$this->strictObject && !is_object($value) && !is_array($value) && !($value instanceof \ArrayAccess))) {
            $this->addError($model, $attribute, $this->message, []);
            return;
        }

        $rules = [];

        foreach ($this->rules as $rule) {
            $fields = [];

            foreach ((array)$rule[0] as $field) {
                $fields[] = $attribute . self::SEPARATOR . $field;
            }

            $rules[] = array_merge([$fields], array_slice($rule, 1));
        }

        $attributes = [];

        foreach ($value as $key => $val) {
            $attributes[$attribute . self::SEPARATOR . $key] = $val;
        }

        $dynModel = DynamicModel::validateData($attributes, $rules);

        foreach ($dynModel->errors as $errors) {
            foreach ($errors as $error) {
                $this->addError($model, $attribute, $error);
            }
        }

        $model->$attribute = $attributes;
    }

    /**
     * @param mixed $value
     *
     * @return array|null
     * @throws
     */
    public function validateValue($value)
    {
        if (!is_object($value) && !is_array($value) && !($value instanceof \ArrayAccess)) {
            return [$this->message, []];
        }

        $dynModel = DynamicModel::validateData($value, $this->rules);

        if ($dynModel->hasErrors()) {
            return [reset(reset($dynModel->errors)), []];
        }

        return null;
    }
}