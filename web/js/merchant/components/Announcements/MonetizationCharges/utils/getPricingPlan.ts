const getPricingPlan = (user: any, isNoCodeMonetizationExperimentOn: boolean) => {
  const pricingPlanId = user?.merchant?.pricing_plan_id;

  if (!pricingPlanId) {
    return null;
  }

  if (pricingPlanId === '1In3Yh5Mluj605' || pricingPlanId === 'FL6zMNWhnSUooe') {
    return isNoCodeMonetizationExperimentOn ? '2%' : null;
  }

  const pricingPlanConfig = user?.pricing_plan_config;

  if (!pricingPlanConfig) return null;

  const nocodeappPricingApplicable = pricingPlanConfig?.nocodeapp_pricing_applicable;
  const nocodeappPercentRate = pricingPlanConfig?.nocodeapp_percent_rate;

  if (!nocodeappPricingApplicable || !nocodeappPercentRate) return null;
  const truncatedPlan = Math.floor(Number(nocodeappPercentRate) * 10) / 10;
  return truncatedPlan % 1 === 0 ? `${truncatedPlan.toFixed(0)}%` : `${truncatedPlan.toFixed(1)}%`;
};

export default getPricingPlan;
