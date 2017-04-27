<?php

namespace CustomerFamily\Loop;

use CustomerFamily\Model\ProductPurchasePriceQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

/**
 * Class ProductPurchasePriceLoop
 * @package CustomerFamily\Loop
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ProductPurchasePriceLoop extends BaseLoop implements PropelSearchLoopInterface
{
    /**
     * Definition of loop arguments
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('pse_id', null, true),
            Argument::createIntTypeArgument('currency_id', null, true)
        );
    }

    /**
     * @return ProductPurchasePriceQuery
     */
    public function buildModelCriteria()
    {
        $search = ProductPurchasePriceQuery::create();

        $search->filterByProductSaleElementsId($this->getPseId());

        $search->filterByCurrencyId($this->getCurrencyId());

        return $search;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \CustomerFamily\Model\ProductPurchasePrice $productPurchasePrice */
        foreach ($loopResult->getResultDataCollection() as $productPurchasePrice) {
            $loopResultRow = new LoopResultRow($productPurchasePrice);

            $loopResultRow
                ->set('PSE_ID', $productPurchasePrice->getProductSaleElementsId())
                ->set('CURRENCY_ID', $productPurchasePrice->getCurrencyId())
                ->set('PURCHASE_PRICE', $productPurchasePrice->getPurchasePrice());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

}