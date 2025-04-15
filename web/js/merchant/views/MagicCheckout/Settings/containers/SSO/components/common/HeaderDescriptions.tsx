import { Box } from '@razorpay/blade/components';
import React from 'react';

export default function HeaderDescriptions() {
  return (
    <Box display="flex" flexDirection="column">
      <Box>
        <b>Legacy Accounts Required on Shopify</b> - Enable Legacy Customer Accounts in Settings →
        Customer Accounts → Legacy Account to continue.{' '}
      </Box>
      <Box>
        <b>Captcha to be disabled on Shopify</b> - To disable captcha, go to Sales Channel → Online
        Store → Preferences to resolve.{' '}
      </Box>
    </Box>
  );
}
