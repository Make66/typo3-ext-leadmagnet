<?php

declare(strict_types=1);

namespace Taketool\Leadmagnet\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

class LeadRepository extends Repository
{
    public function findByToken(string $token): ?object
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->equals('token', $token)
        );
        return $query->execute()->getFirst();
    }
}
