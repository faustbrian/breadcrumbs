<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class ResolvableUser extends Model
{
    use HasFactory;

    #[Override()]
    protected $guarded = [];

    #[Override()]
    public function resolveRouteBinding($value, $field = null): mixed
    {
        return new self([
            'id' => $value,
            $this->getRouteKeyName() => $value,
        ]);
    }
}
