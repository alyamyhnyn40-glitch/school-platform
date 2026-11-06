<?php
declare(strict_types=1);
namespace App\Core;
final class HttpException extends \RuntimeException
{
    /** الصلاحية المطلوبة (لسجل الأمان) عند 403 */
    public ?string $perm = null;
    public function __construct(public int $status, string $message = '', public array $fields = []) { parent::__construct($message, $status); }
    public function withPerm(?string $perm): self { $this->perm = $perm; return $this; }
}
