import * as React from 'react';
import { Heading, Box, Alert, InfoIcon } from '@razorpay/blade/components';

import { CardButton } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/CardButton';
import { GetStartedCards } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';

import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type GetStartedProps = {
  onCreateRule: (type: 'shipping' | 'payment', rule?: Rule) => void;
  isAppUpdateRequired: boolean;
};
export const GetStarted: React.FC<GetStartedProps> = ({ onCreateRule, isAppUpdateRequired }) => {
  return (
    <>
      {isAppUpdateRequired && (
        <Alert
          color="notice"
          isDismissible={false}
          description={<span>{GetStartedCards.appUpdateNotice}</span>}
          icon={() => <InfoIcon color="feedback.icon.notice.intense" />}
          marginBottom="spacing.2"
          isFullWidth
        />
      )}
      <Heading
        color="surface.text.gray.normal"
        size="medium"
        weight="semibold"
        marginBottom="spacing.1"
      >
        Create New
      </Heading>
      <Box display="flex" gap="spacing.8">
        <CardButton onClick={() => onCreateRule('shipping')} {...GetStartedCards.shipping} />
        <CardButton onClick={() => onCreateRule('payment')} {...GetStartedCards.payment} />
      </Box>
    </>
  );
};
