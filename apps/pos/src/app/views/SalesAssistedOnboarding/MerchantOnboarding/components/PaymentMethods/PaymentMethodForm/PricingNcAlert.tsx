import React from 'react';
import { PricingNcComment } from 'apps/pos/src/app/types/PaymentsAndService';
import { Alert, AlertCircleIcon, Box } from '@razorpay/blade/components';
import { parseHtmlString } from 'apps/pos/src/app/utils/paymentsAndServices';

interface PricingNcCommentProps {
  pricingNcCommentDetails: PricingNcComment | null;
}
const PricingNcAlert = ({pricingNcCommentDetails}:PricingNcCommentProps): JSX.Element => {
  return (
    <Box marginBottom={'spacing.5'}>
      <Alert
        testID="pricing-nc-alert"
        emphasis="subtle"
        isDismissible={false}
        color="notice"
        description={parseHtmlString(pricingNcCommentDetails?.value).comment || ''}
        title={pricingNcCommentDetails?.title || 'Pricing Needs Clarification'}
        icon={AlertCircleIcon}
      />
    </Box>
  );
};

export default PricingNcAlert;
