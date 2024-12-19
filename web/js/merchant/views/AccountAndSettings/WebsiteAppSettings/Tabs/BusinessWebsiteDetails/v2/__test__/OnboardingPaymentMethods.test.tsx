import React from 'react';
import { render } from '@testing-library/react';

import OnboardingPaymentMethods from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/OnboardingPaymentMethods';

jest.mock('merchant/views/AccountAndSettings/PaymentMethods', () => () => (
  <div>PaymentMethods Component</div>
));
jest.mock('merchant/views/onboarding/FullPageViewWrapper', () => ({ children }) => (
  <div>{children}</div>
));

describe('OnboardingPaymentMethods', () => {
  test('renders FullPageViewWrapper', () => {
    const { container } = render(<OnboardingPaymentMethods />);
    expect(container).toContainHTML('<div>PaymentMethods Component</div>');
  });
});
