<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Utils\PageSetup;
use KimaiPlugin\GuidelinesBundle\Service\GuidelinesSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The dashboard text, the required-description rule and the future-time
 * limit. "Preview" renders the posted text without saving it.
 */
#[Route(path: '/admin/guidelines')]
#[IsGranted('guidelines_edit')]
final class GuidelinesSettingsController extends AbstractController
{
    public function __construct(private readonly GuidelinesSettings $settings)
    {
    }

    #[Route(path: '', name: 'guidelines_settings', methods: ['GET'])]
    public function edit(): Response
    {
        return $this->renderSettings([
            'content' => $this->settings->getContent(),
            'description_required' => $this->settings->isDescriptionRequired(),
            'description_min_length' => $this->settings->getDescriptionMinLength(),
            'description_required_since' => $this->settings->getDescriptionRequiredSince() ?? '',
            'future_mode' => $this->settings->getFutureMode(),
            'future_minutes' => $this->settings->getFutureMinutes(),
        ], [], false);
    }

    #[Route(path: '', name: 'guidelines_settings_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('guidelines_settings', (string) $request->request->get('_token'))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('guidelines_settings');
        }

        // Normalise line endings so an unchanged text does not count as an update.
        $content = trim(str_replace("\r\n", "\n", (string) $request->request->get('content', '')));
        $required = $request->request->getBoolean('description_required');
        $minLength = $request->request->getInt('description_min_length');
        $since = trim((string) $request->request->get('description_required_since', ''));
        $futureMode = (string) $request->request->get('future_mode', GuidelinesSettings::FUTURE_MODE_KIMAI);
        $futureMinutes = $request->request->getInt('future_minutes');

        $values = [
            'content' => $content,
            'description_required' => $required,
            'description_min_length' => $minLength,
            'description_required_since' => $since,
            'future_mode' => $futureMode,
            'future_minutes' => $futureMinutes,
        ];

        if ($request->request->get('action') === 'preview') {
            return $this->renderSettings($values, [], true);
        }

        $errors = [];
        if (mb_strlen($content) > GuidelinesSettings::MAX_CONTENT_LENGTH) {
            $errors[] = ['key' => 'guidelines.error.content_too_long', 'params' => ['%max%' => GuidelinesSettings::MAX_CONTENT_LENGTH]];
        }
        if ($minLength < 1 || $minLength > GuidelinesSettings::MAX_MIN_LENGTH) {
            $errors[] = ['key' => 'guidelines.error.min_length', 'params' => ['%max%' => GuidelinesSettings::MAX_MIN_LENGTH]];
        }
        if ($since !== '' && !GuidelinesSettings::isDate($since)) {
            $errors[] = ['key' => 'guidelines.error.since_invalid', 'params' => []];
        }
        if ($required && $since === '') {
            $errors[] = ['key' => 'guidelines.error.since_required', 'params' => []];
        }

        if (!\in_array($futureMode, GuidelinesSettings::FUTURE_MODES, true)) {
            $errors[] = ['key' => 'guidelines.error.future_mode_invalid', 'params' => []];
        }
        if ($futureMinutes < 1 || $futureMinutes > GuidelinesSettings::MAX_FUTURE_MINUTES) {
            $errors[] = ['key' => 'guidelines.error.future_minutes', 'params' => ['%max%' => GuidelinesSettings::MAX_FUTURE_MINUTES]];
        }

        if ($errors !== []) {
            return $this->renderSettings($values, $errors, false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->settings->save($content, $required, $minLength, $since, $futureMode, $futureMinutes, $this->currentUser()->getDisplayName());
        $this->flashSuccess('guidelines.flash.saved');

        return $this->redirectToRoute('guidelines_settings');
    }

    /**
     * @param array{content: string, description_required: bool, description_min_length: int, description_required_since: string, future_mode: string, future_minutes: int} $values
     * @param list<array{key: string, params: array<string, int|string>}> $errors
     */
    private function renderSettings(array $values, array $errors, bool $preview, int $status = Response::HTTP_OK): Response
    {
        $page = new PageSetup('guidelines.title');
        $page->setActionName('guidelines_settings');

        $response = $this->render('@Guidelines/settings.html.twig', [
            'page_setup' => $page,
            'values' => $values,
            'errors' => $errors,
            'preview' => $preview,
            'updated_at' => $this->settings->getUpdatedAt()?->setTimezone(new \DateTimeZone($this->currentUser()->getTimezone())),
            'updated_by' => $this->settings->getUpdatedBy(),
            'max_length' => GuidelinesSettings::MAX_CONTENT_LENGTH,
            'kimai_future_allowed' => $this->settings->isKimaiFutureTimesAllowed(),
        ]);
        $response->setStatusCode($status);

        return $response;
    }

    /**
     * Kimai's AbstractController already denies access without a Kimai user.
     */
    private function currentUser(): User
    {
        return $this->getUser();
    }
}
