<?php

namespace Spmartin\TableInheritance\Model\Behavior;

trait MatchesTrait
{
    /**
     * Checks rules match.
     *
     * @param  string $subject Subject
     * @param  array  $rules   Rules list
     * @return bool
     */
    protected function matches(string $subject, array $rules): bool
    {
        foreach ($rules as $rule) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($rule, '/')) . '$/';
            if (preg_match($pattern, $subject)) {
                return true;
            }
        }

        return false;
    }
}
