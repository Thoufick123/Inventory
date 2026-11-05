<?php
/**
 * Input Validator
 * Validates request data and returns error messages.
 */

class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data, array $rules = []) {
        $this->data = $data;
        if (!empty($rules)) {
            $this->applyRules($rules);
        }
    }

    /**
     * Create a new Validator instance.
     */
    public static function make(array $data, array $rules = []): self {
        return new self($data, $rules);
    }

    /**
     * Apply rules from array definition.
     */
    private function applyRules(array $rules): void {
        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            foreach ($ruleList as $rule) {
                $rule = trim($rule);
                if ($rule === '') continue;

                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                match ($ruleName) {
                    'required'  => $this->required($field),
                    'email'     => $this->email($field),
                    'numeric'   => $this->numeric($field),
                    'minLength' => $this->minLength($field, (int)($params[0] ?? 0)),
                    'maxLength' => $this->maxLength($field, (int)($params[0] ?? 255)),
                    'min'       => $this->min($field, (float)($params[0] ?? 0)),
                    'max'       => $this->max($field, (float)($params[0] ?? PHP_INT_MAX)),
                    'in'        => $this->in($field, $params),
                    'date'      => $this->date($field, $params[0] ?? 'Y-m-d'),
                    'phone'     => $this->phone($field),
                    'gst'       => $this->gst($field),
                    'unique'    => $this->unique($field, $params[0] ?? '', $params[1] ?? null, isset($params[2]) ? (int)$params[2] : null),
                    default     => null,
                };
            }
        }
    }

    /**
     * Required field validation.
     */
    public function required(string $field, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field][] = "$label is required.";
        }
        return $this;
    }

    /**
     * Email format validation.
     */
    public function email(string $field, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$label must be a valid email address.";
        }
        return $this;
    }

    /**
     * Numeric validation.
     */
    public function numeric(string $field, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "$label must be a number.";
        }
        return $this;
    }

    /**
     * Minimum length validation.
     */
    public function minLength(string $field, int $min, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field][] = "$label must be at least $min characters.";
        }
        return $this;
    }

    /**
     * Maximum length validation.
     */
    public function maxLength(string $field, int $max, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field][] = "$label must not exceed $max characters.";
        }
        return $this;
    }

    /**
     * Value must be in a list of allowed values.
     */
    public function in(string $field, array $allowed, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "$label has an invalid value.";
        }
        return $this;
    }

    /**
     * Unique value in database table.
     */
    public function unique(string $field, string $table, string $column = null, ?int $exceptId = null, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $column = $column ?? $field;
        $value = $this->data[$field] ?? '';
        
        if ($value !== '' && !empty($table)) {
            $sql = "SELECT COUNT(*) as cnt FROM $table WHERE $column = ?";
            $params = [$value];
            
            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            
            $result = Database::fetchOne($sql, $params);
            if ($result && $result['cnt'] > 0) {
                $this->errors[$field][] = "$label already exists.";
            }
        }
        return $this;
    }

    /**
     * Minimum numeric value.
     */
    public function min(string $field, float $min, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && is_numeric($value) && (float)$value < $min) {
            $this->errors[$field][] = "$label must be at least $min.";
        }
        return $this;
    }

    /**
     * Maximum numeric value.
     */
    public function max(string $field, float $max, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && is_numeric($value) && (float)$value > $max) {
            $this->errors[$field][] = "$label must not exceed $max.";
        }
        return $this;
    }

    /**
     * Date format validation.
     */
    public function date(string $field, string $format = 'Y-m-d', string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '') {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field][] = "$label must be a valid date ($format).";
            }
        }
        return $this;
    }

    /**
     * Confirm field matches another field (e.g., password confirmation).
     */
    public function confirmed(string $field, string $confirmField = null, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $confirmField = $confirmField ?? $field . '_confirmation';
        $value = $this->data[$field] ?? '';
        $confirmValue = $this->data[$confirmField] ?? '';
        if ($value !== '' && $value !== $confirmValue) {
            $this->errors[$field][] = "$label confirmation does not match.";
        }
        return $this;
    }

    /**
     * Exact digits length (numeric only).
     */
    public function digits(string $field, int $length, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && $value !== null && (!ctype_digit((string)$value) || strlen((string)$value) !== $length)) {
            $this->errors[$field][] = "$label must be exactly $length numeric digits.";
        }
        return $this;
    }

    /**
     * Phone number validation (Indian format - 10 digits starting with 6, 7, 8, or 9).
     */
    public function phone(string $field, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && $value !== null) {
            $clean = preg_replace('/^(\+91|91)\s*/', '', trim((string)$value));
            $clean = preg_replace('/[\s\-\(\)]+/', '', $clean);
            if (!preg_match('/^[6-9]\d{9}$/', $clean)) {
                $this->errors[$field][] = "$label must be a valid 10-digit mobile number (digits only, starting with 6, 7, 8, or 9).";
            }
        }
        return $this;
    }

    /**
     * GST number validation.
     */
    public function gst(string $field, string $label = null): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[A-Z\d]{1}[Z]{1}[A-Z\d]{1}$/', $value)) {
            $this->errors[$field][] = "$label must be a valid GST number.";
        }
        return $this;
    }

    /**
     * Check if validation passed.
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Check if validation failed.
     */
    public function fails(): bool {
        return !empty($this->errors);
    }

    /**
     * Get all errors.
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * Get first error for a field or the first error overall.
     */
    public function firstError(?string $field = null): ?string {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }
        $all = $this->allErrors();
        return $all[0] ?? null;
    }

    /**
     * Get all error messages as a flat array.
     */
    public function allErrors(): array {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            $all = array_merge($all, $fieldErrors);
        }
        return $all;
    }

    /**
     * Get a validated input value with default.
     */
    public function getValue(string $field, $default = null) {
        return $this->data[$field] ?? $default;
    }
}
