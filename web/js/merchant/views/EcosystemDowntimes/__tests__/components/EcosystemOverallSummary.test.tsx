import React from 'react';
import { screen, render } from 'test-utils';
import EcosystemOverallSummary from 'merchant/views/EcosystemDowntimes/components/EcosystemOverallSummary';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import { downtime_mock_response } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';
import { processOnGoingDowntimes } from 'merchant/views/EcosystemDowntimes/helpers';
import type { EcosystemDowntimesContextType } from 'merchant/views/EcosystemDowntimes/types';

const initProps: EcosystemDowntimesContextType = {
  state: {
    activeDowntimes: {},
  },
  dispatch: jest.fn(),
  refreshData: jest.fn(),
};

const App = (props): JSX.Element => {
  return (
    <EcosystemDowntimeContext.Provider value={props}>
      <EcosystemOverallSummary />
    </EcosystemDowntimeContext.Provider>
  );
};

describe('<EcosystemOverallSummary/>', () => {
  it('should render EcosystemOverallSummary on screen', () => {
    render(<App {...initProps} />);
    expect(screen.getByTestId('ecosystem-overall-summary-container')).toBeInTheDocument();
  });

  it('should render EcosystemOverallSummary with All methods are functional', () => {
    render(<App {...initProps} />);
    expect(screen.getByLabelText('overall-summary-text')).toHaveTextContent(
      'All methods are operational',
    );
  });

  it('should render EcosystemOverallSummary with Few drops in Cards, UPI', () => {
    const statusProps = {
      ...initProps,
      state: {
        activeDowntimes: processOnGoingDowntimes(downtime_mock_response.data),
      },
    };
    render(<App {...statusProps} />);
    expect(screen.getByLabelText('overall-summary-text')).toHaveTextContent(
      'Few drops noticed in Cards, UPI',
    );
  });
});
