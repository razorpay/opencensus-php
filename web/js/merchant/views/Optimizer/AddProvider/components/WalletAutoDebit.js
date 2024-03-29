import React from 'react';
import { Box, HelpCircleIcon, Text, Switch } from '@razorpay/blade/components';

import Popover, { PopoverBody } from 'common/ui/Popover';

const WalletAutoDebit = (props) => {
  const { label, provider, changeEnableAutoDebitSwitch } = props;

  return (
    <Box display="flex" alignItems="center">
      <Box minWidth="180px" display="flex" alignItems="center">
        <Text>Wallet auto-debit</Text>
        <Box display="flex" alignItems="center" marginLeft="spacing.2">
          <HelpCircleIcon size="medium" color="feedback.icon.neutral.intense" />
          <Popover theme="dark" align="right">
            <PopoverBody>
              Wallet auto-debit will allow your users to pay via wallet balance directly without
              switching apps or exiting your website. Please check if you&#39;re eligible before
              enabling auto-debit on your account.
            </PopoverBody>
          </Popover>
        </Box>
      </Box>
      <Box as="label" display="flex" alignItems="center" gap="spacing.2">
        <Switch
          isChecked={provider?.Gateway_details?.[label]}
          onChange={(e) => changeEnableAutoDebitSwitch(e.isChecked)}
          accessibilityLabel="Toggle wallet auto-debit"
        />
        <Text color="surface.text.gray.muted">
          {provider?.Gateway_details?.[label] ? 'Enabled' : 'Disabled'}
        </Text>
      </Box>
    </Box>
  );
};

export default WalletAutoDebit;
