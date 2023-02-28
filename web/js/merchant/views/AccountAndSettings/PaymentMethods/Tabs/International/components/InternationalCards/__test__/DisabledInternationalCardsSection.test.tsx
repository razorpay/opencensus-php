import DisabledInternationalCardsSection from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/DisabledInternationalCardsSection';
import { DisabledInternationalCardsReasons } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import * as track from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { updateWebsitePath } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner';

const trackIEEventSpy = jest.spyOn(track, 'trackIEEvent');

const renderApp = (reason) => render(<DisabledInternationalCardsSection reason={reason} />);

describe('DisabledInternationalCardsSection', () => {
  test('should render complete your KYC section when reason is not activated', async () => {
    const { history } = renderApp(DisabledInternationalCardsReasons.NOT_ACTIVATED);
    expect(screen.getByText('Complete your KYC to request for international cards'));
    const completeKYCButton = screen.getByRole('button', { name: 'Complete KYC' });
    expect(completeKYCButton).toBeInTheDocument();
    await userEvent.click(completeKYCButton);
    expect(trackIEEventSpy).toHaveBeenCalledWith({
      objectName: 'Complete KYC',
      actionName: 'Clicked',
    });
    expect(history.location.pathname).toBe('/activation');
    expect(
      screen.getByText('You can only request for this after KYC is complete'),
    ).toBeInTheDocument();
  });

  test('should render submit the required action section when reason is Risk FOH', () => {
    renderApp(DisabledInternationalCardsReasons.RISK_FOH);
    expect(screen.getByText('Submit the required document to request for international cards'));
    expect(
      screen.getByText(/You’ll need to submit one of the following documents of your business to/i),
    );
    const completeKYCButton = screen.queryByRole('button', { name: 'Complete KYC' });
    expect(completeKYCButton).not.toBeInTheDocument();
    const mailRiskFundsEmail = screen.getByRole('link', { name: 'riskfundsonhold@razorpay.com' });
    expect(mailRiskFundsEmail).toHaveAttribute('href', 'mailto:riskfundsonhold@razorpay.com');
    expect(
      screen.getByText(
        /Once you share the required details, we’ll share an update within 72 hours. You’ll be able to receive collected payments in your bank account and request for international payments only after this is complete/,
      ),
    ).toBeInTheDocument();
  });

  test('should render your business type is not supported section when reason is business unregistered', () => {
    renderApp(DisabledInternationalCardsReasons.UNREGISTERED);
    expect(
      screen.getByText('Your business type is not supported for international card payments'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Link your PayPal account to collect international payments'),
    ).toBeInTheDocument();
  });

  test('should render update your website details section when reason is no website details', async () => {
    const { history } = renderApp(DisabledInternationalCardsReasons.NO_WEBSITE_DETAILS);
    expect(screen.getByText('Update your website details to request for international payments'));
    const updateWebsiteDetails = screen.getByRole('button', { name: 'Update Website Details' });
    expect(updateWebsiteDetails).toBeInTheDocument();
    await userEvent.click(updateWebsiteDetails);
    expect(trackIEEventSpy).toHaveBeenCalledWith({
      objectName: 'Update Website Details',
      actionName: 'Clicked',
    });
    expect(history.location.pathname).toBe(updateWebsitePath);
    expect(history.location.search).toBe('?action=update-website-details');
    expect(
      screen.getByText(
        'Your website must be registered with Razorpay to request for international payments',
      ),
    ).toBeInTheDocument();
  });

  test("should return null if disabled reason doesn't match", () => {
    renderApp('');
    expect(screen.getByTestId('disabled-international-cards-message')).toBeEmptyDOMElement();
  });
});
