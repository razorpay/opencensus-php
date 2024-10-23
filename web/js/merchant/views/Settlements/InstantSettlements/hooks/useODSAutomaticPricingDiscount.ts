import { useSplitzService } from 'common/splitz';
import { usePricingBreakup } from 'merchant/views/Settlements/InstantSettlements/query-hooks/usePricingBreakup';

/**
 * Should use canViewDiscount before using any returned values except isLoading.
 * */
const useODSAutomaticPricingDiscount = (
  currency: 'INR',
): {
  isLoading: boolean;
  canViewDiscount: boolean;
  /** In Percent - use it with canViewDiscount */
  currentPrice: number | null;
  /** In Percent - use it with canViewDiscount */
  newPrice: number | null;
  /** In Percent - use it with canViewDiscount */
  discountPercent: number;
} => {
  // TODO: This API requires min PG balance of 100rs so better approach would be to get this data from config api
  const { data, isInitialLoading } = usePricingBreakup({
    amount: 10000,
    enabled: true,
    currency,
  });
  const splitzOffer = useSplitzService().abExperiments?.capital_is_auto_offer;
  const isEligibleForDiscount = splitzOffer?.variables?.result === 'on';
  const offerFromExperiment = splitzOffer?.variables?.price;
  const actualPricingPercentInBps = data?.items?.[0]?.pricing_rule?.percent_rate;
  const currentPrice = actualPricingPercentInBps || null;
  const newPrice = Number(offerFromExperiment) || null;

  if (!isEligibleForDiscount) {
    return {
      canViewDiscount: false,
      isLoading: false,
      currentPrice: null,
      newPrice: null,
      discountPercent: 0,
    };
  }

  const discountPercent =
    currentPrice && newPrice ? Math.floor(((currentPrice - newPrice) / currentPrice) * 100) : 0;

  return {
    isLoading: isInitialLoading,
    currentPrice: currentPrice ? +(currentPrice / 100).toFixed(2) : null,
    newPrice: newPrice ? +(newPrice / 100).toFixed(2) : null,
    discountPercent,
    canViewDiscount: !isInitialLoading && discountPercent > 0,
  };
};

export { useODSAutomaticPricingDiscount };
