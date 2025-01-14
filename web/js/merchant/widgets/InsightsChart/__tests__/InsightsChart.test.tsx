import React from 'react';

import InsightsChart from 'merchant/widgets/InsightsChart';
import { INSIGHTS_CHART_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { durationOptionsMap } from 'merchant/widgets/InsightsChart/utils';

import { renderWithSuspense, screen, userEvent, waitFor } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: {} }),
  withSplitzService: jest.fn(),
}));

const renderApp = ({ ...props }) =>
  renderWithSuspense(
    <InsightsChart {...INSIGHTS_CHART_MOCK_RESPONSE} isLoading={false} queryKey={[]} {...props} />,
  );

describe('Widgets -> InsightsChart', () => {
  test('should display loader', async () => {
    renderApp({ isLoading: true });
    await waitFor(() => {
      expect(screen.getAllByTestId('insights-chart-loader')).toHaveLength(
        INSIGHTS_CHART_MOCK_RESPONSE.components.length,
      );
    });
  });
  test('should display default loader count', async () => {
    renderApp({ isLoading: true, components: [] });
    await waitFor(() => {
      expect(screen.getAllByTestId('insights-chart-loader')).toHaveLength(6);
    });
  });

  test('should render component with valid props', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      expect(screen.queryByTestId('insights-chart-loader')).not.toBeInTheDocument();
      expect(
        screen.getByText(new RegExp(`${INSIGHTS_CHART_MOCK_RESPONSE.title}`, 'i')),
      ).toBeVisible();
    });
  });

  test('should render date dropdown correctly', async () => {
    renderApp({ isLoading: false });
    const currentDateString =
      durationOptionsMap[INSIGHTS_CHART_MOCK_RESPONSE.inputs[0].default_value];
    const unselectedDateString = durationOptionsMap.today;

    const dateDropDownCombobox = await screen.findByRole('combobox', {
      name: /date picker/i,
    });

    await userEvent.click(dateDropDownCombobox);

    expect(
      screen.getByRole('option', {
        name: new RegExp(currentDateString, 'i'),
      }),
    ).toBeInTheDocument();

    expect(
      screen.getByRole('option', {
        name: new RegExp(unselectedDateString, 'i'),
      }),
    ).toBeInTheDocument();
  });
});
