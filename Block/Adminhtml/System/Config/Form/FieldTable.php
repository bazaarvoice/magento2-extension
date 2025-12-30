<?php


namespace Bazaarvoice\Connector\Block\Adminhtml\System\Config\Form;


use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class FieldTable extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Bazaarvoice_Connector::system/config/status.phtml';

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    protected $_validations = [];

    public function __construct(
        Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ){
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data, $secureRenderer);

    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Bazaarvoice_Connector::system/config/status.phtml');
        }
        return $this;
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function getValidations() {
        $cronJobs = new \Magento\Framework\DataObject();
        $cronJobs->setTitle('Cron Jobs')
            ->setDescription('Validating BV cron jobs status')
            ->setUrl('validate/cron');

        $productFeed = new \Magento\Framework\DataObject();
        $productFeed->setTitle('Product Feed')
            ->setDescription('Validating BV cron jobs status')
            ->setUrl('validate/cron');

//        $this->_validations = [
//          'cron_jobs'   => [
//              'title'       => 'Cron Jobs',
//              'description' => 'Validating BV cron jobs status',
//              'url'         => 'validate/cron'
//          ],
//            'product_feed'  => [
//                'title'         => 'Product Feed',
//                'description'   => 'Check if product feed config is enabled and if so, check the flat tables',
//                'url'           =>  'validate/product_feed'
//            ],
//            'sftp'  => [
//                'title'         => 'SFTP Connection',
//                'description'   => 'Checking SFTP connections',
//                'url'           =>  'validate/sftp'
//            ]
//        ];

        $this->_validations = [$cronJobs, $productFeed];

        return $this->jsonEncoder->encode($this->_validations);
    }
}
