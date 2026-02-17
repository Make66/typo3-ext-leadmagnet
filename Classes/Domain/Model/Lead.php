<?php

declare(strict_types=1);

namespace Taketool\Leadmagnet\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Lead extends AbstractEntity
{
    protected int $crdate = 0;

    protected string $email = '';

    protected string $token = '';

    protected int $contentElement = 0;

    protected bool $downloaded = false;

    public function getCrdate(): int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getContentElement(): int
    {
        return $this->contentElement;
    }

    public function setContentElement(int $contentElement): void
    {
        $this->contentElement = $contentElement;
    }

    public function isDownloaded(): bool
    {
        return $this->downloaded;
    }

    public function setDownloaded(bool $downloaded): void
    {
        $this->downloaded = $downloaded;
    }
}
