<?php

/**
 * TechDivision\Import\Product\Media\Observers\ProductMediaObserver
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product-media
 * @link      http://www.techdivision.com
 */

namespace TechDivision\Import\Product\Media\Observers;

use TechDivision\Import\Product\Media\Utils\ColumnKeys;
use TechDivision\Import\Product\Observers\AbstractProductImportObserver;

/**
 * Observer that extracts theproduct's media data from a CSV file to be added to media specifi CSV file.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product-media
 * @link      http://www.techdivision.com
 */
class ProductMediaObserver extends AbstractProductImportObserver
{

    /**
     * The artefact type.
     *
     * @var string
     */
    const ARTEFACT_TYPE = 'media';

    /**
     * The the default image label.
     *
     * @var string
     */
    const DEFAULT_IMAGE_LABEL = 'Image';

    /**
     * The array with the image information of on row before they'll be converted into artefacts.
     *
     * @var array
     */
    protected $images = array();

    /**
     * The image artefacts that has to be exported.
     *
     * @var array
     */
    protected $artefacts = array();

    /**
     * The array with names of the images that should be hidden on the product detail page.
     *
     * @var array
     */
    protected $imagesToHide = array();

    /**
     * Holds the image values of the main row.
     *
     * @var array
     */
    protected $mainRow = array();

    /**
     * Process the observer's business logic.
     *
     * @return array The processed row
     */
    protected function process()
    {

        // reset the values of the parent row, if the SKU changes
        if ($this->isLastSku($this->getValue(ColumnKeys::SKU)) === false) {
            $this->mainRow = array();
        }

        // initialize the array for the artefacts and the hidden images
        $this->images = array();
        $this->artefacts = array();
        $this->imagesToHide = array();

        // load the images that has to be hidden on product detail page
        $this->loadImagesToHide();

        // process the images/additional images
        $this->processImages();
        $this->processAdditionalImages();

        // append the artefacts that has to be exported to the subject
        $this->addArtefacts($this->artefacts);
    }

    /**
     * Resolve's the value with the passed colum name from the actual row. If a callback will
     * be passed, the callback will be invoked with the found value as parameter. If
     * the value is NULL or empty, the default value will be returned.
     *
     * @param string        $name     The name of the column to return the value for
     * @param mixed|null    $default  The default value, that has to be returned, if the row's value is empty
     * @param callable|null $callback The callback that has to be invoked on the value, e. g. to format it
     *
     * @return mixed|null The, almost formatted, value
     * @see \TechDivision\Import\Observers\AbstractObserver::getValue()
     */
    protected function getImageValue($name, $default = null, callable $callback = null)
    {

        // query whether or not the a image value is available, return it if yes
        if ($this->hasValue($name) && $this->isLastSku($this->getValue(ColumnKeys::SKU)) === false) {
            return $this->mainRow[$name] = $this->getValue($name, $default, $callback);
        }

        // try to load it from the parent rows
        if (isset($this->mainRow[$name])) {
            return $this->mainRow[$name];
        }
    }

    /**
     * Parses the column and exports the image data to a separate file.
     *
     * @return void
     */
    protected function processImages()
    {

        // load the store view code
        $storeViewCode = $this->getValue(ColumnKeys::STORE_VIEW_CODE);
        $attributeSetCode = $this->getValue(ColumnKeys::ATTRIBUTE_SET_CODE);

        // load the parent SKU from the row
        $parentSku = $this->getValue(ColumnKeys::SKU);

        // load the image types
        $imageTypes = $this->getImageTypes();

        // iterate over the available image fields
        foreach ($imageTypes as $imageColumnName => $labelColumnName) {
            // query whether or not the column contains an image name
            if ($image = $this->getImageValue($imageColumnName)) {
                // load the original image path and query whether or not an image with the name already exists
                if (isset($this->artefacts[$imagePath = $this->getInversedImageMapping($image)])) {
                    continue;
                }

                // initialize the label text
                $labelText = $this->getDefaultImageLabel();

                // query whether or not a custom label text has been passed
                if ($this->hasValue($labelColumnName)) {
                    $labelText = $this->getValue($labelColumnName);
                }

                // prepare the new base image
                $artefact = $this->newArtefact(
                    array(
                        ColumnKeys::STORE_VIEW_CODE        => $storeViewCode,
                        ColumnKeys::ATTRIBUTE_SET_CODE     => $attributeSetCode,
                        ColumnKeys::IMAGE_PARENT_SKU       => $parentSku,
                        ColumnKeys::IMAGE_PATH             => $imagePath,
                        ColumnKeys::IMAGE_PATH_NEW         => $image,
                        ColumnKeys::HIDE_FROM_PRODUCT_PAGE => in_array($image, $this->imagesToHide) ? 1 : 0,
                        ColumnKeys::IMAGE_LABEL            => $labelText
                    ),
                    array(
                        ColumnKeys::STORE_VIEW_CODE        => ColumnKeys::STORE_VIEW_CODE,
                        ColumnKeys::ATTRIBUTE_SET_CODE     => ColumnKeys::ATTRIBUTE_SET_CODE,
                        ColumnKeys::IMAGE_PARENT_SKU       => ColumnKeys::SKU,
                        ColumnKeys::IMAGE_PATH             => $imageColumnName,
                        ColumnKeys::IMAGE_PATH_NEW         => $imageColumnName,
                        ColumnKeys::HIDE_FROM_PRODUCT_PAGE => ColumnKeys::HIDE_FROM_PRODUCT_PAGE,
                        ColumnKeys::IMAGE_LABEL            => $labelColumnName
                    )
                );

                // append the base image to the artefacts
                $this->artefacts[$imagePath] = $artefact;
            }
        }
    }

    /**
     * Parses the column and exports the additional image data to a separate file.
     *
     * @return void
     */
    protected function processAdditionalImages()
    {

        // load the store view code
        $storeViewCode = $this->getValue(ColumnKeys::STORE_VIEW_CODE);
        $attributeSetCode = $this->getValue(ColumnKeys::ATTRIBUTE_SET_CODE);

        // load the parent SKU from the row
        $parentSku = $this->getValue(ColumnKeys::SKU);

        // query whether or not, we've additional images
        if ($additionalImages = $this->getImageValue(ColumnKeys::ADDITIONAL_IMAGES, null, array($this, 'explode'))) {
            // expand the additional image labels, if available
            $additionalImageLabels = $this->getValue(ColumnKeys::ADDITIONAL_IMAGE_LABELS, array(), array($this, 'explode'));

            // initialize the images with the found values
            foreach ($additionalImages as $key => $additionalImage) {
                // load the original image path and query whether or not an image with the name already exists
                if (isset($this->artefacts[$imagePath = $this->getInversedImageMapping($additionalImage)])) {
                    continue;
                }

                // prepare the additional image
                $artefact = $this->newArtefact(
                    array(
                        ColumnKeys::STORE_VIEW_CODE        => $storeViewCode,
                        ColumnKeys::ATTRIBUTE_SET_CODE     => $attributeSetCode,
                        ColumnKeys::IMAGE_PARENT_SKU       => $parentSku,
                        ColumnKeys::IMAGE_PATH             => $imagePath,
                        ColumnKeys::IMAGE_PATH_NEW         => $additionalImage,
                        ColumnKeys::HIDE_FROM_PRODUCT_PAGE => in_array($additionalImage, $this->imagesToHide) ? 1 : 0,
                        ColumnKeys::IMAGE_LABEL            => isset($additionalImageLabels[$key]) ?
                                                              $additionalImageLabels[$key] :
                                                              $this->getDefaultImageLabel()
                    ),
                    array(
                        ColumnKeys::STORE_VIEW_CODE        => ColumnKeys::STORE_VIEW_CODE,
                        ColumnKeys::ATTRIBUTE_SET_CODE     => ColumnKeys::ATTRIBUTE_SET_CODE,
                        ColumnKeys::IMAGE_PARENT_SKU       => ColumnKeys::SKU,
                        ColumnKeys::IMAGE_PATH             => ColumnKeys::ADDITIONAL_IMAGES,
                        ColumnKeys::IMAGE_PATH_NEW         => ColumnKeys::ADDITIONAL_IMAGES,
                        ColumnKeys::HIDE_FROM_PRODUCT_PAGE => ColumnKeys::HIDE_FROM_PRODUCT_PAGE,
                        ColumnKeys::IMAGE_LABEL            => ColumnKeys::ADDITIONAL_IMAGE_LABELS
                    )
                );

                // append the additional image to the artefacts
                $this->artefacts[$imagePath] = $artefact;
            }
        }
    }

    /**
     * Load the images that has to be hidden on the product detail page.
     *
     * @return void
     */
    protected function loadImagesToHide()
    {

        // load the array with the images that has to be hidden
        $hideFromProductPage = $this->getValue(ColumnKeys::HIDE_FROM_PRODUCT_PAGE, array(), array($this, 'explode'));

        // map the image names, because probably they have been renamed by the upload functionlity
        foreach ($hideFromProductPage as $filename) {
            $this->imagesToHide[] = $this->getImageMapping($filename);
        }
    }

    /**
     * Return's the array with the available image types and their label columns.
     *
     * @return array The array with the available image types
     */
    protected function getImageTypes()
    {
        return $this->getSubject()->getImageTypes();
    }

    /**
     * Return's the default image label.
     *
     * @return string|null The default image label
     */
    protected function getDefaultImageLabel()
    {
        return ProductMediaObserver::DEFAULT_IMAGE_LABEL;
    }

    /**
     * Returns the mapped filename (which is the new filename).
     *
     * @param string $filename The filename to map
     *
     * @return string The mapped filename
     */
    protected function getImageMapping($filename)
    {
        return $this->getSubject()->getImageMapping($filename);
    }

    /**
     * Returns the original filename for passed one (which is the new filename).
     *
     * @param string $newFilename The new filename to return the original one for
     *
     * @return string The original filename
     */
    protected function getInversedImageMapping($newFilename)
    {
        return $this->getSubject()->getInversedImageMapping($newFilename);
    }

    /**
     * Create's and return's a new empty artefact entity.
     *
     * @param array $columns             The array with the column data
     * @param array $originalColumnNames The array with a mapping from the old to the new column names
     *
     * @return array The new artefact entity
     */
    protected function newArtefact(array $columns, array $originalColumnNames)
    {
        return $this->getSubject()->newArtefact($columns, $originalColumnNames);
    }

    /**
     * Add the passed product type artefacts to the product with the
     * last entity ID.
     *
     * @param array $artefacts The product type artefacts
     *
     * @return void
     * @uses \TechDivision\Import\Product\Media\Subjects\MediaSubject::getLastEntityId()
     */
    protected function addArtefacts(array $artefacts)
    {
        $this->getSubject()->addArtefacts(ProductMediaObserver::ARTEFACT_TYPE, $artefacts, false);
    }
}
