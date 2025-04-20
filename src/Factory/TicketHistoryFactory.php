<?php

namespace App\Factory;

use App\Entity\TicketHistory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<TicketHistory>
 */
final class TicketHistoryFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return TicketHistory::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'changed_at' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'changed_by_id' => self::faker()->randomNumber(),
            'new_status' => self::faker()->text(50),
            'previous_status' => self::faker()->text(50),
            'ticket_id' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(TicketHistory $ticketHistory): void {})
        ;
    }
}
