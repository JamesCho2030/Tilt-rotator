<?php
namespace App\Utils;

use InvalidArgumentException;

/**
 * Simple validator utility applying basic rules for request data.
 */
class Validator
{
    public static function validate(array $data, array $rules): void
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleSet) as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = '필수 입력 항목입니다.';
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = '유효한 이메일 주소가 아닙니다.';
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (float) substr($rule, 4);
                    if ($value !== null && (float) $value < $min) {
                        $errors[$field][] = "최소값은 {$min}입니다.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
    }
}
