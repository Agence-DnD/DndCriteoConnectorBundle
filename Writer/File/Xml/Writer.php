<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Writer\File\Xml;

use Akeneo\Asset\Component\Model\CategoryInterface;
use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Tool\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Tool\Component\Batch\Item\InitializableInterface;
use Akeneo\Tool\Component\Batch\Job\JobParameters;
use Akeneo\Tool\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Tool\Component\Buffer\BufferFactory;
use Akeneo\Tool\Component\Classification\Repository\CategoryRepositoryInterface;
use Akeneo\Tool\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Channel\Component\Repository\ChannelRepositoryInterface;
use Akeneo\Tool\Component\Connector\Writer\File\AbstractFileWriter;
use Akeneo\Tool\Component\Connector\Writer\File\FlatItemBuffer;
use Symfony\Component\Filesystem\Filesystem;
use Exception;

/**
 * Class Writer
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2019 Agence Dn'D
 * @license         https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            https://www.dnd.fr/
 */
class Writer extends AbstractFileWriter implements
    ItemWriterInterface,
    InitializableInterface,
    StepExecutionAwareInterface
{
    /**
     * Description $attributeRepository field
     *
     * @var AttributeRepositoryInterface $attributeRepository
     */
    protected $attributeRepository;

    /**
     * Description $categoryRepository field
     *
     * @var CategoryRepositoryInterface $categoryRepository
     */
    protected $categoryRepository;

    /**
     * Description $channelRepository field
     *
     * @var ChannelRepositoryInterface $channelRepository
     */
    protected $channelRepository;

    /**
     * Description $bufferFactory field
     *
     * @var BufferFactory $bufferFactory
     */
    protected $bufferFactory;

    /**
     * Description $flatRowBuffer field
     *
     * @var FlatItemBuffer $flatRowBuffer
     */
    protected $flatRowBuffer = null;

    /**
     * Description $XMLRoot field
     *
     * @var \DOMDocument $XMLRoot
     */
    private $XMLRoot;

    /**
     * Description $XMLItems field
     *
     * @var \DOMNode $XMLItems
     */
    private $XMLItems;

    /**
     * Description $exportEntity field
     *
     * @var string $exportEntity
     */
    protected $exportEntity;

    /**
     * Description $jobParameters field
     *
     * @var JobParameters $jobParameters
     */
    protected $jobParameters;

    /**
     * Description $fs field
     *
     * @var Filesystem $fs
     */
    protected $fs;

    /**
     * Description $arrayConverter field
     *
     * @var ArrayConverterInterface $arrayConverter
     */
    protected $arrayConverter;

    /**
     * Description $identifierName field
     *
     * @var string $identifierName
     */
    protected $identifierName;

    /**
     * Description $jobLocale field
     *
     * @var string $jobLocale
     */
    protected $jobLocale;

    /**
     * Description $jobChannel field
     *
     * @var string $jobChannel
     */
    protected $jobChannel;

    /**
     * ProductWriter constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CategoryRepositoryInterface  $categoryRepository
     * @param ChannelRepositoryInterface   $channelRepository
     * @param BufferFactory                $bufferFactory
     * @param ArrayConverterInterface      $arrayConverter
     * @param string                       $exportEntity
     * @param string                       $identifierName
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CategoryRepositoryInterface $categoryRepository,
        ChannelRepositoryInterface $channelRepository,
        BufferFactory $bufferFactory,
        ArrayConverterInterface $arrayConverter,
        string $exportEntity,
        string $identifierName
    ) {
        parent::__construct();

        $this->arrayConverter       = $arrayConverter;
        $this->attributeRepository  = $attributeRepository;
        $this->categoryRepository   = $categoryRepository;
        $this->channelRepository    = $channelRepository;
        $this->bufferFactory        = $bufferFactory;
        $this->exportEntity         = $exportEntity;
        $this->fs                   = new Filesystem();
        $this->identifierName       = $identifierName;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        if (null === $this->flatRowBuffer) {
            $this->flatRowBuffer = $this->bufferFactory->create();
        }
        try {
            $this->jobParameters = $this->stepExecution->getJobParameters()->all();
            $this->jobLocale     = $this->stepExecution->getJobParameters()->get('filters')['data'][1]['context']['locales'][0] ?? 'en';
            $this->jobChannel    = $this->stepExecution->getJobParameters()->get('filters')['structure']['scope'];
            $this->initExportFile();
            $this->initXMLContent();
        } catch (\Exception $exception) {
            throw new \Exception(
                sprintf(
                    'Internal error during XML Export generation file. Reason : %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $productNormalized = $this->arrayConverter->convert(
                $item,
                [
                    'jobParameters' => $this->jobParameters,
                    'attributeRepository' => $this->attributeRepository,
                    'jobLocale' => $this->jobLocale,
                    'jobChannel' => $this->jobChannel
                ]
            );
            if ($this->jobParameters['includeCategories']) {
                $productCategories = $this->getProductCategories($item);
                $productNormalized = array_merge($productNormalized, $productCategories);
            }
            $this->addXMLItem(
                $productNormalized,
                $this->XMLRoot,
                $this->XMLItems
            );
        }
        file_put_contents($this->getPath(), $this->XMLRoot->saveXML());

        $this->flatRowBuffer->write($items, []);
    }

    /**
     * Description initExportFile function
     *
     * @return Writer
     * @throws Exception
     */
    private function initExportFile() : Writer
    {
        try {
            /** @var string $filePath */
            $filePath = $this->getPath();
            if (!is_dir(dirname($filePath))) {
                $this->fs->mkdir(dirname($filePath));
            }
            if (!$this->fs->exists($filePath)) {
                $this->fs->touch($filePath);
            }
            file_get_contents($filePath, '');
        } catch (\Exception $exception) {
            throw new \Exception(
                sprintf(
                    'An error occurred while creating folder or file at path %s',
                    $filePath
                )
            );
        }

        return $this;
    }

    /**
     * Description initXMLContent function
     *
     * @return Writer
     */
    private function initXMLContent() : Writer
    {
        $this->XMLRoot = new \DOMDocument('1.0', 'UTF-8');
        $this->XMLRoot->formatOutput = true;
        $this->XMLRoot->preserveWhiteSpace = false;
        /** @var \DOMElement $XMLElement */
        $XMLElement = $this->XMLRoot->createElement('items');
        $this->XMLRoot->appendChild($XMLElement);
        file_put_contents($this->getPath(), $this->XMLRoot->saveXML());
        $this->XMLItems = $this->XMLRoot->getElementsByTagName('items')->item(0);

        return $this;
    }

    /**
     * Description addXMLItem function
     *
     * @param array        $convertedItem
     * @param \DOMDocument $XMLRoot
     * @param \DOMNode $XMLItems
     *
     * @return void
     */
    private function addXMLItem(array $convertedItem, \DOMDocument $XMLRoot, \DOMNode $XMLItems): void
    {
        /** @var \DOMElement $xmlItem */
        $xmlItem = $XMLRoot->createElement($this->exportEntity);
        /**
         * @var string $criteoAttributeKey
         * @var mixed $criteoAttributeValue
         */
        foreach ($convertedItem as $criteoAttributeKey => $criteoAttributeValue) {
            if (!$criteoAttributeValue && '0' !== $criteoAttributeValue) {
                /** @var \DOMElement $emptyNode */
                $emptyNode = $XMLRoot->createElement($criteoAttributeKey);
                $emptyNode->appendChild($XMLRoot->createTextNode(''));
                $xmlItem->appendChild($emptyNode);
                continue;
            }
            if ($this->identifierName === $criteoAttributeKey) {
                $xmlItem->setAttribute($this->identifierName, $criteoAttributeValue);
                continue;
            }
            /** @var \DOMElement $node */
            $node = $XMLRoot->createElement(
                strtolower($criteoAttributeKey),
                htmlspecialchars($criteoAttributeValue)
            );
            $xmlItem->appendChild($node);

        }

        $XMLItems->appendChild($xmlItem);
    }

    /**
     * Description getProductCategories function
     *
     * @param string[] $product
     *
     * @return string[]
     */
    private function getProductCategories(array $product): array
    {
        /** @var string[] $categories */
        $categoriesFound = [];
        /** @var string[] $productCategories */
        $productCategories = $product['categories'];
        /** @var ChannelInterface $channel */
        $categoryRootChannel = $this->channelRepository->findOneByIdentifier($this->jobChannel);

        /** @var CategoryInterface[] $categories */
        $categories = $this->categoryRepository->findBy(['code' => $productCategories]);
        if (!empty($categories)) {
            /** @var int $i */
            $i = 1;
            /** @var CategoryInterface $category */
            foreach ($categories as $category) {
                /** @var CategoryInterface $categoryRoot */
                $categoryRoot = $this->categoryRepository->findOneBy(['root' => $category->getRoot()]);
                if ($categoryRoot->getCode() !== $categoryRootChannel->getCategory()->getCode()){
                    continue;
                }
                if (!empty($category->setLocale($this->jobLocale)->getTranslation())) {
                    /** @var string $categoryKey */
                    $categoryKey = sprintf('categoryid%d', $i);
                    $categoriesFound[$categoryKey] = $category->setLocale($this->jobLocale)->getTranslation()->getLabel();
                    $i++;
                    if (3 < $i) {
                        break;
                    }
                }
            }
        }

        return $categoriesFound;
    }
}
