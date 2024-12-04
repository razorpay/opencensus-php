import * as React from 'react';
import { Heading, Box } from '@razorpay/blade/components';

import { CardButton } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/CardButton';
import { GetStartedCards } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';

type GetStartedProps = {
  onCreateShippingRule: () => void;
  onCreatePaymentRule: () => void;
};
export const GetStarted: React.FC<GetStartedProps> = ({
  onCreatePaymentRule,
  onCreateShippingRule,
}) => {
  return (
    <>
      <Heading
        color="surface.text.gray.normal"
        size="medium"
        weight="semibold"
        marginBottom="spacing.5"
      >
        Create New
      </Heading>
      <Box display="flex" gap="spacing.8">
        <CardButton onClick={onCreateShippingRule} {...GetStartedCards.shipping} />
        <CardButton onClick={onCreatePaymentRule} {...GetStartedCards.payment} />
      </Box>
    </>
  );
};
