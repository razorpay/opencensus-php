import { getNoCodeMonetizationExperiment } from './getNoCodeMonetizationExperiment';

const getPricingPlan = (user: any) => {
  const pricingPlanId = user?.merchant?.pricing_plan_id;
  const isNoCodeMonetizationExperimentOn = getNoCodeMonetizationExperiment();

  if (!pricingPlanId) {
    return null;
  }

  switch (pricingPlanId) {
    case 'Og341WhcTYm2JZ':
      return '2.1%';
    case 'Og362mlR9xxuoY':
      return '2.2%';
    case 'Og373UFu1pj3i1':
      return '2.5%';
    case '1In3Yh5Mluj605':
      return isNoCodeMonetizationExperimentOn ? '2%' : null;
    case 'FL6zMNWhnSUooe':
      return isNoCodeMonetizationExperimentOn ? '2%' : null;
    default:
      return null;
  }
};

export default getPricingPlan;
