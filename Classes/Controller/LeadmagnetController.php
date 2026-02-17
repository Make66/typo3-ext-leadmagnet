<?php

declare(strict_types=1);

namespace Taketool\Leadmagnet\Controller;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Address;
use Taketool\Leadmagnet\Domain\Model\Lead;
use Taketool\Leadmagnet\Domain\Repository\LeadRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class LeadmagnetController extends ActionController
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ConnectionPool $connectionPool,
        private readonly ResourceFactory $resourceFactory,
    ) {}

    public function showAction(): ResponseInterface
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        $this->view->assignMultiple([
            'data' => $contentObject->data,
            'uid' => $contentObject->data['uid'],
        ]);
        return $this->htmlResponse();
    }

    public function submitAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $email = trim((string)($arguments['email'] ?? ''));
        $contentElementUid = (int)($arguments['contentElementUid'] ?? 0);

        // Honeypot check
        $sweets = (string)($arguments['sweets'] ?? '');
        if ($sweets !== '') {
            return new JsonResponse(['success' => true]);
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'code' => 'invalid_email',
                'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            ]);
        }

        // Validate content element exists
        if ($contentElementUid <= 0) {
            return new JsonResponse([
                'success' => false,
                'code' => 'unknown',
                'message' => 'Ein Fehler ist aufgetreten.',
            ]);
        }

        // Look up content element to verify it exists
        $ttContentRow = $this->connectionPool
            ->getConnectionForTable('tt_content')
            ->select(['uid', 'pid'], 'tt_content', ['uid' => $contentElementUid])
            ->fetchAssociative();

        if (!$ttContentRow) {
            return new JsonResponse([
                'success' => false,
                'code' => 'unknown',
                'message' => 'Ein Fehler ist aufgetreten.',
            ]);
        }

        // Generate token
        $token = bin2hex(random_bytes(32));

        // Store lead
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setToken($token);
        $lead->setContentElement($contentElementUid);
        $lead->setCrdate(time());
        $lead->setPid((int)$ttContentRow['pid']);
        $this->leadRepository->add($lead);
        $this->persistenceManager->persistAll();

        // Build download link
        $downloadTypeNum = (int)($this->settings['downloadTypeNum'] ?? 1730287466);
        $baseUrl = $this->request->getAttribute('normalizedParams')->getSiteUrl();
        $downloadLink = $baseUrl . '?type=' . $downloadTypeNum . '&tx_leadmagnet_show[token]=' . $token;

        // Send email
        try {
            $fluidEmail = new FluidEmail();
            $fluidEmail
                ->setRequest($this->request)
                ->to(new Address($email))
                ->from(new Address(
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? 'noreply@example.com',
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? ''
                ))
                ->subject($this->settings['emailSubject'] ?? 'Ihr Download-Link')
                ->format(FluidEmail::FORMAT_BOTH)
                ->setTemplate('LeadmagnetDownload')
                ->assignMultiple([
                    'downloadLink' => $downloadLink,
                    'email' => $email,
                ]);
            GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'code' => 'mail_failed',
                'message' => 'E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es erneut.',
            ]);
        }

        return new JsonResponse(['success' => true]);
    }

    public function downloadAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $token = (string)($arguments['token'] ?? '');

        if ($token === '') {
            return $this->htmlResponse('<h1>Ungültiger Link</h1><p>Der Download-Link ist ungültig.</p>');
        }

        /** @var Lead|null $lead */
        $lead = $this->leadRepository->findByToken($token);

        if ($lead === null) {
            return $this->htmlResponse('<h1>Ungültiger Link</h1><p>Der Download-Link ist ungültig.</p>');
        }

        // Check token expiry (default 72 hours)
        $expiryHours = (int)($this->settings['tokenExpiryHours'] ?? 72);
        $expiryTime = $lead->getCrdate() + ($expiryHours * 3600);
        if (time() > $expiryTime) {
            return $this->htmlResponse('<h1>Link abgelaufen</h1><p>Der Download-Link ist abgelaufen. Bitte fordern Sie einen neuen an.</p>');
        }

        // Get file reference from FlexForm downloadFile field
        $fileReferences = $this->connectionPool
            ->getConnectionForTable('sys_file_reference')
            ->select(
                ['uid'],
                'sys_file_reference',
                [
                    'uid_foreign' => $lead->getContentElement(),
                    'tablenames' => 'tt_content',
                    'fieldname' => 'settings.downloadFile',
                    'deleted' => 0,
                ],
                orderBy: ['sorting_foreign' => 'ASC']
            )
            ->fetchAssociative();

        if (!$fileReferences) {
            return $this->htmlResponse('<h1>Datei nicht gefunden</h1><p>Die Datei konnte nicht gefunden werden.</p>');
        }

        $fileReference = $this->resourceFactory->getFileReferenceObject($fileReferences['uid']);
        $file = $fileReference->getOriginalFile();

        // Mark as downloaded
        $lead->setDownloaded(true);
        $this->leadRepository->update($lead);
        $this->persistenceManager->persistAll();

        // Stream the file
        return new Response(
            200,
            [
                'Content-Type' => $file->getMimeType(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName() . '"',
                'Content-Length' => (string)$file->getSize(),
            ],
            $file->getContents()
        );
    }
}
