<?php //strict

namespace IO\Services;

use IO\Helper\Utils;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Tag\Contracts\TagRepositoryContract;
use Plenty\Modules\Tag\V2\Contracts\TagRelationshipRepositoryContract;
use Plenty\Modules\Tag\Models\Tag;

/**
 * Service Class TagService
 *
 * This service class contains functions related to tag functionality.
 * All public functions are available in the Twig template renderer.
 *
 * @package IO\Services
 */
class TagService
{
    /** @var TagRepositoryContract */
    private $tagRepository;

    /** @var TagRelationshipRepositoryContract */
    private $tagRelationRepo;

    /**
     * TagService constructor.
     * @param TagRepositoryContract $tagRepository
     */
    public function __construct(
                                TagRepositoryContract $tagRepository,
                                TagRelationshipRepositoryContract $tagRelationRepo
                    )
    {
        $this->tagRepository = $tagRepository;
        $this->tagRelationRepo = $tagRelationRepo;
    }

    /**
     * Get a tag by its id
     *
     * @param int $tagId The id of the tag
     * @return Tag
     * @throws \Throwable
     */
    public function getTagById(int $tagId)
    {
        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);
        $tagRepository = $this->tagRepository;

        $tagData = $authHelper->processUnguarded(function () use ($tagRepository, $tagId) {
            return $tagRepository->getTagById($tagId);
        });

        return $tagData;
    }

    /**
     * Get the name of a tag for a specific language
     *
     * @param int $tagId The id of the tag
     * @param string|null $lang The language to get the name in (ISO-639-1)
     * @return string
     * @throws \Throwable
     */
    public function getTagName(int $tagId, $lang = null)
    {
        if ($lang === null) {
            $lang = Utils::getLang();
        }

        $tag = $this->getTagById($tagId);

        if (is_null($tag)) {
            return "";
        }

        foreach ($tag->names as $tagName) {
            if ($tagName->tagLang === $lang) {
                return $tagName->tagName;
            }
        }

        return $tag->tagName;
    }

    public function getTagRelations($relationIdentifier, $relationType, $tagId = null)
    {
        $authHelper = pluginApp(AuthHelper::class);
        $tagRelationRepo = $this->tagRelationRepo;

        $filter = [
            'type' => $relationType,
            'value' => $relationIdentifier
        ];

        if(!is_null($tagId))
            $filter['tagId'] = $tagId;
        

        $tagData = $authHelper->processUnguarded(function () use ($tagRelationRepo, $filter) {
            $tagRelationRepo->setFilters($filter);
            return $tagRelationRepo->search([], 50, 1);
        });

        return $tagData;
    }
}
