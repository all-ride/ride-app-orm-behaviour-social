<?php

namespace ride\application\orm\model\behaviour\initializer;

use ride\library\generator\CodeClass;
use ride\library\generator\CodeGenerator;
use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\field\PropertyField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\behaviour\initializer\BehaviourInitializer;


/**
 * Setup the social behaviour based on the model options
 */
class SocialBehaviourInitializer implements BehaviourInitializer {

    /**
     * Constructs a new instance
     * @param string $service Name of the service inside
     * @return null
     */
    public function __construct($service = 'address') {
        $this->service = $service;
    }

    /**
     * Gets the behaviours for the model of the provided model table
     * @param \ride\library\orm\definition\ModelTable $modelTable
     * @return array An array with instances of Behaviour
     * @see \ride\library\orm\model\behaviour\Behaviour
     */
    public function getBehavioursForModel(ModelTable $modelTable) {
        if (!$modelTable->getOption('behaviour.social')) {
            return array();
        }

        $isLocalized = $modelTable->isLocalized();
        $baseOptions = array();

        // if tabs are used, add a new tab 'social'
        $tabs = $modelTable->getOption('scaffold.form.tabs');
        if ($tabs) {
            $baseOptions['scaffold.form.tab'] = 'social';

            // add the tab if it isn't available yet
            $tabArray = explode(',', str_replace(' ', '', $tabs));
            if (!in_array('social', $tabArray)) {
                $modelTable->setOption('scaffold.form.tabs', $tabs . ',social');
            }
        }

        if (!$modelTable->hasField('socialTitle')) {
            $options = $baseOptions;
            $options['label.name'] = 'label.title.social';
            $options['label.description'] = 'label.title.social.description';

            $titleField = new PropertyField('socialTitle', 'string');
            $titleField->setIsLocalized($isLocalized);
            $titleField->setOptions($options);

            $modelTable->addField($titleField);
        }

        if (!$modelTable->hasField('socialDescription')) {
            $options = $baseOptions;
            $options['label.name'] = 'label.description.social';
            $options['label.description'] = 'label.description.social.description';

            $descriptionField = new PropertyField('socialDescription', 'text');
            $descriptionField->setIsLocalized($isLocalized);
            $descriptionField->setOptions($options);

            $modelTable->addField($descriptionField);
        }

        if (!$modelTable->hasField('socialImage')) {
            $options = $baseOptions;
            $options['label.name'] = 'label.image.social';
            $options['label.description'] = 'label.image.social.description';

            if (class_exists('\\ride\\application\\orm\\asset\\entry\\AssetEntry')) {
                $imageField = new BelongsToField('socialImage', 'Asset');
                $options['scaffold.form.type'] = 'assets';
            } else {
                $imageField = new PropertyField('socialImage', 'image');
            }

            $imageField->setIsLocalized($isLocalized);
            $imageField->setOptions($options);

            $modelTable->addField($imageField);
        }

        return array();
    }

    /**
     * Generates the needed code for the entry class of the provided model table
     * @param \ride\library\orm\definition\ModelTable $table
     * @param \ride\library\generator\CodeGenerator $generator
     * @param \ride\library\generator\CodeClass $class
     * @return null
     */
    public function generateEntryClass(ModelTable $modelTable, CodeGenerator $generator, CodeClass $class) {
        if (!$modelTable->getOption('behaviour.social')) {
            return;
        }

        $class->addImplements('ride\\application\\orm\\entry\\SocializedEntry');
        $class->addUse('ride\\library\\social\\SharedImage');

        $code =
'$title = $this->getSocialTitle();
if ($title) {
    $sharedItem->setTitle($title);
}

$description = $this->getSocialDescription();
if ($description) {
    $sharedItem->setDescription($description);
}

$image = $this->getSocialImage();
if ($image) {
    $alt = null;

    if (!is_string($image)) {
        $alt = $image->getAlt();
        $image = $image->getValue();
    }

    $sharedItem->addImage(new SharedImage($image, $alt));
}';

        if ($modelTable->hasField('datePublishedFrom')) {
            $code .= '
$datePublished = $this->getDatePublishedFrom();
if ($datePublished) {
    $sharedItem->setDatePublished($datePublished);
}';
        } elseif ($modelTable->hasField('datePublished')) {
            $code .= '
$datePublished = $this->getDatePublished();
if ($datePublished) {
    $sharedItem->setDatePublished($datePublished);
}';
        } elseif ($modelTable->hasField('dateAdded')) {
            $code .= '
$dateAdded = $this->getDateAdded();
if ($dateAdded) {
    $sharedItem->setDatePublished($dateAdded);
}';
        }

        if ($modelTable->isLocalized()) {
            $code .= '
$locale = $this->getLocale();
if ($locale) {
    $sharedItem->setLocale($locale);
}';
        }

        $sharedItemArgument = $generator->createVariable('sharedItem', 'ride\\library\\social\\SharedItem');

        $populateSharedItemMethod = $generator->createMethod('populateSharedItem', array($sharedItemArgument), $code);
        $populateSharedItemMethod->setDescription('Populates the shared item with data from this entry');

        $class->addMethod($populateSharedItemMethod);
    }

}
