<?php

declare(strict_types=1);

namespace App\Action;

use App\Responder\Json\JsonResponder;
use App\Service\Medias\VideoURLProxyChecker;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxVideoURLCheckAction.
 *
 * Verify if single trick video URL can be loaded using ajax.
 */
class AjaxVideoURLCheckAction
{
    use LoggerAwareTrait;

    /**
     * @var VideoURLProxyChecker
     */
    private $trickVideoChecker;

    /**
     * AjaxTrickListAction constructor.
     *
     * @param VideoURLProxyChecker $trickVideoChecker
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function __construct(VideoURLProxyChecker $trickVideoChecker, LoggerInterface $logger)
    {
       $this->trickVideoChecker = $trickVideoChecker;
       $this->setLogger($logger);
    }

    /**
     * Check if single trick video URL can be loaded from AJAX request.
     *
     * Please not url is always the same even if language changed.
     * This is a simple AJAX request and locale parameter is null.
     * Particular "url" attribute value can be empty in some cases!
     * Its declared placeholder requirement can be: {url<(.+)?>},
     * but choice is made to check if "url" attribute is null (optional placeholder)
     * instead of checking its value with this placeholder requirement: {url<(.+)>?}
     *
     * @Route("/load-trick-video/url/{url<(.+)>?}", name="load_trick_video_url_check", methods={"GET"})
     *
     * @param JsonResponder $responder
     * @param Request       $request
     *
     * @return JsonResponse
     *
     * "url" attribute value is checked with a filter:
     * @see VideoURLProxyChecker::filterURLAttribute()
     * About trailing slash:
     * @see https://symfony.com/doc/current/routing/slash_in_parameter.html
     * CAUTION! Update any URI change in:
     * @see LoginFormAuthenticationManager::onAuthenticationSuccess()
     */
    public function __invoke(JsonResponder $responder, Request $request): JsonResponse
    {
        // Check video URL value
        $url = $this->trickVideoChecker->filterURLAttribute($request);
        if (\is_null($url)) {
            $this->logger->error(
                "[trace app SnowTricks] AjaxVideoURLCheckAction/__invoke => " .
                "Technical error due to video url set to null: check loading process for both client and server side!"
            );
        }
        // Check if URL is formatted as expected (validation) and accessible
        $data = $this->trickVideoChecker->verify($url);
        return $responder($data);
    }
}
