<?php

declare(strict_types=1);

namespace App\Service\Form\Validator;

use App\Domain\DTOToEmbed\VideoInfosDTO;
use App\Service\Form\Validator\Constraint\VideoInfosConstraint;
use App\Service\Medias\VideoURLProxyChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class VideoInfosConstraintValidator.
 *
 * This class manages a custom constraint validation callback as concerns VideoInfosDTO instance.
 *
 * @see https://symfony.com/doc/current/validation/custom_constraint.html
 */
final class VideoInfosConstraintValidator extends AbstractTrickCollectionConstraintValidator
{
    /**
     * @var VideoURLProxyChecker
     */
    private $videoURLProxyChecker;

    /**
     * VideoInfosConstraintValidator constructor.
     *
     * @param VideoURLProxyChecker $videoURLProxyChecker
     */
    public function __construct(VideoURLProxyChecker $videoURLProxyChecker)
    {
        // Use this service to check video validity and existence
        $this->videoURLProxyChecker = $videoURLProxyChecker;
    }

    /**
     * Add a constraint validation callback.
     *
     * {@inheritDoc}
     *
     * @throws \Exception
     * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function validate($object, Constraint $constraint): void
    {
        if (!$object instanceof VideoInfosDTO) {
            throw new \InvalidArgumentException('Object to validate must be an instance of "VideoInfosDTO"!');
        }
        if (!$constraint instanceof VideoInfosConstraint) {
            throw new UnexpectedTypeException($constraint, VideoInfosConstraint::class);
        }
        // Check valid "url" by controlling video accessible content
        $this->validateUrl($this->context);
        // Check valid "show list rank" (sortable block) field value
        $this->validateShowListRank($this->context);
    }

    /**
     * Apply an intermediate custom validation constraint callback on "url" property.
     *
     * @param ExecutionContextInterface $context
     * @param mixed|null                $payload
     *
     * @return void
     *
     * @see For information: root namespace special compiled functions: https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/3048
     */
    public function validateUrl(ExecutionContextInterface $context, $payload = null): void
    {
        // Get current validated object (VideoInfosDTO)
        $object = $context->getObject();
        $url = $object->getUrl();
        // If URL is empty, a basic "NotBlank" constraint violation is applied!
        if (!\is_null($url)) {
            // Video URL is not allowed! Please note video URL format could also have been checked thanks to basic "Regex" validation constraint!
            if (false === $this->videoURLProxyChecker->isAllowed($url)) {
                $context->buildViolation("Video url is not allowed!\nPlease check source URL.")
                    ->atPath('url')
                    ->addViolation();
            } else {
                // Video content cannot be retrieved or does not exist!
                if (false === $this->videoURLProxyChecker->isContent($url)) {
                    $context->buildViolation("Video content is not available!\nPlease check source URL.")
                        ->atPath('url')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * Apply an intermediate custom validation constraint callback on "showListRank" property.
     *
     * @param ExecutionContextInterface $context
     * @param mixed|null                $payload
     *
     * @return void
     *
     * @see For information: root namespace special compiled functions: https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/3048
     */
    public function validateShowListRank(ExecutionContextInterface $context, $payload = null): void
    {
        // Validate current collection item show list rank by checking VideoInfosDTO data
        $this->validateItemCollectionRank('videos', $context, $payload);
    }
}
