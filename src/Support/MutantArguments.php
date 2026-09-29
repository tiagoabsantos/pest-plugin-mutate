<?php

declare(strict_types=1);

namespace Pest\Mutate\Support;

/**
 * The arguments of the original run, reduced to what a mutant's subprocess can accept.
 *
 * A mutant runs one PHPUnit process, never ParaTest, so every ParaTest-only option has to
 * go. Left in, PHPUnit aborts with `Unknown option "--processes"` before running a single
 * test, and that exit code is indistinguishable from a killed mutant — so the mutant is
 * counted as tested and the score reads 100%.
 *
 * @see https://github.com/pestphp/pest/issues/1937
 */
final class MutantArguments
{
    /**
     * ParaTest's own options, mapped to whether they take a value.
     *
     * Only the options ParaTest adds on top of PHPUnit are listed: the ones it merely
     * forwards are valid for the mutant too.
     *
     * @var array<string, bool>
     */
    private const array PARATEST_ONLY = [
        '--functional' => false,
        '--max-batch-size' => true,
        '--max-processes' => true,
        '--no-test-tokens' => false,
        '--passthru-php' => true,
        '--processes' => true,
        '--runner' => true,
        '--shard-test-distribution' => true,
        '--shard-test-distribution-seed' => true,
        '--tmp-dir' => true,
        '--verbose' => false,
        '-m' => true,
    ];

    /**
     * @param  array<int, string>  $originalArguments
     * @return array<int, string>
     */
    public static function from(array $originalArguments): array
    {
        $arguments = [];
        $dropNextArgument = false;

        foreach ($originalArguments as $argument) {
            if ($dropNextArgument) {
                $dropNextArgument = false;

                continue;
            }

            // the mutant reuses the coverage the original run already collected
            if (str_starts_with($argument, '--coverage')) {
                continue;
            }

            $name = explode('=', $argument, 2)[0];

            if (! array_key_exists($name, self::PARATEST_ONLY)) {
                $arguments[] = $argument;

                continue;
            }

            // `--processes 2` spends two arguments, and a stray `2` would be read as a path
            $dropNextArgument = self::PARATEST_ONLY[$name] && ! str_contains($argument, '=');
        }

        return $arguments;
    }
}
