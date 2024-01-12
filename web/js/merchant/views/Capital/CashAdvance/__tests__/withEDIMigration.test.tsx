import React from 'react';
import withEDIMigration from 'merchant/views/Capital/CashAdvance/withEDIMigration';
import { SpiltzContextState } from 'common/splitz/types';
import { render, screen, waitFor, userEvent } from 'test-utils';

const variantOn = { capital_edi_dashboard_migration: { variables: { result: 'on' } } };

const defaultAbExperiments = {};
let mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments } as unknown as SpiltzContextState),
}));

const App = withEDIMigration(() => <p>Component</p>);

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

  it('should render new dashboard redirection UI when experiment active', async () => {
    mockAbExperiments = variantOn;
    const user = userEvent.setup();
    render(<App />);
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

  it('should render component when experiment inactive', () => {
    mockAbExperiments = defaultAbExperiments;
    render(<App />);
    expect(screen.getByText('Component')).toBeInTheDocument();
    expect(screen.queryByText(/Cash Advance/i)).not.toBeInTheDocument();
  });
});
