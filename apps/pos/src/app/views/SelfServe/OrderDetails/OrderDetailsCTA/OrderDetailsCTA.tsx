import React from 'react';
import { Button, ChevronRightIcon } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useNavigate } from 'react-router-dom';

import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';

const OrderDetailsCTA = (): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const navigate = useNavigate();

  const handleOnContinueClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Continue Shopping',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Post Checkout',
      l2FunnelStage: 'Order Confirmation',
      section: 'Post-checkout',
      subSection: 'Order Confirmation',
    });

    navigate('/pos/catalog');
  };

  const handleOnViewOrdersClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'View Orders',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Post Checkout',
      l2FunnelStage: 'Order Confirmation',
      section: 'Post-checkout',
      subSection: 'Order Confirmation',
    });

    navigate('/pos/orders');
  };

  return (
    <React.Fragment>
      <Button
        size="large"
        icon={ChevronRightIcon}
        iconPosition="right"
        marginRight="spacing.5"
        marginBottom="spacing.5"
        isFullWidth={isMobile}
        onClick={handleOnContinueClick}
      >
        Continue Shopping
      </Button>
      <Button
        variant="secondary"
        size="large"
        marginBottom="spacing.5"
        isFullWidth={isMobile}
        onClick={handleOnViewOrdersClick}
      >
        View Orders
      </Button>
    </React.Fragment>
  );
};

export default OrderDetailsCTA;
