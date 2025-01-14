import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { TabbedCharts } from 'merchant/widgets/TabbedCharts/index';
import { render, screen, userEvent } from 'test-utils';

import { TABBED_CHARTS_MOCKED_RESPONSE } from './mocks';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: {} }),
  withSplitzService: jest.fn(),
}));

describe('TabbedCharts', () => {
  const renderApp = ({ isLoading = false }) => {
    render(
      <SuspenseWithLoader>
        <TabbedCharts isLoading={isLoading} {...TABBED_CHARTS_MOCKED_RESPONSE} />
      </SuspenseWithLoader>,
    );
  };
  test('renders correctly', () => {
    renderApp({ isLoading: false });
    expect(screen.getByText(TABBED_CHARTS_MOCKED_RESPONSE.title)).toBeInTheDocument();
  });

  test('renders the skeletal loader when isLoading is true', () => {
    renderApp({ isLoading: true });
    expect(screen.getByTestId('loading-skeleton')).toBeInTheDocument();
  });

  test('renders the correct number of SelectInput components', () => {
    renderApp({ isLoading: false });
    const selectInputs = screen.getAllByRole('combobox');
    expect(selectInputs).toHaveLength(TABBED_CHARTS_MOCKED_RESPONSE.inputs.length);
  });

  test('renders the correct number of tabs and handles click behavior', async () => {
    renderApp({ isLoading: false });
    const tabs = screen.getAllByTestId(/tab-\d+/);
    expect(tabs).toHaveLength(TABBED_CHARTS_MOCKED_RESPONSE.components.length);
    await userEvent.click(tabs[1]);
    const activeTabTitle = screen.getByText(TABBED_CHARTS_MOCKED_RESPONSE.components[1].title);
    expect(activeTabTitle).toHaveStyle({
      color: 'rgb(25, 25, 25)',
    });
  });
});
