<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Writer\File;

use Akeneo\Component\Batch\Job\RuntimeErrorException;
use Akeneo\Component\Buffer\BufferFactory;
use Akeneo\Component\Buffer\BufferInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Pim\Component\Connector\Writer\File\AbstractFileWriter;
use Pim\Component\Connector\Writer\File\ArchivableWriterInterface;
use Pim\Component\Connector\Writer\File\FilePathResolverInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Akeneo\Component\Classification\Repository\CategoryRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Pim\Bundle\BaseConnectorBundle\Validator\Constraints\Channel;
use Akeneo\Component\Batch\Item\InvalidItemException;
use Pim\Bundle\CatalogBundle\AttributeType\AttributeTypes;
use Akeneo\Component\FileStorage\Repository\FileInfoRepositoryInterface;

/**
 * Write data into a xml file for Criteo
 *
 * @author    Florian Fauvel <florian.fauvel@dnd.fr>
 * @copyright 2016 Agence Dn'D (http://www.dnd.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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

    /** @var ChannelManager */
    protected $channelManager;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var string */
    protected $locale;

    /** @var FileInfoRepositoryInterface */
    protected $fileInfoRepository;

    /** @var int */
    protected $maxCategoriesDepth;

    /**
     * @param FilePathResolverInterface    $filePathResolver
     * @param BufferFactory                $bufferFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ChannelManager               $channelManager
     * @param CategoryRepositoryInterface  $categoryRepository
     * @param LocaleRepositoryInterface    $localeRepository
     * @param FileInfoRepositoryInterface   $fileInfoRepository
     */
    public function __construct(FilePathResolverInterface $filePathResolver, BufferFactory $bufferFactory, AttributeRepositoryInterface $attributeRepository, ChannelManager $channelManager, CategoryRepositoryInterface $categoryRepository, LocaleRepositoryInterface $localeRepository, FileInfoRepositoryInterface $fileInfoRepository)
    {
        parent::__construct($filePathResolver);

        $this->buffer               = $bufferFactory->create();
        $this->attributeRepository  = $attributeRepository;
        $this->channelManager       = $channelManager;
        $this->categoryRepository   = $categoryRepository;
        $this->localeRepository     = $localeRepository;
        $this->fileInfoRepository   = $fileInfoRepository;
        $this->maxCategoriesDepth   = 3;
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return XmlProductWriter
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return XmlProductWriter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductUrl()
    {
        return $this->productUrl;
    }

    /**
     * @param string $productUrl
     * @return XmlProductWriter
     */
    public function setProductUrl($productUrl)
    {
        $this->productUrl = $productUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getBigImage()
    {
        return $this->bigImage;
    }

    /**
     * @param string $bigImage
     * @return XmlProductWriter
     */
    public function setBigImage($bigImage)
    {
        $this->bigImage = $bigImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getSmallImage()
    {
        return $this->smallImage;
    }

    /**
     * @param string $smallImage
     * @return XmlProductWriter
     */
    public function setSmallImage($smallImage)
    {
        $this->smallImage = $smallImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return XmlProductWriter
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return XmlProductWriter
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return string
     */
    public function getRetailPrice()
    {
        return $this->retailPrice;
    }

    /**
     * @param string $retailPrice
     * @return XmlProductWriter
     */
    public function setRetailPrice($retailPrice)
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecommendable()
    {
        return $this->recommendable;
    }

    /**
     * @param string $recommendable
     * @return XmlProductWriter
     */
    public function setRecommendable($recommendable)
    {
        $this->recommendable = $recommendable;

        return $this;
    }

    /**
     * @return int
     */
    public function getIncludeCategories()
    {
        return $this->includeCategories;
    }

    /**
     * @param int $includeCategories
     * @return XmlProductWriter
     */
    public function setIncludeCategories($includeCategories)
    {
        $this->includeCategories = $includeCategories;

        return $this;
    }

    /**
     * Return PimMediaUrl with rtrim to be sur that there is a / at the end of the url
     * @return string
     */
    public function getPimMediaUrl()
    {
        return rtrim($this->pimMediaUrl, '/') . '/';
    }

    /**
     * @param string $pimMediaUrl
     * @return XmlProductWriter
     */
    public function setPimMediaUrl($pimMediaUrl)
    {
        $this->pimMediaUrl = $pimMediaUrl;

        return $this;
    }

    /**
     * Set channel
     *
     * @param string $channelCode Channel code
     *
     * @return $this
     */
    public function setChannel($channelCode)
    {
        $this->channel = $channelCode;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string Channel code
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set locale
     *
     * @param string $localeCode Locale code
     *
     * @return $this
     */
    public function setLocale($localeCode)
    {
        $this->locale = $localeCode;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string Locale code
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        if (false === file_exists($this->getPath())) {
            $xml = new \DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            $products = $xml->createElement('products');
            $xml->appendChild($products);
        } else {
            $xml = new \DOMDocument('1.0','utf-8');
            $content = file_get_contents($this->getPath());
            $content = html_entity_decode($content);
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            $xml->loadXML($content);
            $products = $xml->getElementsByTagName("products")->item(0);
        }
        foreach ($items as $item) {
            $item['product'] = $this->formatProductArray($item['product']);
            $product = $xml->createElement('product');
            $product->setAttribute('id', $item['product'][$this->getId()]);
            $this->addItemChild('name', $item['product'], $this->getName(), $product, $xml);
            $this->addItemChild('description', $item['product'], $this->getDescription(), $product, $xml);
            $this->addItemChild('producturl', $item['product'], $this->getProductUrl(), $product, $xml);
            $this->addItemChild('smallimage', $item['product'], $this->getSmallImage(), $product, $xml);
            $this->addItemChild('bigmage', $item['product'], $this->getBigImage(), $product, $xml);
            $this->addItemChild('price', $item['product'], $this->getPrice(), $product, $xml);
            $this->addItemChild('retailprice', $item['product'], $this->getRetailPrice(), $product, $xml);
            $this->addItemChild('recommendable', $item['product'], $this->getRecommendable(), $product, $xml);
            if ($this->getIncludeCategories()) {
                $productCategories      = $this->removeCategoriesNotInChannel($item['product']['categories']);
                $productCategoriesLabel = $this->getCategoriesLabel($productCategories);
                $i = 1;
                foreach ($productCategoriesLabel as $categoryLabel) {
                    if ($i <= $this->maxCategoriesDepth) {
                        $product->appendChild($xml->createElement('categoryid' . $i, $categoryLabel));
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
     * @return boolean|\DOMElement
     */
    protected function addItemChild($nodeName, $productData, $key, $product, $xml)
    {
        if (!isset($productData[$key])) {
            $this->setItemError($productData, 'job_execution.summary.undefined_index ' . $key);
        }

        if ($productData[$key] != '') {
            $node = $xml->createElement($nodeName, $productData[$key]);

            return $product->appendChild($node);
        }

        return false;
    }

    /**
     * Remove categories code which are not in root category tree associated to current channel
     * @param  array $categories
     * @return array
     */
    protected function removeCategoriesNotInChannel($categories)
    {
        $categories    = explode(',', $categories);
        $channel       = $this->channelManager->getChannelByCode($this->getChannel());

        return array_intersect($this->getCategories($channel->getCategory()->getChildren()), $categories);
    }

    /**
     * Retrieve categories only if they are in root category tree associated to current channel
     * @param  array $children
     * @param  array|null $categories
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
            if($child->hasChildren()) {
                $allCategories = array_merge($allCategories, $this->getCategories($child->getChildren(), $categories));
            }
        }

        return $allCategories;
    }

    /**
     * Retrieve categories labels for selected locale
     * @param  array $categories
     * @return array $labels
     */
    protected function getCategoriesLabel($categories)
    {
        $labels = [];
        foreach ($this->categoryRepository->getCategoriesByCodes($categories) as $category) {
            $labels[] = $category->setLocale($this->getLocale())->getLabel();
        }

        return $labels;
    }

    public function getConfigurationFields()
    {
        return array_merge(
                parent::getConfigurationFields(),
                [
                    'id' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.id.label',
                            'help'     => 'dnd_criteo_connector.export.id.help',
                            'required' => true,
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'name' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.name.label',
                            'help'     => 'dnd_criteo_connector.export.name.help',
                            'required' => true,
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'productUrl' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.productUrl.label',
                            'help'     => 'dnd_criteo_connector.export.productUrl.help',
                            'required' => true,
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'smallImage' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.smallImage.label',
                            'help'     => 'dnd_criteo_connector.export.smallImage.help',
                            'required' => true,
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'bigImage' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.bigImage.label',
                            'help'     => 'dnd_criteo_connector.export.bigImage.help',
                            'required' => true,
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'description' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.description.label',
                            'help'     => 'dnd_criteo_connector.export.description.help',
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'price' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.price.label',
                            'help'     => 'dnd_criteo_connector.export.price.help',
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'retailPrice' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.retailPrice.label',
                            'help'     => 'dnd_criteo_connector.export.retailPrice.help',
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'recommendable' => [
                        'type'    => 'choice',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.recommendable.label',
                            'help'     => 'dnd_criteo_connector.export.recommendable.help',
                            'choices'  => $this->getAttributesChoices(),
                            'select2'  => true
                        ]
                    ],
                    'includeCategories' => [
                        'type'    => 'switch',
                        'options' => [
                            'label'    => 'dnd_criteo_connector.export.includeCategories.label',
                            'help'     => 'dnd_criteo_connector.export.includeCategories.help'
                        ]
                    ],
                    'channel' => [
                        'type'    => 'choice',
                        'options' => [
                            'choices'  => $this->channelManager->getChannelChoices(),
                            'required' => true,
                            'select2'  => true,
                            'label'    => 'pim_base_connector.export.channel.label',
                            'help'     => 'pim_base_connector.export.channel.help'
                        ]
                    ],
                    'locale' => [
                        'type'    => 'choice',
                        'options' => [
                            'choices'  => $this->getLocaleCodes(),
                            'required' => true,
                            'select2'  => true,
                            'label'    => 'dnd_criteo_connector.export.locale.label',
                            'help'     => 'dnd_criteo_connector.export.locale.help'
                        ]
                    ],
                    'pimMediaUrl' => [
                        'options' => [
                            'label'    => 'pim_base_connector.export.pimMediaUrl.label',
                            'help'     => 'pim_base_connector.export.pimMediaUrl.help'
                        ]
                    ],
                ]
            );
    }

    /**
     * Get locale codes for select option
     *
     * @return array
     */
    protected function getLocaleCodes()
    {
        $choices = [];
        foreach ($this->localeRepository->getActivatedLocales() as $locale) {
            $choices[$locale->getCode()] = $locale->getCode();
        }

        return $choices;
    }

    /**
     * Retrieve attributes code for select option
     *
     * @return array[] $choices
     */
    protected function getAttributesChoices()
    {
        $choices = [];
        $choices[''] = '';
        foreach ($this->attributeRepository->getAttributesAsArray() as $attribute) {
            $choices[$attribute['code']] = $attribute['code'];
        }

        return $choices;
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

    /**
     * Get label value for select and multiselect attributes
     * Remove locale / channel in product array keys
     * Remove html characters, encode special html characters on textarea /text attributes
     * Hack to prevent undefined index on product array if attribute mapping is not specified
     * Create url for product images
     *
     * @param  array $product
     * @return array $newProduct
     */
    protected function formatProductArray($product)
    {
        $newProduct = [];
        foreach ($product as $key => $value) {
            $newKey = explode('-', $key);
            $newProduct[$newKey[0]] = $product[$key];
            $attribute = $this->attributeRepository->findOneByIdentifier($newKey[0]);
            if ($attribute === null) {
                continue;
            }
            if ($attribute->getAttributeType() == AttributeTypes::IMAGE) {
                $fileName = basename($value);
                $file     = $this->fileInfoRepository->findOneBy(['originalFilename' => $fileName]);
                if ($file !== null) {
                    $newProduct[$newKey[0]] = $this->getPimMediaUrl() . 'file_storage/catalog/' . $file->getKey();
                }
            } elseif (in_array($attribute->getAttributeType(), [AttributeTypes::TEXT, AttributeTypes::TEXTAREA])) {
                $newProduct[$newKey[0]] = htmlentities(html_entity_decode($value));
            } elseif (in_array($attribute->getAttributeType(), [AttributeTypes::OPTION_MULTI_SELECT, AttributeTypes::OPTION_SIMPLE_SELECT])) {
                foreach ($attribute->getOptions() as $option) {
                    if($option->getCode() == $value) {
                        $newProduct[$newKey[0]] = $option->setLocale($this->getLocale())->getOptionValue()->getLabel();
                        break;
                    }
                }
            }
        }
        $newProduct[''] = '';

        return $newProduct;
    }
}
