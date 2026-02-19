<?php

declare(strict_types=1);

namespace Taketool\Leadmagnet\Controller;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Address;
use Taketool\Leadmagnet\Domain\Model\Lead;
use Taketool\Leadmagnet\Domain\Repository\LeadRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
        $newsletter = (bool)($arguments['newsletter'] ?? false);

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

        // Check for duplicate
        if ($this->leadRepository->existsByEmailAndContentElement($email, $contentElementUid)) {
            return new JsonResponse(['success' => true, 'code' => 'already_registered']);
        }

        // Generate token
        $token = bin2hex(random_bytes(32));

        // Store lead
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setToken($token);
        $lead->setContentElement($contentElementUid);
        $lead->setNewsletter($newsletter);
        $lead->setCrdate(time());
        $lead->setPid((int)$ttContentRow['pid']);
        $this->leadRepository->add($lead);
        $this->persistenceManager->persistAll();

        // Build download link
        $downloadLink = $this->uriBuilder
            ->reset()
            ->setTargetPageUid((int)$ttContentRow['pid'])
            ->setCreateAbsoluteUri(true)
            ->uriFor('download', ['token' => $token], 'Leadmagnet', 'Leadmagnet', 'Show');

        // Send email
        try {
            $fluidEmail = new FluidEmail();
            $fluidEmail
                ->setRequest($this->request)
                ->to(new Address($email))
                ->from(new Address(
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?: 'noreply@example.com',
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
            error_log('Leadmagnet mail error [' . get_class($e) . ']: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'code' => 'mail_failed',
                'message' => $e->getMessage(), // temporary: expose error for debugging
            ]);
        }

        return new JsonResponse(['success' => true]);
    }

    public function downloadAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $token = (string)($arguments['token'] ?? '');

        if ($token === '') {
            return $this->downloadError('download.error.invalid_token');
        }

        /** @var Lead|null $lead */
        $lead = $this->leadRepository->findByToken($token);

        if ($lead === null) {
            return $this->downloadError('download.error.invalid_token');
        }

        // Check token expiry (default 72 hours)
        $expiryHours = (int)($this->settings['tokenExpiryHours'] ?? 72);
        $expiryTime = $lead->getCrdate() + ($expiryHours * 3600);
        if (time() > $expiryTime) {
            return $this->downloadError('download.error.expired');
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
            return $this->downloadError('download.error.file_not_found');
        }

        $fileReference = $this->resourceFactory->getFileReferenceObject($fileReferences['uid']);
        $file = $fileReference->getOriginalFile();

        // Mark as downloaded
        $lead->setDownloaded(true);
        $this->leadRepository->update($lead);
        $this->persistenceManager->persistAll();

        // Stream file directly, bypassing page rendering
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($file->getContents());
        $stream->rewind();

        $response = new Response(
            $stream,
            200,
            [
                'Content-Type' => $file->getMimeType(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName() . '"',
                'Content-Length' => (string)$file->getSize(),
            ]
        );

        throw new PropagateResponseException($response, 1);
    }

    private function downloadError(string $key): ResponseInterface
    {
        $message = LocalizationUtility::translate($key, 'Leadmagnet') ?? $key;
        return $this->htmlResponse('<p class="alert alert-warning">' . htmlspecialchars($message) . '</p>');
    }
}
