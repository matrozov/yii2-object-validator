<?php
namespace matrozov\yii2subObjectValidator;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\Validator;

class SubObjectValidator extends Validator
{
    public $rules = [];

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
     * @param array|Validator $rule
     * @param null $model
     *
     * @return Validator
     * @throws
     */
    private function getValidator($model, $rule)
    {
        if ($rule instanceof Validator) {
            return $rule;
        }
        elseif (is_array($rule) && isset($rule[0], $rule[1])) {
            return Validator::createValidator($rule[1], $model, (array)$rule[0], array_slice($rule, 2));
        }

        throw new InvalidConfigException('Invalid validation rule: a rule must be an array specifying validator type.');
    }

    /**
     * @param Model  $model
     * @param string $attribute
     *
     * @return void
     * @throws InvalidConfigException
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!is_object($value) || !is_array($value) || !($value instanceof \ArrayAccess)) {
            $this->addError($model, $attribute, $this->message, []);
            return;
        }

        foreach ($this->rules as $rule) {
            $validator = $this->getValidator($model, $rule);

            foreach ($validator->attributes as $attribute) {
                if (!isset($value->$attribute)) {
                    $this->addError($model, $attribute, $this->message, []);

                    continue;
                }

                $model->$attribute = $value->$attribute;

                $validator->validateAttribute($model, $attribute);

                $value->$attribute = $model->$attribute;
            }
        }

        $model->$attribute = $value;
    }

    /**
     * @param $value
     *
     * @return array|null
     * @throws
     */
    public function validateValue($value)
    {
        if (!is_object($value) || !is_array($value) || !($value instanceof \ArrayAccess)) {
            return [$this->message, []];
        }

        $model = new Model();

        foreach ($this->rules as $rule) {
            $validator = $this->getValidator($model, $rule);

            foreach ($validator->attributes as $attribute) {
                if (!isset($value->$attribute)) {
                    return [$this->message, []];
                }

                if (($result = $validator->validateValue($value->$attribute)) !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}