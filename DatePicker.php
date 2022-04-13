<?php

/**
 * Addons and code snippets for Nette Framework. (unofficial)
 */

namespace LiborCinka;

use Nette;
use Nette\Forms;
use Nette\Utils\DateTime;

/**
 * Form control for selecting date.
 *
 *  – compatible with jQuery UI DatePicker and HTML 5
 *  – works with DateTime
 *
 */
class DatePicker extends Forms\Controls\BaseControl
{

	/** @link    http://dev.w3.org/html5/spec/common-microsyntaxes.html#valid-date-string */
	const W3C_DATE_FORMAT = 'j. n. Y';

	/** @var     DateTime|NULL     internal date reprezentation */
	protected $value;

	/** value entered by user (unfiltered) */
	protected ?string $rawValue;

	private string $className = 'date';


	/**
	 * Returns class name.
	 */
	public function getClassName(): string
	{
		return $this->className;
	}


	/**
	 * Sets class name for input element.
	 */
	public function setClassName(string $className): self
	{
		$this->className = $className;
		return $this;
	}


	/**
	 * Generates control's HTML element.
	 *
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();

		if (isset($control->attrs['class'])) {
			if (strpos($control->attrs['class'], $this->className) === false) {
				$control->attrs['class'] .= ' ' . $this->className;
			}
		} else {
			$control->attrs['class'] = $this->className;
		}

		list($min, $max) = $this->extractRangeRule($this->getRules());
		if ($min !== null) {
			$control->min = $min->format(self::W3C_DATE_FORMAT);
		}
		if ($max !== null) {
			$control->max = $max->format(self::W3C_DATE_FORMAT);
		}
		if ($this->value) {
			$control->value = $this->value->format(self::W3C_DATE_FORMAT);
		}
		return $control;
	}


	/**
	 * Sets DatePicker value.
	 *
	 * @param DateTime|int|string $value
	 */
	public function setValue($value): self
	{
		if ($value instanceof DateTime || $value instanceof \DateTime) {
		} elseif (is_int($value)) { // timestamp
		} elseif (empty($value)) {
			$rawValue = $value;
			$value = null;
		} elseif (is_string($value)) {
			$rawValue = $value;

			if (preg_match('#^(?P<dd>\d{1,2})[. -] *(?P<mm>\d{1,2})([. -] *(?P<yyyy>\d{4})?)?$#', $value, $matches)) {
				$dd = $matches['dd'];
				$mm = $matches['mm'];
				$yyyy = isset($matches['yyyy']) ? $matches['yyyy'] : date('Y');

				if (checkdate($mm, $dd, $yyyy)) {
					$value = "$yyyy-$mm-$dd";
				} else {
					$value = null;
				}
			}
		} else {
			throw new \InvalidArgumentException();
		}

		if ($value !== null) {
			// DateTime constructor throws Exception when invalid input given
			try {
				$value = DateTime::from($value); // clone DateTime when given
			} catch (\Exception $e) {
				$value = null;
			}
		}

		if (!isset($rawValue) && isset($value)) {
			$rawValue = $value->format(self::W3C_DATE_FORMAT);
		}

		$this->value = $value;
		$this->rawValue = $rawValue;

		return $this;
	}


	/**
	 * Returns unfiltered value.
	 */
	public function getRawValue(): string
	{
		return $this->rawValue;
	}


	/**
	 * Does user enter anything? (the value doesn't have to be valid)
	 *
	 * @param DatePicker $control
	 */
	public static function validateFilled(Forms\IControl $control): bool
	{
		if (!$control instanceof self) {
			throw new Nette\InvalidStateException('Unable to validate ' . get_class($control) . ' instance.');
		}
		$rawValue = $control->rawValue;
		return !empty($rawValue);
	}


	/**
	 * Is entered value valid? (empty value is also valid!)
	 *
	 * @param DatePicker $control
	 */
	public static function validateValid(Forms\IControl $control): bool
	{
		if (!$control instanceof self) {
			throw new Nette\InvalidStateException('Unable to validate ' . get_class($control) . ' instance.');
		}
		$value = $control->value;
		return (empty($control->rawValue) || $value instanceof DateTime);
	}

	/**
	 * Is entered value valid? (empty value is also valid!)
	 *
	 * @param DatePicker $control
	 */
	public static function validateRegexp(Forms\IControl $control): bool
	{
		if (!$control instanceof self) {
			throw new Nette\InvalidStateException('Unable to validate ' . get_class($control) . ' instance.');
		}
		
		$value = $control->value;
		return (empty($control->rawValue) || $value instanceof DateTime);
	}
	/**
	 * Is entered values within allowed range?
	 *
	 * @param DatePicker $control
	 * @param array $range 0 => minDate, 1 => maxDate
	 */
	public static function validateRange(Forms\IControl $control, array $range): bool
	{
		return Nette\Utils\Validators::isInRange($control->getValue(), $range);
	}


	/**
	 * Finds minimum and maximum allowed dates.
	 *
	 * @return array $rules 0 => DateTime|null $minDate, 1 => DateTime|null $maxDate
	 */
	private function extractRangeRule(Forms\Rules $rules): array
	{
		$controlMin = $controlMax = null;
		foreach ($rules as $rule) {
			if ($rule->type === Forms\Rule::VALIDATOR) {
				if ($rule->operation === Forms\Form::RANGE && !$rule->isNegative) {
					$ruleMinMax = $rule->arg;
				}
			} elseif ($rule->type === Forms\Rule::CONDITION) {
				if ($rule->operation === Forms\Form::FILLED && !$rule->isNegative && $rule->control === $this) {
					$ruleMinMax = $this->extractRangeRule($rule->subRules);
				}
			}

			if (isset($ruleMinMax)) {
				list($ruleMin, $ruleMax) = $ruleMinMax;
				if ($ruleMin !== null && ($controlMin === null || $ruleMin > $controlMin)) {
					$controlMin = $ruleMin;
				}
				if ($ruleMax !== null && ($controlMax === null || $ruleMax < $controlMax)) {
					$controlMax = $ruleMax;
				}
				$ruleMinMax = null;
			}
		}
		return array($controlMin, $controlMax);
	}
}
