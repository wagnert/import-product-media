<?php

/**
 * TechDivision\Import\Product\Media\Services\ProductMediaProcessorInterface
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

namespace TechDivision\Import\Product\Media\Services;

use TechDivision\Import\Product\Services\ProductProcessorInterface;

/**
 * A SLSB providing methods to load product data using a PDO connection.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product-media
 * @link      http://www.techdivision.com
 */
interface ProductMediaProcessorInterface extends ProductProcessorInterface
{

    /**
     * Return's the action with the product media gallery CRUD methods.
     *
     * @return \TechDivision\Import\Product\Media\Actions\ProductMediaGalleryAction The action with the product media gallery CRUD methods
     */
    public function getProductMediaGalleryAction();

    /**
     * Return's the action with the product media gallery valueCRUD methods.
     *
     * @return \TechDivision\Import\Product\Media\Actions\ProductMediaGalleryAction The action with the product media gallery value CRUD methods
     */
    public function getProductMediaGalleryValueAction();

    /**
     * Return's the action with the product media gallery value to entity CRUD methods.
     *
     * @return \TechDivision\Import\Product\Media\Actions\ProductMediaGalleryAction $productMediaGalleryAction The action with the product media gallery value to entity CRUD methods
     */
    public function getProductMediaGalleryValueToEntityAction();

    /**
     * Return's the action with the product media gallery value video CRUD methods.
     *
     * @return \TechDivision\Import\Product\Media\Actions\ProductMediaGalleryAction The action with the product media gallery value video CRUD methods
     */
    public function getProductMediaGalleryValueVideoAction();

    /**
     * Persist's the passed product media gallery data and return's the ID.
     *
     * @param array       $productMediaGallery The product media gallery data to persist
     * @param string|null $name                The name of the prepared statement that has to be executed
     *
     * @return string The ID of the persisted entity
     */
    public function persistProductMediaGallery($productMediaGallery, $name = null);

    /**
     * Persist's the passed product media gallery value data.
     *
     * @param array       $productMediaGalleryValue The product media gallery value data to persist
     * @param string|null $name                     The name of the prepared statement that has to be executed
     *
     * @return void
     */
    public function persistProductMediaGalleryValue($productMediaGalleryValue, $name = null);

    /**
     * Persist's the passed product media gallery value to entity data.
     *
     * @param array       $productMediaGalleryValuetoEntity The product media gallery value to entity data to persist
     * @param string|null $name                             The name of the prepared statement that has to be executed
     *
     * @return void
     */
    public function persistProductMediaGalleryValueToEntity($productMediaGalleryValuetoEntity, $name = null);

    /**
     * Persist's the passed product media gallery value video data.
     *
     * @param array       $productMediaGalleryValueVideo The product media gallery value video data to persist
     * @param string|null $name                          The name of the prepared statement that has to be executed
     *
     * @return void
     */
    public function persistProductMediaGalleryValueVideo($productMediaGalleryValueVideo, $name = null);
}
