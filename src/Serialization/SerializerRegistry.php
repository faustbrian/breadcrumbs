<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Breadcrumbs\Serialization;

use Cline\Breadcrumbs\Contracts\BreadcrumbSerializer;
use Cline\Breadcrumbs\Exceptions\InvalidBreadcrumbSerializerException;
use Cline\Breadcrumbs\Exceptions\MissingBreadcrumbSerializerException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;

use function throw_unless;

/**
 * Resolves configured breadcrumb serializers by format.
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
#[Singleton()]
final readonly class SerializerRegistry
{
    /**
     * @param array<string, class-string> $serializers
     */
    public function __construct(
        private Container $container,
        #[Config('breadcrumbs.serializers', [])]
        private array $serializers,
    ) {}

    /**
     * @throws InvalidBreadcrumbSerializerException
     * @throws MissingBreadcrumbSerializerException
     */
    public function resolve(string $format): BreadcrumbSerializer
    {
        throw_unless(isset($this->serializers[$format]), MissingBreadcrumbSerializerException::forFormat($format));

        $serializerClass = $this->serializers[$format];
        $serializer = $this->container->make($serializerClass);

        throw_unless(
            $serializer instanceof BreadcrumbSerializer,
            InvalidBreadcrumbSerializerException::forFormat($format, $serializerClass),
        );

        return $serializer;
    }
}
