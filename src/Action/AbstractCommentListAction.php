<?php

declare(strict_types=1);

namespace App\Action;

use App\Domain\Entity\Comment;
use App\Domain\ServiceLayer\CommentManager;
use App\Domain\ServiceLayer\MediaTypeManager;
use Ramsey\Uuid\UuidInterface;

/**
 * Class AbstractCommentListAction.
 *
 * Manage trick single page comment list for initial display and AJAX "load more" functionality.
 */
abstract class AbstractCommentListAction
{
    /**
     * @var CommentManager
     */
    protected $commentService;

    /**
     * @var MediaTypeManager
     */
    protected $mediaTypeService;

    /**
     * @var array
     */
    private $commentListData;

    /**
     * AbstractCommentListAction constructor.
     *
     * @param CommentManager   $commentService
     * @param MediaTypeManager $mediaTypeService
     */
    public function __construct(CommentManager $commentService, MediaTypeManager $mediaTypeService)
    {
        $this->commentService = $commentService;
        $this->mediaTypeService = $mediaTypeService;
        $this->commentListData = [
            'commentLoadingMode'      => Comment::COMMENT_LOADING_MODE,
            'commentNumberPerLoading' => Comment::COMMENT_NUMBER_PER_LOADING,
            'listEnded'               => 'No more comment to load!',
            'noList'                  => 'No comment exists for this trick at this time!',
            'technicalError'          => 'Sorry, something wrong happened' . "\n" .
                                         'during comment list loading!' . "\n" .
                                         'Please contact us or try again later.' . "\n",
        ];
    }

    /**
     * Get comment list necessary data.
     *
     * @return array
     */
    protected function getCommentListData(): array
    {
        return $this->commentListData;
    }

    /**
     * Get medias necessary data.
     *
     * @return array
     */
    protected function getMediasData(): array
    {
        // Get registered normal image type (corresponds particular dimensions)
        $mediaTypesValues = $this->mediaTypeService->getMandatoryDefaultTypes();
        $normalImageMediaType = $this->mediaTypeService->findSingleByUniqueType($mediaTypesValues['trickNormal']);
        return [
            'mediaError'           => 'Media loading error',
            'mediaTypesValues'     => $mediaTypesValues,
            'normalImageMediaType' => $normalImageMediaType
        ];
    }

    /**
     * Retrieve trick comments list to show.
     *
     * @param $trickUuid
     * @param $offset
     * @param $limit
     * @param $commentLoadingMode
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function prepareTrickCommentsListWithRanks(
        UuidInterface $trickUuid,
        int $offset,
        int $limit,
        string $commentLoadingMode
    ): array {
        // Get comments filtered list
        $selectedTrickComments = $this->commentService->findOnesByTrickWithOffsetLimit(
            $trickUuid,
            $offset,
            $limit,
            $commentLoadingMode,
            true
        );
        // Get uuid data with a second simple query to use it for comparison
        $trickCommentData = $this->commentService->findOnesByTrick($trickUuid, $commentLoadingMode, true);
        // Get all comments total count
        $commentTotalCount = \count($trickCommentData);
        // Get first level comments total count to use with AJAX loading
        $firstLevelCommentsTotalCount = 0;
        for ($i = 0; $i < $commentTotalCount; $i++) {
            if (\is_null($trickCommentData[$i]['parent_uuid'])) {
                $firstLevelCommentsTotalCount++;
            }
        }
        // Add comment ranks to filtered comments
        $selectedTrickComments = $this->commentService->addRanksToTrickComments(
            $trickCommentData,
            $selectedTrickComments,
            $commentLoadingMode
        );
        // Return needed data
        return [
            'commentsTotalCount'           => $commentTotalCount,
            'commentListWithRanks'         => $selectedTrickComments,
            'firstLevelCommentsTotalCount' => $firstLevelCommentsTotalCount
        ];
    }
}
