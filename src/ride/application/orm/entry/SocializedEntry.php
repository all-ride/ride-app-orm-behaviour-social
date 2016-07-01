<?php

namespace ride\application\orm\entry;

use ride\library\social\SharedItem;

/**
 * Interface for an entry with social sharing support
 */
interface SocializedEntry {

    /**
     * Gets the title for the shared item
     * @return string
     */
    public function getSocialTitle();

    /**
     * Gets the description for the shared item
     * @return string
     */
    public function getSocialDescription();

    /**
     * Gets the description for the shared item
     * @return string
     */
    public function getSocialImage();

    /**
     * Populates the shared item with data from this entry
     * @param \ride\library\social\SharedItem $sharedItem
     * @return null
     */
    public function populateSharedItem(SharedItem $sharedItem);

}
