<?php

namespace RZP\Models\Merchant\OneClickCheckout;

use RZP\Models\Base;
use RZP\Models\Merchant\MerchantGiftCardPromotions\Service as MerchantGiftCardPromotionService;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Order;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as OrderOneCCFields;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{

    /**
     * Method for any preprocessing before updating orderMeta
     * @param string $orderId
     * @param array $orderMetaInput
     * @throws \Throwable
     * @return array
     */
    // mockResponse is needed for Functional Tests for apply gift card
    // as this function will again fetch the gift card details before updating.
    public function update1CcOrder(string $orderId, array $orderMetaInput, $mockResponse = null): array
    {
        (new Order1cc\Validator())->validateInput('edit1CCOrder', $orderMetaInput);

        // preprocess for gift cards if any. Disable if the merchant is not set.
        if (!empty($this->merchant))
        {
            $orderMetaInputPromotion = $orderMetaInput[OrderOneCCFields::PROMOTIONS] ?? [];
            $this->trace->info(TraceCode::UPDATE_1CC_ORDER_PROMOTION_UPDATE, [
                'orderId' => $orderId,
                'orderMetaInputPromotion' => $orderMetaInputPromotion
            ]);

            $updatedPromotions = (new MerchantGiftCardPromotionService())->preProcessGiftCards($orderId, $orderMetaInput, $mockResponse);

            $this->trace->info(TraceCode::UPDATE_1CC_ORDER_PROMOTION_UPDATE, [
                'orderId' => $orderId,
                'updatedPromotion' => $updatedPromotions
            ]);

            $orderMetaInput = array_merge($orderMetaInput,
                [
                     'promotions' => $updatedPromotions
                ]);
        }
        return (new Order\OrderMeta\Core)->update1CCOrder($orderId, $orderMetaInput);
    }

    public function get1CcPricingObject(string $orderId) {

        list($order, $orderMeta) = (new CommonUtils())->getOneCcOrderMeta($orderId);

        $value = $orderMeta->getValue();

        $finalCartAmount = $value[Order1cc\Fields::NET_PRICE];

        $lineItemsTotal = $value[Order1cc\Fields::LINE_ITEMS_TOTAL];

        $shippingFee = $value[Order1cc\Fields::SHIPPING_FEE] ?? 0;

        $codFee  = $value[Order1cc\Fields::COD_FEE] ?? 0;

        $promotions = $value[Order1cc\Fields::PROMOTIONS] ?? [];

        $taxDetails = $value[Order1cc\Fields::TAX_DETAILS] ?? [];

        $totalTaxApplied = $this->getTaxesApplied($taxDetails);

        $couponValueApplied = (new Utils\CommonUtils())->getAppliedCouponValue($promotions);

        $nectorCoinsApplied = (new Utils\CommonUtils())->getDiscountAmountByPromotionType($promotions, Constants::NECTOR_COINS);

        $terraWalletValue = (new Utils\CommonUtils())->getDiscountAmountByPromotionType($promotions, Constants::TERRA_WALLET);

        $totalGiftCardValueApplied = $this->calculateTotalGiftCardValue($promotions);

        $discountOnCodFee = $this->getCodFeePromotionApplied($promotions);

        $adjustedCodFee = max(0, $lineItemsTotal - $couponValueApplied - $nectorCoinsApplied - $terraWalletValue) + $totalTaxApplied + $shippingFee + $codFee - $discountOnCodFee - $totalGiftCardValueApplied - $finalCartAmount;

        return [
            Order1cc\Fields::NET_PRICE => $finalCartAmount,
            Order1cc\Fields::LINE_ITEMS_TOTAL => $lineItemsTotal,
            Order1cc\Fields::SHIPPING_FEE => $shippingFee,
            Order1cc\Fields::COD_FEE => $codFee,
            Constants::TOTAL_COUPON_VALUE => $couponValueApplied,
            Constants::NECTOR_COINS_APPLIED => $nectorCoinsApplied,
            Constants::TOTAL_GIFT_CARD_VALUE => $totalGiftCardValueApplied,
            Constants::FINAL_ADJUSTED_COD_VALUE => $totalGiftCardValueApplied != 0 ? $adjustedCodFee : $codFee,
            Constants::TOTAL_TAX_APPLIED   => $totalTaxApplied,
            Constants::TERRA_WALLET_VALUE => $terraWalletValue,
        ];
    }

    protected function calculateTotalGiftCardValue($promotions)
    {
        $discount = 0;

        $appliedGiftCards = (new Utils\CommonUtils())->removeCouponsFromPromotions($promotions);

        foreach ($appliedGiftCards as  $card) {
            $discount+= $card[Order1cc\Fields::PROMOTIONS_VALUE];
        }

        return $discount;
    }

    public function getTaxesApplied($taxDetails)
    {
        if (empty($taxDetails) === true || isset($taxDetails['taxes_included']) === false || $taxDetails['taxes_included'] === true) {
            return 0;
        }
        return $taxDetails['total_tax'] ?? 0;
    }

    protected function getCodFeePromotionApplied($promotions)
    {
        $discount = 0;

        foreach ($promotions as  $coupon) {
            if (isset($coupon[OrderOneCCFields::PROMOTIONS_TYPE]) === true &&  $coupon[OrderOneCCFields::PROMOTIONS_TYPE] === Constants::TYPE_COD_FEE_COUPON) {
                $discount += $coupon[Order1cc\Fields::PROMOTIONS_VALUE];
            }
        }
        return $discount;
    }
}

