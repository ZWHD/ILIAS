<?php

use ILIAS\GlobalScreen\Collector\StorageFacade;
use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\MainMenu\isChild;

/**
 * Class ilMMItemRepository
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilMMItemRepository {

	/**
	 * @var bool
	 */
	private $synced = false;
	/**
	 * @var StorageFacade
	 */
	private $storage;
	/**
	 * @var \ILIAS\GlobalScreen\Collector\MainMenu\Main
	 */
	private $main_collector;
	/**
	 * @var \ILIAS\GlobalScreen\Provider\Provider[]
	 */
	private $providers = [];
	/**
	 * @var ilMMItemInformation
	 */
	private $information;
	/**
	 * @var ilGSRepository
	 */
	private $gs;


	/**
	 * ilMainMenuCollector constructor.
	 *
	 * @param StorageFacade $storage
	 */
	public function __construct(StorageFacade $storage) {
		global $DIC;
		$this->storage = $storage;
		$this->gs = new ilGSRepository($storage);
		$this->information = new ilMMItemInformation($this->storage);
		$this->providers = $this->initProviders();
		$this->main_collector = $DIC->globalScreen()->collector()->mainmenu($this->providers, $this->information);
		$this->sync();
	}


	/**
	 * @return \ILIAS\GlobalScreen\MainMenu\TopItem\TopLinkItem|\ILIAS\GlobalScreen\MainMenu\TopItem\TopParentItem
	 */
	public function getStackedTopItemsForPresentation(): array {
		$this->sync();

		$top_items = $this->main_collector->getStackedTopItemsForPresentation();

		return $top_items;
	}


	/**
	 * @param IdentificationInterface $identification
	 *
	 * @return \ILIAS\GlobalScreen\MainMenu\isItem
	 */
	public function getSingleItem(IdentificationInterface $identification): \ILIAS\GlobalScreen\MainMenu\isItem {
		return $this->main_collector->getSingleItem($identification);
	}


	/**
	 * @return array
	 */
	private function initProviders(): array {
		$providers = [];
		foreach (ilGSProviderStorage::get() as $provider_storage) {
			/**
			 * @var $provider_storage ilGSProviderStorage
			 */
			$providers[] = $provider_storage->getInstance();
		}

		return $providers;
	}


	/**
	 * @return ilMMItemRepository
	 */
	public function repository(): ilMMItemRepository {
		return $this;
	}


	/**
	 * @return array
	 */
	public function getTopItems(): array {
		// sync
		$this->sync();

		return ilMMItemStorage::where(" parent_identification = '' OR parent_identification IS NULL ")->orderBy('position')->getArray();
	}


	/**
	 * @return array
	 */
	public function getSubItems(): array {
		// sync
		$this->sync();
		$r = $this->storage->db()->query(
			"SELECT sub_items.*, top_items.position AS parent_position 
FROM il_mm_items AS sub_items 
JOIN il_mm_items AS top_items ON top_items.identification = sub_items.parent_identification
WHERE sub_items.parent_identification != '' ORDER BY top_items.position, sub_items.position ASC"
		);
		$return = [];
		while ($data = $this->storage->db()->fetchAssoc($r)) {
			$return[] = $data;
		}

		return $return;
	}


	/**
	 * @param IdentificationInterface|null $identification
	 *
	 * @return ilMMItemFacadeInterface
	 * @throws Throwable
	 */
	public function getItemFacade(IdentificationInterface $identification = null): ilMMItemFacadeInterface {
		if ($identification === null) {
			return new ilMMNullItemFacade(new \ILIAS\GlobalScreen\Identification\NullIdentification(), $this->main_collector);
		}
		if ($identification->getClassName() === ilMMCustomProvider::class) {
			return new ilMMCustomItemFacade($identification, $this->main_collector);
		}

		return new ilMMItemFacade($identification, $this->main_collector);
	}


	/**
	 * @param string $identification
	 *
	 * @return ilMMItemFacadeInterface
	 * @throws Throwable
	 */
	public function getItemFacadeForIdentificationString(string $identification): ilMMItemFacadeInterface {
		global $DIC;
		$id = $DIC->globalScreen()->identification()->fromSerializedIdentification($identification);

		return $this->getItemFacade($id);
	}


	private function sync(): bool {
		if ($this->synced === false || $this->synced === null) {
			foreach ($this->gs->getIdentificationsForPurpose(ilGSRepository::PURPOSE_MAIN_MENU) as $identification) {
				$this->getItemFacadeForIdentificationString($identification->serialize());
			}
			$this->synced = true;
		}

		return $this->synced;
	}


	public function updateItem(ilMMItemFacadeInterface $item_facade) {
		$item_facade->update();
		$this->storage->cache()->flush();
	}


	public function createItem(ilMMItemFacadeInterface $item_facade) {
		$item_facade->create();
		$this->storage->cache()->flush();
	}


	public function deleteItem(ilMMItemFacadeInterface $item_facade) {
		if ($item_facade->isCustom()) {
			$item_facade->delete();
			$this->storage->cache()->flush();
		}
	}
}