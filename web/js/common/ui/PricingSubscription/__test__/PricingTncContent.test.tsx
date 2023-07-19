import React from 'react';
import { render, screen } from 'test-utils';
import { TncContentMemo } from 'common/ui/PricingSubscription/PricingTnC';
import { TNC_CONTENT } from 'common/ui/PricingSubscription/constants';
import { PricingTncInfoMemo } from 'common/ui/PricingSubscription/PricingBundleCommon';

const tncText = 'Terms & Conditions';
describe('Tests for `TncModal & GST` components', () => {
  test('Should show Term n condition Content in Modal', () => {
    render(<TncContentMemo />);
    const title = screen.getByText(tncText);
    const subHeader = screen.getByTestId('tncSubHeader');
    expect(title).toBeInTheDocument();
    expect(subHeader).toBeInTheDocument();
    TNC_CONTENT.forEach(({ text }) => {
      expect(screen.getByText(text)).toBeInTheDocument();
    });
  });
  test('Should show GST & Term n condition context in pricing plan', () => {
    render(<PricingTncInfoMemo isMobile={true} />);
    const autoRenewal = screen.getByText('Auto Renewal Plans. No Refunds');
    const gstText = screen.getByText('*Prices mentioned are exclusive of GST');
    const fullTnC = screen.getByText(tncText);

    expect(autoRenewal).toBeInTheDocument();
    expect(gstText).toBeInTheDocument();
    expect(fullTnC).toBeInTheDocument();
  });
});
