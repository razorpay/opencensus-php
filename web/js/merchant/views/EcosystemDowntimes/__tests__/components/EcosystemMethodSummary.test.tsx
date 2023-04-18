import React from 'react';
import { screen, userEvent, render } from 'test-utils';
import EcosystemMethodSummary from 'merchant/views/EcosystemDowntimes/components/EcosystemMethodSummary';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import { processOnGoingDowntimes } from 'merchant/views/EcosystemDowntimes/helpers';
import { downtime_mock_response } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/mockResponses';
import type { EcosystemDowntimesContextType } from 'merchant/views/EcosystemDowntimes/types';

const initProps: EcosystemDowntimesContextType = {
  state: {
    activeDowntimes: {},
  },
  dispatch: jest.fn(),
  refreshData: jest.fn(),
};

type AppTypes = {
  initialProps: EcosystemDowntimesContextType;
  method?: string;
};

const App = ({ initialProps, method = '' }: AppTypes): JSX.Element => {
  return (
    <EcosystemDowntimeContext.Provider value={initialProps}>
      <EcosystemMethodSummary method={method} maxToShow={1} />
    </EcosystemDowntimeContext.Provider>
  );
};

describe('<EcosystemMethodSummary/>', () => {
  it('should render EcosystemMethodSummary on screen', () => {
    render(<App initialProps={initProps} />);
    expect(screen.getByLabelText('ecosystem-method-summary')).toBeInTheDocument();
  });

  it('should render EcosystemMethodSummary with All instruments are functional if no downtimes', () => {
    render(<App initialProps={initProps} />);
    expect(screen.getByText('All instruments are functional')).toBeInTheDocument();
  });

  it('should render EcosystemMethodSummary with one summary if downtimes exists and list not expanded', () => {
    const propsWithDowntimes = {
      ...initProps,
      state: {
        activeDowntimes: processOnGoingDowntimes(downtime_mock_response.data),
      },
    };
    render(<App initialProps={propsWithDowntimes} method="card" />);
    const listItems = screen.getAllByRole('listitem');
    expect(listItems).toHaveLength(1);
  });

  it('should render EcosystemMethodSummary with one summary if downtimes exists in sorted order after list expanded', async () => {
    const propsWithDowntimes = {
      ...initProps,
      state: {
        activeDowntimes: processOnGoingDowntimes(downtime_mock_response.data),
      },
    };
    render(<App initialProps={propsWithDowntimes} method="card" />);
    const sortedHeadings = ['High Severity Downtime', 'Medium Severity Downtime'];
    await userEvent.click(screen.getByTestId('show-more-summary'));

    const listItems = screen.getAllByLabelText('method-summary-heading');
    expect(listItems).toHaveLength(2);

    listItems.forEach((item, index) => expect(item).toHaveTextContent(sortedHeadings[index]));
  });

  it('should render EcosystemMethodSummary in collapsed state after clicking on show less', async () => {
    const propsWithDowntimes = {
      ...initProps,
      state: {
        activeDowntimes: processOnGoingDowntimes(downtime_mock_response.data),
      },
    };
    render(<App initialProps={propsWithDowntimes} method="card" />);
    await userEvent.click(screen.getByTestId('show-more-summary'));

    await userEvent.click(screen.getByTestId('show-less-summary'));
    const listItems = screen.getAllByLabelText('method-summary-heading');
    expect(listItems).toHaveLength(1);
  });
});
