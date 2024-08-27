import React from 'react';

import CashAdvanceRedirectToX from 'merchant/views/Capital/CashAdvance/withEDIMigration';
import { render, screen, waitFor, userEvent } from 'test-utils';

const defaultWindowOpen = window.open;
const mockedWindowOpen = jest.fn();
window.open = mockedWindowOpen;

describe('withEDIMigration', () => {
  beforeEach(() => {
    mockedWindowOpen.mockClear();
  });

  afterAll(() => {
    window.open = defaultWindowOpen;
  });

  it('should render new dashboard redirection UI', async () => {
    const user = userEvent.setup();
    render(<CashAdvanceRedirectToX />);
    expect(screen.getByText('Cash Advance has been moved to a new dashboard')).toBeInTheDocument();
    expect(screen.getByText('Cash Advance')).toBeInTheDocument();
    expect(screen.getByText('Facilitated by')).toBeInTheDocument();
    expect(screen.getByText('RTSPL')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Get additional money whenever required, repay and borrow again up to your limit any number of times.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Redirecting in/i)).toBeInTheDocument();
    expect(screen.getByText(/Seconds/i)).toBeInTheDocument();
    await user.click(screen.getByRole('link'));
    await waitFor(() => {
      expect(screen.queryByText(/Redirecting in/i)).not.toBeInTheDocument();
    });
    expect(screen.getByRole('link')).toHaveAttribute(
      'href',
      'https://x.razorpay.com/capital/cash-advance?from=dashboard',
    );
    expect(mockedWindowOpen).not.toHaveBeenCalled();
  });
});
