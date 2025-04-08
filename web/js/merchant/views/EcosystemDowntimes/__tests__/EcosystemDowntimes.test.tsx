import React from 'react';
import { render, userEvent, screen, waitFor } from 'test-utils';
import EcosystemDowntimes from 'merchant/views/EcosystemDowntimes';
import MethodsContainer from 'merchant/views/EcosystemDowntimes/containers/MethodsContainer';

jest.mock('merchant/views/EcosystemDowntimes/containers/MethodsContainer');

jest.mock('merchant/views/EcosystemDowntimes/components/EcosystemRefreshNudge', () => ({
  __esModule: true,
  default: () => <div data-testid="test-method-container">Test Refresh Nudge</div>,
}));

jest.mock('merchant/views/EcosystemDowntimes/context', () => ({
  EcosystemDowntimeProvider: ({ children }) => <div>{children}</div>,
}));

const App = (): JSX.Element => {
  return (
    <EcosystemDowntimes
      mode="live"
      showMobileNav={true}
      showNewHomePage={false}
    />
  );
};

describe('<EcosystemDowntimes/>', () => {
  test('should hightlight status detail icon after clicked ', async () => {
    const mockedMethodsContainer = MethodsContainer as jest.MockedFunction<typeof MethodsContainer>;
    mockedMethodsContainer.mockImplementation(() => (
      <div data-testid="test-method-container">Test Methods</div>
    ));
    render(<App />);
    await waitFor(() => {
      expect(screen.getByLabelText('status-detail-icon')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByLabelText('status-detail-icon'));
    expect(screen.getByLabelText('status-detail-icon-container')).toHaveClass(
      'status-details--active',
    );
  });

  test('should render MethodsContainer once clicked on status detail icon', async () => {
    const mockedMethodsContainer = MethodsContainer as jest.MockedFunction<typeof MethodsContainer>;
    mockedMethodsContainer.mockImplementation(() => (
      <div data-testid="test-method-container">Test Methods</div>
    ));
    render(<App />);
    await waitFor(() => {
      expect(screen.getByLabelText('status-detail-icon')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByLabelText('status-detail-icon'));
    expect(screen.getByLabelText('ecosystem-health-container')).toBeInTheDocument();
  });

  test('should render fallback component i.e old payment status in case of error', async () => {
    render(<App />);
    const mockedMethodsContainer = MethodsContainer as jest.MockedFunction<typeof MethodsContainer>;
    mockedMethodsContainer.mockImplementation(() => <>{new Error('Something went wrong')}</>);
    await waitFor(() => {
      expect(screen.getByLabelText('status-detail-icon')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByLabelText('status-detail-icon'));
    await waitFor(() => {
      expect(screen.getByLabelText('payment-methods-status-heading')).toHaveTextContent(
        'Payment Methods Status',
      );
    });
  });
});
