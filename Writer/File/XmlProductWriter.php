<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Writer\File;

use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\Batch\Job\RuntimeErrorException;
use Akeneo\Component\Buffer\BufferFactory;
use Akeneo\Component\Buffer\BufferInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Pim\Component\Connector\Writer\File\AbstractFileWriter;
use Pim\Component\Connector\Writer\File\ArchivableWriterInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Akeneo\Component\Classification\Repository\CategoryRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Akeneo\Component\Batch\Item\InvalidItemException;
use Pim\Component\Catalog\AttributeTypes;
use Akeneo\Component\FileStorage\Repository\FileInfoRepositoryInterface;


/**
 * Class XmlProductWriter
 *
 * @author                 Agence Dn'D <contact@dnd.fr>
 * @copyright              Copyright (c) 2017 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
class XmlProductWriter extends AbstractFileWriter implements ArchivableWriterInterface
{

    /** @var BufferInterface */
    protected $buffer;

    /** @var array */
    protected $writtenFiles = [];

    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $productUrl;

    /** @var string */
    protected $bigImage;

    /** @var string */
    protected $smallImage;

    /** @var string */
    protected $description;

    /** @var string */
    protected $price;

    /** @var string */
    protected $retailPrice;

    /** @var string */
    protected $recommendable;

    /** @var string */
    protected $pimMediaUrl;

    /** @var int */
    protected $includeCategories;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var CategoryRepositoryInterface */
    protected $categoryRepository;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Channel
     *
     * @var string Channel code
     */
    protected $channel;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var string */
    protected $locale;

    /** @var FileInfoRepositoryInterface */
    protected $fileInfoRepository;

    /** @var int */
    protected $maxCategoriesDepth;

    /**
     * @param BufferFactory                $bufferFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ChannelRepositoryInterface   $channelRepository
     * @param CategoryRepositoryInterface  $categoryRepository
     * @param LocaleRepositoryInterface    $localeRepository
     * @param FileInfoRepositoryInterface  $fileInfoRepository
     */
    public function __construct(
        BufferFactory $bufferFactory,
        AttributeRepositoryInterface $attributeRepository,
        ChannelRepositoryInterface $channelRepository,
        CategoryRepositoryInterface $categoryRepository,
        LocaleRepositoryInterface $localeRepository,
        FileInfoRepositoryInterface $fileInfoRepository
    ) {
        parent::__construct();

        $this->buffer = $bufferFactory->create();
        $this->attributeRepository = $attributeRepository;
        $this->channelRepository = $channelRepository;
        $this->categoryRepository = $categoryRepository;
        $this->localeRepository = $localeRepository;
        $this->fileInfoRepository = $fileInfoRepository;
        $this->maxCategoriesDepth = 3;
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $parameters = $this->stepExecution->getJobParameters();
        if (false === file_exists($this->getPath())) {
            $xml = new \DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            $products = $xml->createElement('products');
            $xml->appendChild($products);

        } else {
            $xml = new \DOMDocument('1.0', 'utf-8');
            $content = file_get_contents($this->getPath());
            $content = html_entity_decode($content);
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            $xml->loadXML($content);
            $products = $xml->getElementsByTagName("products")->item(0);
        }
        foreach ($items as $item) {
            $item['product'] = $this->formatProductArray($item['values']);
            unset($item['values']);
            $product = $xml->createElement('product');
            $product->setAttribute('id', $item['product'][$parameters->get('id')]);
            $this->addItemChild('name', $item['product'], $parameters->get('name'), $product, $xml);
            $this->addItemChild('description', $item['product'], $parameters->get('description'), $product, $xml);
            $this->addItemChild('producturl', $item['product'], $parameters->get('productUrl'), $product, $xml);
            $this->addItemChild('smallimage', $item['product'], $parameters->get('smallImage'), $product, $xml);
            $this->addItemChild('bigimage', $item['product'], $parameters->get('bigImage'), $product, $xml);
            $this->addItemChild('price', $item['product'], $parameters->get('price'), $product, $xml);
            $this->addItemChild('retailprice', $item['product'], $parameters->get('retailPrice'), $product, $xml);
            $this->addItemChild('recommendable', $item['product'], $parameters->get('recommendable'), $product, $xml);
            if ($parameters->get('includeCategories')) {
                $productCategories = $this->removeCategoriesNotInChannel($item['categories']);
                $productCategoriesLabel = $this->getCategoriesLabel($productCategories);
                $i = 1;
                foreach ($productCategoriesLabel as $categoryLabel) {
                    if ($i <= $this->maxCategoriesDepth) {
                        $product->appendChild($xml->createElement('categoryid'.$i, $categoryLabel));
                    }
                    $i++;
                }
            }

            $products->appendChild($product);

            $xml->formatOutput = true;
        }

        $path = $this->getPath();
        if (!is_dir(dirname($path))) {
            $this->localFs->mkdir(dirname($path));
        }

        if (false === file_put_contents($path, $xml->saveXML())) {
            throw new RuntimeErrorException('Failed to write to file %path%', ['%path%' => $this->getPath()]);
        }

        return null;
    }

    /**
     * Add new node to xml item node
     *
     * @param string       $nodeName
     * @param array        $productData
     * @param string       $key
     * @param \DomElement  $product
     * @param \DomDocument $xml
     *
     * @return boolean|\DOMElement
     */
    protected function addItemChild($nodeName, $productData, $key, $product, $xml)
    {
        if (!isset($productData[$key])) {
            $this->setItemError($productData, 'job_execution.summary.undefined_index '.$key);
        }

        if ($productData[$key] != '') {
            $node = $xml->createElement($nodeName, $productData[$key]);

            return $product->appendChild($node);
        }

        return false;
    }

    /**
     * Get label value for select and multiselect attributes
     * Remove locale / channel in product array keys
     * Remove html characters, encode special html characters on textarea /text attributes
     * Hack to prevent undefined index on product array if attribute mapping is not specified
     * Create url for product images
     *
     * @param  array $product
     *
     * @return array $newProduct
     */
    protected function formatProductArray($product)
    {

        $parameters = $this->stepExecution->getJobParameters()->all();
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
                            if ($option->getCode() == $value) {
                                $newProduct[$newKey[0]] = $option->setLocale($parameters['locale'])->getOptionValue(
                                )->getLabel();
                                break;
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
     * Remove categories code which are not in root category tree associated to current channel
     *
     * @param  array $categories
     *
     * @return array
     */
    protected function removeCategoriesNotInChannel($categories)
    {
        $parameters = $parameters = $this->stepExecution->getJobParameters();
        $channel = $this->getChannelByCode($parameters->get('filters')['structure']['scope']);

        return array_intersect($this->getCategories($channel->getCategory()->getChildren()), $categories);
    }

    /**
     * Retrieve categories only if they are in root category tree associated to current channel
     *
     * @param  array      $children
     * @param  array|null $categories
     *
     * @return array $allCategories
     */
    protected function getCategories($children, $categories = null)
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
     * @param  array $categories
     *
     * @return array $labels
     */
    protected function getCategoriesLabel($categories)
    {
        $parameters = $this->stepExecution->getJobParameters();
        $labels = [];
        foreach ($this->categoryRepository->getCategoriesByCodes($categories) as $category) {
            $labels[] = $category->setLocale($parameters->get('filters')['structure']['locales'][0])->getLabel();
        }

        return $labels;
    }

    /**
     * Get channel by code
     *
     * @param string $code
     *
     * @return ChannelInterface
     */
    public function getChannelByCode($code)
    {
        return $this->channelRepository->findOneBy(['code' => $code]);
    }

    /**
     * @param array $item
     * @param       $error
     *
     * @throws InvalidItemException
     */
    protected function setItemError(array $item, $error)
    {
        if ($this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('skip');
        }

        throw new InvalidItemException($error, $item);
    }
}
