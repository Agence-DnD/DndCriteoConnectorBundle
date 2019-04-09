<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Writer\File;

use Akeneo\Tool\Component\Batch\Item\InvalidItemException;
use Akeneo\Tool\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Tool\Component\Batch\Item\InitializableInterface;
use Akeneo\Tool\Component\Batch\Job\JobParameters;
use Akeneo\Tool\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Tool\Component\Buffer\BufferFactory;
use Akeneo\Tool\Component\Classification\Repository\CategoryRepositoryInterface;
use Entity\Category;
use Akeneo\Channel\Component\Model\Channel;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Channel\Component\Repository\ChannelRepositoryInterface;
use Akeneo\Tool\Component\Connector\Writer\File\AbstractFileWriter;
use Akeneo\Tool\Component\Connector\Writer\File\FlatItemBuffer;
use Akeneo\Tool\Component\Connector\Writer\File\FlatItemBufferFlusher;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Exception;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Class XmlProductWriter
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2017 Agence Dn'D
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            http://www.dnd.fr/
 */
class XmlProductWriter extends AbstractFileWriter implements
    ItemWriterInterface,
    InitializableInterface,
    StepExecutionAwareInterface
{

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var CategoryRepositoryInterface */
    protected $categoryRepository;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var BufferFactory */
    protected $bufferFactory;

    /** @var FlatItemBuffer */
    protected $flatRowBuffer = null;

    /** @var FlatItemBufferFlusher */
    protected $flusher = null;

    /** @var array */
    protected $writtenFiles = [];

    /** @var DOMDocument $DOMRoot */
    protected $DOMRoot;

    /** @var DOMDocument $DOMBody */
    protected $DOMBody;

    /** @var string $exportEntity */
    protected $exportEntity;

    /** @var int $maxCategoriesDepth */
    protected $maxCategoriesDepth;

    /** @var JobParameters $jobParameters */
    protected $jobParameters;

    /**
     * ProductWriter constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ChannelRepositoryInterface $channelRepository
     * @param BufferFactory $bufferFactory
     * @param FlatItemBufferFlusher $flusher
     * @param string $exportEntity
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CategoryRepositoryInterface $categoryRepository,
        ChannelRepositoryInterface $channelRepository,
        BufferFactory $bufferFactory,
        FlatItemBufferFlusher $flusher,
        string $exportEntity
    ) {
        parent::__construct();

        $this->attributeRepository  = $attributeRepository;
        $this->categoryRepository   = $categoryRepository;
        $this->channelRepository    = $channelRepository;

        $this->bufferFactory        = $bufferFactory;
        $this->flusher              = $flusher;

        $this->exportEntity         = $exportEntity;
        $this->maxCategoriesDepth   = 3;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        if (null === $this->flatRowBuffer) {
            $this->flatRowBuffer = $this->bufferFactory->create();
        }
        try {
            $this->jobParameters = $this->stepExecution->getJobParameters();

            $this->initExportFile();
            $this->initDomContent();
        } catch (FileException $fileException) {
            throw new FileException('Cant create export file');
        } catch (Exception $exception) {
            throw new Exception('Internal error in generating XML Export');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $item['product'] = $this->formatProductArray($item['values']);
            $this->addXmlNode($item, $this->DOMRoot, $this->DOMBody);
        }

        file_put_contents($this->getPath(), $this->DOMRoot->saveXML());

        $this->flatRowBuffer->write($items, []);
    }

    /**
     * Init Export File
     *
     * @return void
     * @throws Exception
     */
    private function initExportFile() : void
    {
        try {
            /** @var string $file */
            $file = $this->getPath();
            if (!is_dir(dirname($file))) {
                $this->localFs->mkdir(dirname($file), 0777);
            }
            if (!file_exists($file)) {
                $this->localFs->touch($file);
            }
            file_get_contents($file, '');
        } catch (Exception $exception) {
            throw new Exception("An error occurred while creating your directory at " . $this->getPath());
        }
    }

    /**
     * Init DOM Content
     *
     * @return void
     */
    private function initDomContent() : void
    {
        /** @var DOMDocument DOMRoot */
        $this->DOMRoot = new \DOMDocument('1.0', 'UTF-8');
        /** @var DOMElement $xmlRoot */
        $xmlRoot = $this->DOMRoot->createElement('items');

        $this->DOMRoot->appendChild($xmlRoot);
        $this->DOMRoot->formatOutput = true;
        $this->DOMRoot->preserveWhiteSpace = false;

        file_put_contents($this->getPath(), $this->DOMRoot->saveXML());

        $this->setDomItems($this->DOMRoot->getElementsByTagName('items')->item(0));
    }

    /**
     * Add new node in XML Body
     *
     * @param array $item
     * @param DOMDocument $xmlFile
     * @param DOMNode $xmlRoot
     *
     * @throws InvalidItemException
     */
    private function addXmlNode(array $item, DOMDocument $xmlFile, DOMNode $xmlRoot) : void
    {
        /** @var JobParameters $parameters */
        $parameters = $this->jobParameters;
        /** @var \DOMElement $xmlItem */
        $xmlItem = $xmlFile->createElement($this->exportEntity);
        $xmlItem = $xmlRoot->appendChild($xmlItem);

        $xmlItem->setAttribute('id', $item['product'][$parameters->get('id')]);
        $this->addItemChild('name', $item['product'], $parameters->get('name'), $xmlItem, $xmlFile);
        $this->addItemChild('description', $item['product'], $parameters->get('description'), $xmlItem, $xmlFile);
        $this->addItemChild('producturl', $item['product'], $parameters->get('productUrl'), $xmlItem, $xmlFile);
        $this->addItemChild('smallimage', $item['product'], $parameters->get('smallImage'), $xmlItem, $xmlFile);
        $this->addItemChild('bigimage', $item['product'], $parameters->get('bigImage'), $xmlItem, $xmlFile);
        $this->addItemChild('price', $item['product'], $parameters->get('price'), $xmlItem, $xmlFile);
        $this->addItemChild('retailprice', $item['product'], $parameters->get('retailPrice'), $xmlItem, $xmlFile);
        $this->addItemChild('recommendable', $item['product'], $parameters->get('recommendable'), $xmlItem, $xmlFile);
        if ($parameters->get('includeCategories')) {
            $productCategories = $this->removeCategoriesNotInChannel($item['categories']);
            $productCategoriesLabel = $this->getCategoriesLabel($productCategories);
            $i = 1;
            foreach ($productCategoriesLabel as $categoryLabel) {
                if ($i <= $this->maxCategoriesDepth) {
                    $xmlItem->appendChild($xmlFile->createElement('categoryid' . $i, $categoryLabel));
                }
                $i++;
            }
        }
    }

    /**
     * Get label value for select and multiselect attributes
     * Remove locale / channel in product array keys
     * Remove html characters, encode special html characters on textarea /text attributes
     * Hack to prevent undefined index on product array if attribute mapping is not specified
     * Create url for product images
     *
     * @param array $product
     *
     * @return array $newProduct
     */
    private function formatProductArray(array $product) : array
    {
        /** @var array $parameters */
        $parameters = $this->jobParameters->all();
        $parameters['locale'] = $parameters['filters']['structure']['locales'][0];
        unset($parameters['with_media']);
        $newProduct = [];
        foreach ($product as $key => $value) {
            if (!in_array($key, $parameters)) {
                continue;
            }
            $value = $product[$key][0]['data'];
            $product[$key] = $value;
            $newKey = explode('-', $key);
            $newProduct[$newKey[0]] = $product[$key];
            $attribute = $this->attributeRepository->findOneByIdentifier($newKey[0]);
            if ($attribute !== null) {
                switch ($attribute->getAttributeType()) {
                    case AttributeTypes::OPTION_MULTI_SELECT:
                    case AttributeTypes::OPTION_SIMPLE_SELECT:
                        foreach ($attribute->getOptions() as $option) {
                            if ($option->getCode() === $value) {
                                if (null !== $option->setLocale($parameters['locale'])->getOptionValue()) {
                                    $newProduct[$newKey[0]] = $option->setLocale($parameters['locale'])->getOptionValue(
                                    )->getLabel();
                                    break;
                                }
                            }
                        }
                        break;
                    case AttributeTypes::TEXTAREA:
                    case AttributeTypes::TEXT:
                        $newProduct[$newKey[0]] = htmlentities(html_entity_decode($value));
                        break;
                    case AttributeTypes::PRICE_COLLECTION:
                        foreach ($value as $index => $data) {
                            if ($data['currency'] == $parameters['currency']) {
                                $value = number_format($data['amount'], 2, $parameters['decimalSeparator'], '');
                                $newProduct[$newKey[0]] = $value.' '.$parameters['currency'];
                            }
                        }
                        break;
                    case AttributeTypes::IMAGE:
                        $newProduct[$newKey[0]] = rtrim(
                                $parameters['pimMediaUrl'],
                                '/'
                            ).'/file_storage/catalog/'.$value;
                        break;
                }
            }
        }
        $parameters = [
            $parameters['id']            => '',
            $parameters['name']          => '',
            $parameters['description']   => '',
            $parameters['productUrl']    => '',
            $parameters['smallImage']    => '',
            $parameters['bigImage']      => '',
            $parameters['price']         => '',
            $parameters['retailPrice']   => '',
            $parameters['recommendable'] => '',
        ];
        $missingValues = array_diff_key($parameters, $newProduct);
        $newProduct += $missingValues;
        $newProduct[''] = '';

        return $newProduct;
    }

    /**
     * Add new node to xml item node
     *
     * @param string      $nodeName
     * @param array       $productData
     * @param string      $key
     * @param DomElement  $product
     * @param DomDocument $xml
     *
     * @return boolean|DOMElement
     * @throws InvalidItemException
     */
    private function addItemChild(string $nodeName, array $productData, string $key, DOMElement $product, DOMDocument $xml)
    {
        if (!isset($productData[$key])) {
            $this->setItemError($productData, 'job_execution.summary.undefined_index ' . $key);
        }
        if ($productData[$key] !== '') {
            $node = $xml->createElement($nodeName, $productData[$key]);
            $product->appendChild($node);
        }

        return false;
    }


    /**
     * Retrieve categories only if they are in root category tree associated to current channel
     *
     * @param array $children
     * @param array|null $categories
     *
     * @return array $allCategories
     */
    private function getCategories($children, array $categories = null) : array
    {
        $allCategories = $categories;
        if ($allCategories === null) {
            $allCategories = [];
        }
        foreach ($children as $child) {
            $allCategories[] = $child->getCode();
            if ($child->hasChildren()) {
                $allCategories = array_merge($allCategories, $this->getCategories($child->getChildren(), $categories));
            }
        }

        return $allCategories;
    }

    /**
     * Retrieve categories labels for selected locale
     *
     * @param Category[] $categories
     *
     * @return string[] $labels
     */
    private function getCategoriesLabel(array $categories) : array
    {
        /** @var JobParameters $parameters */
        $parameters = $this->jobParameters;
        $labels = [];
        foreach ($this->categoryRepository->getCategoriesByCodes($categories) as $category) {
            $labels[] = $category->setLocale($parameters->get('filters')['structure']['locales'][0])->getLabel();
        }

        return $labels;
    }

    /**
     * Remove categories code which are not in root category tree associated to current channel
     *
     * @param array $categories
     *
     * @return array
     */
    private function removeCategoriesNotInChannel(array $categories) : array
    {
        /** @var JobParameters $parameters */
        $parameters = $this->jobParameters;
        $channel = $this->getChannelByCode($parameters->get('filters')['structure']['scope']);

        return array_intersect($this->getCategories($channel->getCategory()->getChildren()), $categories);
    }

    /**
     * Get channel by code
     *
     * @param string $code
     * @return null|Channel|object
     */
    private function getChannelByCode(string $code) : ?Channel
    {
        return $this->channelRepository->findOneBy(['code' => $code]);
    }

    /**
     * @param DOMElement $element
     */
    private function setDomItems(DOMElement $element) : void
    {
        $this->DOMBody = $element;
    }

    /**
     * Add warning
     *
     * @param array $item
     * @param $error
     *
     * @throws InvalidItemException
     */
    private function setItemError(array $item, $error)
    {
        if ($this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('skip');
        }

        throw new InvalidItemException($error, $item);
    }
}
