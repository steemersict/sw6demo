<?php
declare(strict_types=1);

namespace Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscount;

use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice\PromotionDiscountPriceCollection;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PromotionDiscountEntity extends Entity
{
    use EntityIdTrait;

    /**
     * This scope defines promotion discounts on
     * the entire cart and its line items.
     */
    public const SCOPE_CART = 'cart';

    /**
     * This scope defines promotion discounts on
     * the delivery costs.
     */
    public const SCOPE_DELIVERY = 'delivery';

    /**
     * This type defines a percentage
     * price definition of the discount.
     */
    public const TYPE_PERCENTAGE = 'percentage';

    /**
     * This type defines an absolute price
     * definition of the discount in the
     * current context currency.
     */
    public const TYPE_ABSOLUTE = 'absolute';

    /**
     * This type defines an fixed price
     * definition of the discount.
     */
    public const TYPE_FIXED = 'fixed';

    /**
     * @var string
     */
    protected $promotionId;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var float
     */
    protected $value;

    /**
     * @var PromotionEntity|null
     */
    protected $promotion;

    /**
     * @var RuleCollection|null
     */
    protected $discountRules;

    /**
     * @var bool
     */
    protected $considerAdvancedRules;

    /**
     * @var float|null
     */
    protected $maxValue;

    /**
     * @var PromotionDiscountPriceCollection
     */
    protected $promotionDiscountPrices;

    public function getPromotionId(): string
    {
        return $this->promotionId;
    }

    public function setPromotionId(string $promotionId): void
    {
        $this->promotionId = $promotionId;
    }

    /**
     * Gets the scope of this discount.
     * This is basically the affected area where the
     * discount is being used on.
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Sets the scope that is being affected
     * by the value of this discount.
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getPromotion(): ?PromotionEntity
    {
        return $this->promotion;
    }

    public function setPromotion(PromotionEntity $promotion): void
    {
        $this->promotion = $promotion;
    }

    public function getDiscountRules(): ?RuleCollection
    {
        return $this->discountRules;
    }

    public function setDiscountRules(?RuleCollection $discountRules): void
    {
        $this->discountRules = $discountRules;
    }

    /**
     * if a promotionDiscountPrice has a value for a currency this value should be
     * taken for the discount value and not the value of this entity
     *
     * @return PromotionDiscountPriceCollection
     */
    public function getPromotionDiscountPrices(): ?PromotionDiscountPriceCollection
    {
        return $this->promotionDiscountPrices;
    }

    public function setPromotionDiscountPrices(?PromotionDiscountPriceCollection $promotionDiscountPrices): void
    {
        $this->promotionDiscountPrices = $promotionDiscountPrices;
    }

    public function isConsiderAdvancedRules(): bool
    {
        if ($this->considerAdvancedRules === null) {
            return false;
        }

        return $this->considerAdvancedRules;
    }

    public function setConsiderAdvancedRules(bool $considerAdvancedRules): void
    {
        $this->considerAdvancedRules = $considerAdvancedRules;
    }

    /**
     * Gets the maximum discount value
     * of a percentage discount if set for the promotion.
     */
    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    /**
     * Sets a maximum discount value for the promotion.
     * This one will be used to as a threshold for percentage discounts.
     */
    public function setMaxValue(?float $maxValue): void
    {
        $this->maxValue = $maxValue;
    }
}
