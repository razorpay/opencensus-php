import React from 'react';

import { render, screen, within } from 'test-utils';

import RewardsInfo from '../RewardsInfo';

describe('ExporterRewardsOnBoarding', () => {
  beforeEach(() => {
    render(<RewardsInfo />);
  });

  test('displays onboarding information correctly', () => {
    expect(screen.getAllByRole('heading')).toHaveLength(2);
    // Assert headers
    expect(screen.getByText('Rewards - Fee Credits')).toBeInTheDocument();
    expect(
      screen.getByText('How do I increase my International payments GMV on Razorpay'),
    ).toBeInTheDocument();

    // Assert fee credits information
    expect(
      screen.getByText(
        'Fee credits are the credits using which you can receive the full settlement amount without any fee deduction. Specific business categories also use these credits to help them meet regulatory requirements.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('For example:')).toBeInTheDocument();
    expect(
      screen.getByText(
        'If you have a fee credit of ₹100.00, all the transactions will be settled in full, and the fees for these payments will be deducted from the ₹100 fee credit.',
      ),
    ).toBeInTheDocument();

    // Assert increase GMV information
    expect(
      screen.getByText(
        'Here are a few tips and tricks to increase international payments GMV on Razorpay:',
      ),
    ).toBeInTheDocument();

    // Assert list items
    const list = screen.getByRole('list');
    const listItems = within(list).getAllByRole('listitem');
    expect(listItems).toHaveLength(4);
  });
});
