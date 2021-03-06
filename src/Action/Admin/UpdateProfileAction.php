<?php

declare(strict_types=1);

namespace App\Action\Admin;

use App\Domain\Entity\User;
use App\Domain\ServiceLayer\ImageManager;
use App\Domain\ServiceLayer\MediaManager;
use App\Domain\ServiceLayer\TrickManager;
use App\Domain\ServiceLayer\UserManager;
use App\Responder\Json\JsonResponder;
use App\Responder\Redirection\RedirectionResponder;
use App\Responder\TemplateResponder;
use App\Service\Form\Handler\FormHandlerInterface;
use App\Service\Form\Type\Admin\UpdateProfileAvatarType;
use App\Utils\Traits\RouterHelperTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UpdateProfileAction.
 *
 * Manage user profile infos update form.
 */
class UpdateProfileAction
{
    use RouterHelperTrait;

    /**
     * @var ImageManager
     */
    private $imageService;

    /**
     * @var MediaManager
     */
    private $mediaService;

    /**
     * @var TrickManager
     */
    private $trickService;

    /**
     * @var UserManager
     */
    private $userService;

    /**
     * @var array|FormHandlerInterface[]
     */
    private $formHandlers;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * UpdateProfileAction constructor.
     *
     * @param ImageManager                 $imageService
     * @param MediaManager                 $mediaService
     * @param TrickManager                 $trickService
     * @param UserManager                  $userService
     * @param array|FormHandlerInterface[] $formHandlers
     * @param RouterInterface              $router
     */
    public function __construct(
        ImageManager $imageService,
        MediaManager $mediaService,
        TrickManager $trickService,
        UserManager $userService,
        array $formHandlers,
        RouterInterface $router
    ) {
        $this->imageService = $imageService;
        $this->mediaService = $mediaService;
        $this->trickService = $trickService;
        $this->userService = $userService;
        $this->formHandlers = $formHandlers;
        $this->setRouter($router);
    }

    /**
     *  Show profile update forms (user avatar, account) and validation errors.
     *
     * Please not this action is used for both non AJAX/AJAX mode!
     * If this action is a simple AJAX request, this url is always the same even if language changed: "locale" parameter can be null.
     *
     * @Route({
     *     "en": "/{_locale?<en>}/{mainRoleLabel<admin|member>}/update-profile"
     * }, name="update_profile", methods={"GET", "POST"})
     *
     * @param RedirectionResponder $redirectionResponder
     * @param JsonResponder        $jsonResponder,
     * @param TemplateResponder    $responder
     * @param Request              $request
     *
     * @return Response|null
     *
     * @throws \Exception
     */
    public function __invoke(
        RedirectionResponder $redirectionResponder,
        JsonResponder $jsonResponder,
        TemplateResponder $responder,
        Request $request
    ): ?Response {
        // Get user from Symfony security context: access is controlled by ACL.
        /** @var UserInterface|User $authenticatedUser */
        $authenticatedUser = $this->userService->getAuthenticatedMember();
        // Set form without initial model data (unnecessary), and set the request by binding it
        $updateProfileAvatarForm = $this->formHandlers[0]->initForm()->bindRequest($request);
        // Set form with authenticated user initial model data and set the request by binding it
        $updateProfileInfosForm = $this->formHandlers[1]->initForm(['userToUpdate' => $authenticatedUser])->bindRequest($request);
        // Process only on submit with POST request for both forms
        if ('POST' === $request->getMethod()) {
            // Return the appropriate response by handling the forms
            $redirectionResponse = $this->manageRedirectionResponseWithFormsHandling(
                $redirectionResponder,
                $jsonResponder,
                $request,
                [
                    'updateProfileAvatarForm' => $updateProfileAvatarForm,
                    'updateProfileInfosForm'  => $updateProfileInfosForm
                ],
                $authenticatedUser
            );
            if (!\is_null($redirectionResponse)) {
                return $redirectionResponse;
            }
        }
        $data = [
            'avatarUploadAjaxMode'    => UpdateProfileAvatarType::IS_AVATAR_UPLOAD_AJAX_MODE,
            'avatarUploadError'       => $this->formHandlers[0]->getUserAvatarError() ?? null,
            'uniqueUserError'         => $this->formHandlers[1]->getUniqueUserError() ?? null,
            'updateProfileAvatarForm' => $updateProfileAvatarForm->createView(),
            'updateProfileInfosForm'  => $updateProfileInfosForm->createView(),
            'userAvatarImage'         => $this->imageService->getUserAvatarImage($authenticatedUser),
            'userCreatedTricks'       => $this->trickService->findOnesByAuthor($authenticatedUser->getUuid())
        ];
        return $responder($data, self::class);
    }

    /**
     * Get a response relative to avatar upload ajax request de/activation.
     *
     * @param bool                 $isFormRequestValid
     * @param JsonResponder        $jsonResponder
     * @param RedirectionResponder $redirectionResponder
     * @param Request              $request
     *
     * @return Response|null
     */
    private function getAvatarUploadResponse(
        bool $isFormRequestValid,
        JsonResponder $jsonResponder,
        RedirectionResponder $redirectionResponder,
        Request $request
    ): ?Response {
        // Get profile update form defined route and user main role param
        $routeName = 'update_profile';
        $routeParameters = ['mainRoleLabel' => $request->attributes->get('mainRoleLabel')];
        if ($request->isXmlHttpRequest()) {
            // Return a JSON response to perform a JS redirection in case of success
            if ($isFormRequestValid) {
                $redirectionURL = $this->generateURLFromRoute($routeName, $routeParameters);
                $response = $jsonResponder(['redirectionURL' => $redirectionURL]);
            // Return a JSON response to show an invalid form or a custom check error notification
            } else {
                $avatarUploadError = $this->formHandlers[0]->getUserAvatarError();
                $response = $jsonResponder($avatarUploadError);
            }
        } else {
            $response = $isFormRequestValid ? $redirectionResponder($routeName, $routeParameters) : null;
        }
        return $response;
    }

    /**
     * Manage and handle the two forms to get the corresponding response.
     *
     * @param RedirectionResponder  $redirectionResponder
     * @param JsonResponder         $jsonResponder
     * @param Request               $request
     * @param array|FormInterface[] $forms
     * @param UserInterface         $identifiedUser
     *
     * @return Response|null
     */
    private function manageRedirectionResponseWithFormsHandling(
        RedirectionResponder $redirectionResponder,
        JsonResponder $jsonResponder,
        Request $request,
        array $forms,
        UserInterface $identifiedUser
    ): ?Response {
        $actionData = ['userService' => $this->userService, 'userToUpdate' => $identifiedUser];
        // Manage the forms
        switch ($submittedRequest = $request->request) {
            // Update profile avatar image
            case $submittedRequest->has($forms['updateProfileAvatarForm']->getName()): // 'update_profile_avatar'
                $actionData['imageService'] = $this->imageService;
                $actionData['mediaService'] = $this->mediaService;
                // Constraints and custom validation: call actions to perform if necessary on success
                $isFormRequestValid = $this->formHandlers[0]->processFormRequest($actionData);
                // Adapt the response depending on ajax mode de/activation
                $response = $this->getAvatarUploadResponse($isFormRequestValid, $jsonResponder, $redirectionResponder, $request);
                break;
            // Update other profile infos
            case $submittedRequest->has($forms['updateProfileInfosForm']->getName()): // 'update_profile_infos'
                // Constraints and custom validation: call actions to perform if necessary on success
                $isFormRequestValid =$this->formHandlers[1]->processFormRequest($actionData);
                // Redirect to the right page if form request is a success
                $response = $isFormRequestValid ? $redirectionResponder('home') : null;
                break;
            default:
                $response = null;
        }
        return $response;
    }
}
