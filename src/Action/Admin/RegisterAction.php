<?php

declare(strict_types=1);

namespace App\Action\Admin;

use App\Domain\ServiceLayer\UserManager;
use App\Responder\TemplateResponder;
use App\Service\Form\Handler\FormHandlerInterface;
use App\Responder\Redirection\RedirectionResponder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RegisterAction.
 *
 * Manage user registration form.
 */
class RegisterAction
{
    /**
     * @var UserManager
     */
    private $userService;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var FormHandlerInterface
     */
    private $formHandler;

    /**
     * RegisterAction constructor.
     *
     * @param UserManager          $userService
     * @param FlashBagInterface    $flashBag
     * @param FormHandlerInterface $formHandler
     */
    public function __construct(UserManager $userService, FlashBagInterface $flashBag, FormHandlerInterface $formHandler) {
        $this->userService = $userService;
        $this->flashBag = $flashBag;
        $this->formHandler = $formHandler;
    }

    /**
     *  Show registration form (user registration) and validation errors.
     *
     * @Route({
     *     "en": "/{_locale<en>}/register"
     * }, name="register", methods={"GET", "POST"})
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
        $registerForm = $this->formHandler->initForm()->bindRequest($request);
        // Process only on submit
        if ($registerForm->isSubmitted()) {
            // Constraints and custom validation: call actions to perform if necessary on success
            $isFormRequestValid = $this->formHandler->processFormRequest(['userService' => $this->userService]);
            if ($isFormRequestValid) {
                return $redirectionResponder('home');
            }
        }
        $data = [
            'uniqueUserError' => $this->formHandler->getUniqueUserError() ?? null,
            'registerForm'    => $registerForm->createView()
        ];
        return $responder($data,self::class);
    }

    /**
     * Activate user account after registration.
     *
     * @Route({
     *     "en": "/{_locale<en>}/validate-account/{userId}"
     * }, name="validate_account")
     *
     * @param RedirectionResponder $redirectionResponder
     * @param Request              $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function activateUserAccount(RedirectionResponder $redirectionResponder, Request $request): Response
    {
        $userId = $request->attributes->get('userId');
        $isActivated = $this->userService->activateAccount($userId);
        if (!$isActivated) {
            $this->flashBag->add(
                'danger',
                'You are not allowed to access' . "\n" .
                         'account activation process!' . "\n" .
                         'Please contact us if necessary.'
            );
        } else {
            $this->flashBag->add(
                'success',
                'Good job!' . "\n" .
                         'Your account was successfully activated.' . "\n" .
                         'Please login to access member area.'
            );
        }
        return $redirectionResponder('home');
    }
}
