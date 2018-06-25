<?php
namespace matrozov\yii2subObjectValidator;

use Yii;
use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Class SubObjectValidator
 * @package matrozov\yii2subObjectValidator
 *
 * @property array   $rules
 * @property boolean $strictObject
 */
class SubObjectValidator extends Validator
{
    const SEPARATOR = '->';

    /**
     * @var array
     */
    public $rules = [];

    /**
     * @var bool
     */
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

        // Prepare rules
        foreach ($this->rules as $rule) {
            $fields = [];

            foreach ((array)$rule[0] as $field) {
                if (substr($field, 0, 1) === '!') {
                    $fields[] = '!' . $attribute . self::SEPARATOR . ltrim($field, '!');
                }
                else {
                    $fields[] = $attribute . self::SEPARATOR . $field;
                }
            }

            $rules[] = array_merge([$fields], array_slice($rule, 1));
        }

        $attributes = [];

        // Prepare attributes
        foreach ($value as $key => $val) {
            $attributes[$attribute . self::SEPARATOR . $key] = $val;
        }

        $subModel = new DynamicModel($attributes);

        // Set attribute labels
        $subModel->setAttributeLabels($model->attributeLabels());

        $validators = $subModel->getValidators();

        // Prepare validators
        foreach ($rules as $rule) {
            if ($rule instanceof Validator) {
                $validators->append($rule);
            }
            elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
                $validator = Validator::createValidator($rule[1], $model, (array)$rule[0], array_slice($rule, 2));
                $validators->append($validator);
            }
            else {
                throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
            }
        }

        $subModel->validate();

        // Transfer error messages
        foreach ($subModel->errors as $subAttribute => $errors) {
            $params = [];

            $subAttributeValue = $subModel->$subAttribute;

            if (is_array($subAttributeValue)) {
                $subAttributeValue = 'array()';
            } elseif (is_object($subAttributeValue) && !method_exists($subAttributeValue, '__toString')) {
                $subAttributeValue = '(object)';
            }

            foreach ($errors as $error) {
                $this->addError($model, $subAttribute, $error, [
                    'value' => $subAttributeValue,
                ]);
            }
        }

        $model->$attribute = $value;
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