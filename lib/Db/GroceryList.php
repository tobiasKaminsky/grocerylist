<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class GroceryList extends Entity implements JsonSerializable {

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $userId;

	/**
	 * @var int
	 */
	protected $showOnlyUnchecked;

    /**
     * @var int
     */
    protected $uncheckedCount;

	public function __construct() {
		$this->addType('title', 'string');
		$this->addType('userId', 'string');
		$this->addType('showOnlyUnchecked', 'int');
        $this->addType('uncheckedCount', 'int');
	}

	public function getShowOnlyUnchecked(): bool {
		return $this->getter('showOnlyUnchecked') !== 0;
	}

	public function setShowOnlyUnchecked(bool $showOnlyUnchecked): void {
		$this->setter('showOnlyUnchecked', [$showOnlyUnchecked ? 1 : 0]);
	}

    public function setUncheckedCount(int $count): void {
        $this->setter('uncheckedCount', [$count]);
    }


	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'userId' => $this->getUserId(),
			'showOnlyUnchecked' => $this->getShowOnlyUnchecked(),
            'uncheckedCount' => $this->uncheckedCount,
		];
	}
}
