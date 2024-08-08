/* eslint-disable @typescript-eslint/no-unsafe-argument */
import React from 'react';
import { render, screen } from 'test-utils';

import Loans from '../LoansV2';

function renderApp(isUnregisteredBusiness: boolean) {
  return render(<Loans />, {
    initialState: {
      session: {
        user: {
          business_type: isUnregisteredBusiness ? 11 : null,
          isAdminOrOwner: true,
        },
      },
    },
  });
}

describe('Capital/Loans', () => {
  test('it should render BL for registered business', () => {
    renderApp(false);
    expect(screen.getByText('Business Loans')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Working capital needs solved for our customers. Business financing up to Rs. 2 crores at zero collateral.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Powered by/i)).toBeInTheDocument();
    expect(screen.getByText(/privacy policy/i)).toBeInTheDocument();
    expect(screen.getByText(/terms of use/i)).toBeInTheDocument();
    expect(screen.getByText(/apply now!/i)).toBeInTheDocument();
    expect(screen.getByTestId(/apply/i)).toHaveAttribute(
      'href',
      'https://razorpay.typeform.com/to/kdCTwe1w?typeform-source=pg_dashboard',
    );
    expect(screen.getByText('Expand your team with top talent')).toBeInTheDocument();
    expect(screen.getByText('Meet critical payments on time, every time')).toBeInTheDocument();
    expect(screen.getByText('Gain control of your cash flow')).toBeInTheDocument();
    expect(
      screen.getByText('Fuel your growth initiatives and reach new heights'),
    ).toBeInTheDocument();
  });

  test('it should render PL for unregistered business', () => {
    renderApp(true);
    expect(screen.getByText('Insta Loans')).toBeInTheDocument();
    expect(
      screen.getByText('Secure instant funds up to Rs. 5,00,000 at zero collateral.'),
    ).toBeInTheDocument();
    expect(screen.getByText(/Powered by/i)).toBeInTheDocument();
    expect(screen.getByText(/privacy policy/i)).toBeInTheDocument();
    expect(screen.getByText(/terms of use/i)).toBeInTheDocument();
    expect(screen.getByText(/apply now!/i)).toBeInTheDocument();
    expect(screen.getByTestId(/apply/i)).toHaveAttribute(
      'href',
      'https://oneapp.abfldirect.com/b2c/login?dsa_hash=a03effa23e4a5bc7c48a68660058cf4b8b93d8d913108ca99f82cb0c4b088485',
    );
    expect(screen.getByText('Get flexible repayment options')).toBeInTheDocument();
    expect(screen.getByText('Solve cash flow gaps with ease')).toBeInTheDocument();
    expect(screen.getByText('Address unforeseen expenses immediately')).toBeInTheDocument();
    expect(screen.getByText('Seize new growth opportunities')).toBeInTheDocument();
  });
});
