<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Promotion\Cart;

use Shopware\Core\Checkout\Cart\Exception\InvalidPayloadException;
use Shopware\Core\Checkout\Cart\Exception\InvalidQuantityException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice\PromotionDiscountPriceCollection;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice\PromotionDiscountPriceEntity;
use Shopware\Core\Checkout\Promotion\Exception\UnknownPromotionDiscountTypeException;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PromotionItemBuilder
{
    /**
     * will be used as prefix for the key
     * within placeholder items
     */
    public const PLACEHOLDER_PREFIX = 'promotion-';

    /**
     * Builds a new placeholder promotion line item that does not have
     * any side effects for the calculation. It will contain the code
     * within the payload which can then be used to create a real promotion item.
     *
     * @throws InvalidPayloadException
     * @throws InvalidQuantityException
     */
    public function buildPlaceholderItem(string $code, int $currencyPrecision): LineItem
    {
        // void duplicate codes with other items
        // that might not be from the promotion scope
        $uniqueKey = self::PLACEHOLDER_PREFIX . $code;

        $item = new LineItem($uniqueKey, PromotionProcessor::LINE_ITEM_TYPE);
        $item->setLabel($uniqueKey);
        $item->setGood(false);

        // this is used to pass on the code for later usage
        $item->setReferencedId($code);

        // this is important to avoid any side effects when calculating the cart
        // a percentage of 0,00 will just do nothing
        $item->setPriceDefinition(new PercentagePriceDefinition(0, $currencyPrecision));

        return $item;
    }

    /**
     * Builds a new Line Item for the provided discount and its promotion.
     * It will automatically reference all provided "product" item Ids within the payload.
     *
     * @throws InvalidPayloadException
     * @throws InvalidQuantityException
     * @throws UnknownPromotionDiscountTypeException
     */
    public function buildDiscountLineItem(PromotionEntity $promotion, PromotionDiscountEntity $discount, SalesChannelContext $context): LineItem
    {
        /** @var int $currencyPrecision */
        $currencyPrecision = $context->getContext()->getCurrencyPrecision();

        //get the rules collection of discount
        /** @var RuleCollection|null $discountRuleCollection */
        $discountRuleCollection = $discount->getDiscountRules();

        // this is our target Filter that may be null if discount has no filters
        $targetFilter = null;

        // we do only need to build a target rule if user has allowed it
        // and the rule collection is not empty
        if ($discountRuleCollection instanceof RuleCollection && $discount->isConsiderAdvancedRules() && $discountRuleCollection->count() > 0) {
            $targetFilter = new OrRule();

            /** @var RuleEntity $discountRule */
            foreach ($discountRuleCollection as $discountRule) {
                /** @var Rule|string|null $rule */
                $rule = $discountRule->getPayload();

                if ($rule instanceof Rule) {
                    $targetFilter->addRule($rule);
                }
            }
        }

        // our promotion values are always negative values.
        // either type percentage or absolute needs to be negative to get
        // automatically subtracted within the calculation process
        $promotionValue = -abs($discount->getValue());

        switch ($discount->getType()) {
            case PromotionDiscountEntity::TYPE_ABSOLUTE:
                $promotionValue = -abs($this->getCurrencySpecificValue($discount, $discount->getValue(), $context));
                $promotionDefinition = new AbsolutePriceDefinition($promotionValue, $currencyPrecision, $targetFilter);
                break;

            case PromotionDiscountEntity::TYPE_PERCENTAGE:
                $promotionDefinition = new PercentagePriceDefinition($promotionValue, $currencyPrecision, $targetFilter);
                break;

            case PromotionDiscountEntity::TYPE_FIXED:
                $promotionValue = -abs($this->getCurrencySpecificValue($discount, $discount->getValue(), $context));
                $promotionDefinition = new AbsolutePriceDefinition($promotionValue, $currencyPrecision, $targetFilter);
                break;

            default:
                $promotionDefinition = null;
        }

        if ($promotionDefinition === null) {
            throw new UnknownPromotionDiscountTypeException($discount);
        }

        // build our discount line item
        // and make sure it has everything as dynamic content.
        // this is necessary for the recalculation process.
        $promotionItem = new LineItem($discount->getId(), PromotionProcessor::LINE_ITEM_TYPE);
        $promotionItem->setLabel($promotion->getName());
        $promotionItem->setDescription($promotion->getName());
        $promotionItem->setGood(false);
        $promotionItem->setRemovable(true);
        $promotionItem->setPriceDefinition($promotionDefinition);

        // always make sure we have a valid code entry.
        // this helps us to identify the item by code later on
        if ($promotion->isUseCodes()) {
            $promotionItem->setReferencedId((string) $promotion->getCode());
        }

        // add custom content to our payload.
        // we need this as meta data information.
        $promotionItem->setPayload(
            $this->buildPayload(
                $promotion,
                $discount,
                $context
            )
        );

        // add our lazy-validation rules.
        // this is required within the recalculation process.
        // if the requirements are not met, the calculation process
        // will remove our discount line item.
        $promotionItem->setRequirement($promotion->getPreconditionRule());

        return $promotionItem;
    }

    /**
     * in case of a delivery discount we add a 0.0 lineItem just to show customers and
     * shop owners, that delivery costs have been discounted by a promotion discount
     * if promotion is a auto promotion (no code) it may not be removed from cart
     *
     * @throws \Shopware\Core\Checkout\Cart\Exception\InvalidPayloadException
     * @throws \Shopware\Core\Checkout\Cart\Exception\InvalidQuantityException
     */
    public function buildDeliveryPlaceholderLineItem(LineItem $discount, QuantityPriceDefinition $priceDefinition, CalculatedPrice $price): LineItem
    {
        $mayRemove = true;
        if ($discount->getReferencedId() === null) {
            $mayRemove = false;
        }
        // create a fake lineItem that stores our promotion code
        $promotionItem = new LineItem($discount->getId(), PromotionProcessor::LINE_ITEM_TYPE, $discount->getReferencedId(), 1);
        $promotionItem->setLabel($discount->getLabel());
        $promotionItem->setDescription($discount->getLabel());
        $promotionItem->setGood(false);
        $promotionItem->setRemovable($mayRemove);
        $promotionItem->setPayload($discount->getPayload());
        $promotionItem->setPriceDefinition($priceDefinition);
        $promotionItem->setPrice($price);

        return $promotionItem;
    }

    /**
     * Builds a custom payload array from the provided promotion data.
     * This will make sure we have our eligible items referenced as meta data
     * and also have the code in our payload.
     */
    private function buildPayload(PromotionEntity $promotion, PromotionDiscountEntity $discount, SalesChannelContext $context): array
    {
        $payload = [];

        // to save how many times a promotion has been used, we need to know the promotion's id during checkout
        $payload['promotionId'] = $promotion->getId();

        // set discountId
        $payload['discountId'] = $discount->getId();

        // set the discount type absolute, percentage, ...
        $payload['discountType'] = $discount->getType();

        // set value of discount in payload
        $payload['value'] = (string) $discount->getValue();

        // set our max value for maximum percentage discounts
        if ($discount->getType() === PromotionDiscountEntity::TYPE_PERCENTAGE && $discount->getMaxValue() !== null) {
            $payload['maxValue'] = (string) $this->getCurrencySpecificValue($discount, $discount->getMaxValue(), $context);
        } else {
            $payload['maxValue'] = '';
        }

        // set the scope of the discount cart, delivery....
        $payload['discountScope'] = $discount->getScope();

        return $payload;
    }

    /**
     * get the absolute price from collection if there is a price defined for the SalesChannelContext currency
     * if no price is defined return standard discount price
     */
    private function getCurrencySpecificValue(PromotionDiscountEntity $discount, float $default, SalesChannelContext $context): float
    {
        /** @var PromotionDiscountPriceCollection|null $currencyPrices */
        $currencyPrices = $discount->getPromotionDiscountPrices();

        // if there is no special defined price return default value
        if (!$currencyPrices instanceof PromotionDiscountPriceCollection || $currencyPrices->count() === 0) {
            return $default;
        }

        // there are defined special prices, let's look if we may find one in collection for sales channel currency
        // if there is one we want to return this otherwise we return standard value

        /** @var string $currencyId */
        $currencyId = $context->getCurrency()->getId();

        $discountValue = $default;

        /** @var PromotionDiscountPriceEntity $currencyPrice */
        foreach ($currencyPrices as $currencyPrice) {
            if ($currencyPrice->getCurrencyId() === $currencyId) {
                // we have found a defined price, we overwrite standard value and break loop
                $discountValue = $currencyPrice->getPrice();
                break;
            }
        }

        // return the value
        return $discountValue;
    }
}
