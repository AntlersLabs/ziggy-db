<?php

declare(strict_types=1);

namespace AntlersLabs\ZiggyDb\Guardrails;

final class MysqlGrants
{
    /**
     * @var list<string>
     */
    private const array READ_ONLY_PRIVILEGES = ['SELECT', 'USAGE', 'SHOW VIEW', 'LOCK TABLES'];

    /**
     * @param  list<string>  $grants
     * @return list<string>
     */
    public static function violations(array $grants): array
    {
        $violations = [];

        foreach ($grants as $grant) {
            if (preg_match('/^GRANT\s+(.+?)\s+ON\s+/i', $grant, $matches) !== 1) {
                $violations['unrecognised grant: '.$grant] = true;

                continue;
            }

            foreach (explode(',', $matches[1]) as $privilege) {
                $privilege = strtoupper(trim($privilege));

                if (! in_array($privilege, self::READ_ONLY_PRIVILEGES, true)) {
                    $violations[$privilege] = true;
                }
            }
        }

        return array_keys($violations);
    }
}
