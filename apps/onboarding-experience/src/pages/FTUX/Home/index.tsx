import React, { ReactNode } from 'react';
import { Box } from '@razorpay/blade/components';
import useHomepageState from '@FTUX/hooks/useHomepageState';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';

import AccordionSection from './AccordionSection';
import NocodeSection from './NoCodeSection';
import BrowseAllProducts from './BrowseAllProducts';
import PaymentHandle from './PaymentHandle';
import TransactionBanner from './TransactionBanner';
import AddWebsiteNudge from './AddWebsiteNudge';
import WaysToCollectPayment from './WaysToCollectPayment';
import WelcomeHeader from './WelcomeHeader';

const ELEMENTS_MAP: Record<HOMEPAGE_ELEMENTS, ReactNode> = {
  [HOMEPAGE_ELEMENTS.ACCORDION]: <AccordionSection />,
  [HOMEPAGE_ELEMENTS.NOCODE_NUDGE]: <NocodeSection />,
  [HOMEPAGE_ELEMENTS.BROWSE_ALL]: <BrowseAllProducts />,
  [HOMEPAGE_ELEMENTS.PAYMENT_HANDLE]: <PaymentHandle />,
  [HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT]: <WaysToCollectPayment />,
  [HOMEPAGE_ELEMENTS.WEBSITE_NUDGE]: <AddWebsiteNudge />,
  [HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION]: <TransactionBanner />,
};

/**
 * Home component - Main layout for the FTUX (First Time User Experience) page
 * Renders different sections based on the user's onboarding state
 */
const Home = () => {
  // Get the current homepage state which determines which elements to show
  const homepageState = useHomepageState();

  return (
    <Box paddingX="spacing.5" paddingY="spacing.1">
      <WelcomeHeader />
      <Box paddingY="spacing.1" display="flex" flexDirection="column" gap="spacing.7">
        {/* Dynamically render components based on the homepage state */}
        {homepageState.map((element: HOMEPAGE_ELEMENTS) => ELEMENTS_MAP[element])}
      </Box>
    </Box>
  );
};

export default Home;
