<?php

declare(strict_types=1);

namespace App\Action\Admin;

use App\Domain\ServiceLayer\UserManager;
use App\Responder\Redirection\RedirectionResponder;
use App\Responder\TemplateResponder;
use App\Service\Form\Handler\FormHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RequestNewPasswordAction.
 *
 * Manage password renewal request form to receive a token by email.
 * User has forgotten his personal password.
 */
class RequestNewPasswordAction
{
    /**
     * @var UserManager
     */
    private $userService;

    /**
     * @var FormHandlerInterface
     */
    private $formHandler;

    /**
     * RequestNewPasswordAction constructor.
     *
     * @param UserManager          $userService
     * @param FormHandlerInterface $formHandler
     */
    public function __construct(UserManager $userService, FormHandlerInterface $formHandler) {
        $this->userService = $userService;
        $this->formHandler = $formHandler;
    }

    /**
     * Show password renewal request form and validation.
     *
     * @Route({
     *     "en": "/{_locale<en>}/request-new-password"
     * }, name="request_new_password", methods={"GET", "POST"})
     *
     * @param RedirectionResponder $redirectionResponder
     * @param TemplateResponder    $responder
     * @param Request              $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function __invoke(RedirectionResponder $redirectionResponder, TemplateResponder $responder, Request $request): Response
    {
        // Set form without initial model data and set the request by binding it
        $requestNewPasswordForm = $this->formHandler->initForm()->bindRequest($request);
        // Process only on submit
        if ($requestNewPasswordForm->isSubmitted()) {
            // Constraints and custom validation: call actions to perform if necessary on success
            $isFormRequestValid = $this->formHandler->processFormRequest(['userService' => $this->userService]);
            if ($isFormRequestValid) {
                return $redirectionResponder('request_new_password');
            }
        }
        $data = [
            'userError'              => $this->formHandler->getUserError() ?? null,
            'requestNewPasswordForm' => $requestNewPasswordForm->createView()
        ];
        return $responder($data, self::class);
    }
}
