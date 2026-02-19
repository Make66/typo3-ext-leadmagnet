<?php

declare(strict_types=1);

namespace Taketool\Leadmagnet\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

class LeadRepository extends Repository
{
    public function existsByEmailAndContentElement(string $email, int $contentElementUid): bool
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('email', $email),
                $query->equals('contentElement', $contentElementUid)
            )
        );
        return $query->execute()->count() > 0;
    }

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
