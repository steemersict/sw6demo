<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Price;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;

class GrossPriceCalculator
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var PriceRoundingInterface
     */
    private $priceRounding;

    /**
     * @var ReferencePriceCalculator
     */
    private $referencePriceCalculator;

    public function __construct(
        TaxCalculator $taxCalculator,
        PriceRoundingInterface $priceRounding,
        ReferencePriceCalculator $referencePriceCalculator
    ) {
        $this->taxCalculator = $taxCalculator;
        $this->priceRounding = $priceRounding;
        $this->referencePriceCalculator = $referencePriceCalculator;
    }

    public function calculate(QuantityPriceDefinition $definition): CalculatedPrice
    {
        $unitPrice = $this->getUnitPrice($definition);

        $unitTaxes = $this->taxCalculator->calculateGrossTaxes(
            $unitPrice,
            $definition->getPrecision(),
            $definition->getTaxRules()
        );

        foreach ($unitTaxes as $tax) {
            $tax->setTax($tax->getTax() * $definition->getQuantity());
            $tax->setPrice($tax->getPrice() * $definition->getQuantity());
        }

        $price = $this->priceRounding->round(
            $unitPrice * $definition->getQuantity(),
            $definition->getPrecision()
        );

        return new CalculatedPrice(
            $unitPrice,
            $price,
            $unitTaxes,
            $definition->getTaxRules(),
            $definition->getQuantity(),
            $this->referencePriceCalculator->calculate($price, $definition)
        );
    }

    private function getUnitPrice(QuantityPriceDefinition $definition): float
    {
        //unit price already calculated?
        if ($definition->isCalculated()) {
            return $definition->getPrice();
        }

        $price = $this->taxCalculator->calculateGross(
            $definition->getPrice(),
            $definition->getPrecision(),
            $definition->getTaxRules()
        );

        return $this->priceRounding->round($price, $definition->getPrecision());
    }
}
