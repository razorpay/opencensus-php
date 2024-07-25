import { useRoutes, RouteObject } from 'react-router-dom';
import { MERCHANT_ONBOARDING_ROUTES } from 'apps/pos/src/app/routes';

const MerchantOnboardingRoutes = (): JSX.Element => {
  const routes = useRoutes(MERCHANT_ONBOARDING_ROUTES as RouteObject);
  return routes;
};

export default MerchantOnboardingRoutes;
