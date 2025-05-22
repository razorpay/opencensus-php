import React, { ReactNode, Suspense, lazy } from 'react';
import { Box } from '@razorpay/blade/components';
import useHomepageState from '@FTUX/hooks/useHomepageState';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import { PageLayoutLoader } from 'apps/onboarding-experience/src/common/components/PageLayoutLoader';

// Lazy load components
const AccordionSection = lazy(
  () => import(/* webpackChunkName: 'AccordionSection' */ './AccordionSection'),
);
const NocodeSection = lazy(() => import(/* webpackChunkName: 'NoCodeSection' */ './NoCodeSection'));
const BrowseAllProducts = lazy(
  () => import(/* webpackChunkName: 'BrowseAllProducts' */ './BrowseAllProducts'),
);
const PaymentHandle = lazy(() => import(/* webpackChunkName: 'PaymentHandle' */ './PaymentHandle'));
const TransactionBanner = lazy(
  () => import(/* webpackChunkName: 'TransactionBanner' */ './TransactionBanner'),
);
const AddWebsiteNudge = lazy(
  () => import(/* webpackChunkName: 'AddWebsiteNudge' */ './AddWebsiteNudge'),
);
const WaysToAcceptPayment = lazy(
  () => import(/* webpackChunkName: 'WaysToAcceptPayment' */ './WaysToAcceptPayment'),
);
const WelcomeHeader = lazy(() => import(/* webpackChunkName: 'WelcomeHeader' */ './WelcomeHeader'));

const ELEMENTS_MAP: Record<HOMEPAGE_ELEMENTS, ReactNode> = {
  [HOMEPAGE_ELEMENTS.ACCORDION]: <AccordionSection />,
  [HOMEPAGE_ELEMENTS.NOCODE_NUDGE]: <NocodeSection />,
  [HOMEPAGE_ELEMENTS.BROWSE_ALL]: <BrowseAllProducts />,
  [HOMEPAGE_ELEMENTS.PAYMENT_HANDLE]: <PaymentHandle />,
  [HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT]: <WaysToAcceptPayment />,
  [HOMEPAGE_ELEMENTS.WEBSITE_NUDGE]: <AddWebsiteNudge />,
  [HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION]: <TransactionBanner />,
};

/**
 * Home component - Main layout for the FTUX (First Time User Experience) page
 * Renders different sections based on the user's onboarding state
 */
const App = () => {
  // Get the current homepage state which determines which elements to show
  const homepageState = useHomepageState();

  return (
    <Box paddingX="spacing.5" paddingY="spacing.1">
      {homepageState.length < 1 ? (
        <PageLayoutLoader />
      ) : (
        <Suspense fallback={<PageLayoutLoader />}>
          <WelcomeHeader />
          <Box paddingY="spacing.1" display="flex" flexDirection="column" gap="spacing.7">
            {/* Dynamically render components based on the homepage state */}
            {homepageState.map((element: HOMEPAGE_ELEMENTS, idx: number) => (
              <Suspense key={idx} fallback={<PageLayoutLoader />}>
                {ELEMENTS_MAP[element]}
              </Suspense>
            ))}
          </Box>
        </Suspense>
      )}
    </Box>
  );
};

export default App;
