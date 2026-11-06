<?php
declare(strict_types=1);
namespace App\Core;

/** قواعد: required|min:n|max:n|email|phone|numeric|in:a,b|date|same:field|regex:... */
final class Validator
{
    public array $errors = [];
    public function __construct(private array $data, private array $rules, private array $labels = []) { $this->run(); }

    public static function make(array $data, array $rules, array $labels = []): self { return new self($data, $rules, $labels); }
    public function fails(): bool { return $this->errors !== []; }
    public function validated(): array
    {
        $o = [];
        foreach (array_keys($this->rules) as $k) { if (array_key_exists($k, $this->data)) { $o[$k] = is_string($this->data[$k]) ? trim($this->data[$k]) : $this->data[$k]; } }
        return $o;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleStr) {
            $val = $this->data[$field] ?? null;
            $val = is_string($val) ? trim($val) : $val;
            $label = $this->labels[$field] ?? $field;
            $rules = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            $empty = $val === null || $val === '';
            foreach ($rules as $r) {
                [$name, $arg] = array_pad(explode(':', $r, 2), 2, null);
                if ($name === 'required' && $empty) { $this->add($field, "الحقل «$label» مطلوب"); break; }
                if ($empty) { continue; }
                $err = match ($name) {
                    'min' => mb_strlen((string)$val) < (int)$arg ? "«$label» يجب ألا يقل عن $arg أحرف" : null,
                    'max' => mb_strlen((string)$val) > (int)$arg ? "«$label» يجب ألا يزيد عن $arg حرفًا" : null,
                    'email' => filter_var($val, FILTER_VALIDATE_EMAIL) ? null : "«$label» بريد إلكتروني غير صالح",
                    'phone' => preg_match('/^\+?[0-9]{7,15}$/', (string)$val) ? null : "«$label» رقم هاتف غير صالح",
                    'numeric' => is_numeric($val) ? null : "«$label» يجب أن يكون رقمًا",
                    'in' => in_array((string)$val, explode(',', (string)$arg), true) ? null : "قيمة «$label» غير مسموحة",
                    'date' => (\DateTime::createFromFormat('Y-m-d', (string)$val) !== false) ? null : "«$label» تاريخ غير صالح",
                    'same' => ($this->data[$arg] ?? null) === $val ? null : "«$label» غير مطابق",
                    'regex' => preg_match((string)$arg, (string)$val) ? null : "«$label» بصيغة غير صحيحة",
                    default => null,
                };
                if ($err) { $this->add($field, $err); break; }
            }
        }
    }
    private function add(string $f, string $m): void { $this->errors[$f][] = $m; }
    public function firstErrors(): array { return array_map(fn($e) => $e[0], $this->errors); }
}
