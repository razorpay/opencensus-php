import * as SettlementsDB from 'merchant/views/Settlements/tests/data/SettlementsDB';
import React from 'react';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { fireEvent, render, screen, delay } from 'test-utils';
import TestModal from 'common/services/test/TestModal';

jest.mock('merchant/models/User', () => ({
  __esModule: true,
  // eslint-disable-next-line func-name-matching
  default: function mock() {
    return {
      fetch: () => Promise.resolve({ data: null }),
    };
  },
}));

const mockGoBackToInitialModalView = jest.fn();

function App() {
  return (
    <TestModal
      component={
        <ScheduledModal
          eventCategory="Dashboard - Early Settlement"
          fromWhere="Settlements"
          goBackToInitialModalView={mockGoBackToInitialModalView}
        />
      }
    />
  );
}

test('test pre enalblement', async () => {
  render(<App />, { showModal: true });

  await delay();

  expect(screen.getByRole('heading', { name: /Enable Early Settlement/i })).toBeInTheDocument();
  expect(
    screen.queryByText(
      /Early settlements will automatically settle the amount to your account in few hours from the time of transaction, everyday./i,
    ),
  ).toBeInTheDocument();

  expect((screen.getByText(/Learn more/i) as HTMLAnchorElement).href).toBe(
    'http://razorpay.com/settlement',
  );

  expect(screen.getByText(/Here's how instantly it works/i)).toBeInTheDocument();
  expect(screen.getByText(/Everyday at/i)).toHaveTextContent(
    /Everyday at 9AM and 5PM all your payments get settled/i,
  );

  const expectedFee = `${SettlementsDB.scheduledPricing.percent_rate / 100}%`;

  expect(screen.getByText(/A minimal fee of/i)).toHaveTextContent(new RegExp(expectedFee));

  expect(screen.getByRole('button', { name: 'Enable Early Settlement' })).not.toBeDisabled();
});

test('test footer enablement', async () => {
  render(<App />, { showModal: true });

  await delay();

  // Initial call on mount
  expect(window.rzpAnalytics).toBeCalledTimes(1);
  expect(window.rzpAnalytics).toBeCalledWith({
    eventAction: 'Click Enable ES',
    eventCategory: 'Dashboard - Early Settlement',
    eventLabel: 'Enable Scheduled ES - Settlements',
  });

  const checkFaqElem = screen.getByText(/Check FAQs/i);
  const contactElem = screen.getByText(/Contact Support/i);

  expect((checkFaqElem as HTMLAnchorElement).href).toBe('https://razorpay.com/capital/#faqs');

  expect(contactElem).toBeInTheDocument();

  fireEvent.click(checkFaqElem);
  expect(window.rzpAnalytics).toBeCalledTimes(2);

  fireEvent.click(contactElem);
  expect(window.rzpAnalytics).toBeCalledTimes(3);
});

test('test post enalblement', async () => {
  render(<App />, { showModal: true });

  await delay();

  fireEvent.click(screen.getByRole('button', { name: /Enable Early Settlement/i }));

  await delay();

  expect(window.rzpAnalytics).toBeCalledTimes(3);
  expect(window.rzpAnalytics).toBeCalledWith({
    eventAction: 'ES Modal',
    eventCategory: 'Dashboard - Early Settlement',
    eventLabel: 'Scheduled ES Enabling attempt | Enable Scheduled ES',
  });

  await delay();

  expect(await screen.findByText(/Successfully Enabled!/i)).toBeVisible();
});
