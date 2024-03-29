import React from 'react';
import XBankingWidget from 'common/ui/XBankingWidget';
import './razorpayx-widget-content.styl';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

const RazorpayXWidget = () => {
  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
      <XBankingWidget />;
    </BladeProvider>
  );
};

export default RazorpayXWidget;
