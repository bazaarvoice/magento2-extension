<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

class Crosssell extends Item
{
    protected $_type = \Bazaarvoice\Connector\Model\Source\ProductList::CROSSSELL;
}
