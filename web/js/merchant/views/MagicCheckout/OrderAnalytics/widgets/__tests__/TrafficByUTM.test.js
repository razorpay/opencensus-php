import React from 'react';
import TrafficByUTM from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/TrafficByUTM';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { API_RESPONSE } from 'merchant/views/MagicCheckout/OrderAnalytics/__tests__/mocks/fixtures';
import { LIMIT } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <TrafficByUTM {...props} />
    </Provider>
  );
};

describe('Magic - TrafficByUTM Widget', () => {
  test('should render the widget', () => {
    render(<App data={API_RESPONSE.data.metrics.traffic_utm} />);
    expect(screen.getByText('Traffic by UTM parameters')).toBeInTheDocument();
    expect(screen.getByText('WHATSAPP')).toBeInTheDocument();
    expect(screen.getByText('248')).toBeInTheDocument();
    expect(screen.getByText('₹1.82L')).toBeInTheDocument();
    expect(screen.getByRole('combobox')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Source' }).selected).toBe(true);
  });
  test('should change columns when campaign is selected in dropdown', async () => {
    const CAMPAIGN_NAME = 'Magic Test Campaign';
    render(<App data={API_RESPONSE.data.metrics.traffic_utm} />);
    expect(screen.queryByText(CAMPAIGN_NAME)).not.toBeInTheDocument();
    await userEvent.selectOptions(
      screen.getByRole('combobox'),
      screen.getByRole('option', { name: 'Campaign' }),
    );
    expect(screen.getByRole('option', { name: 'Campaign' }).selected).toBe(true);
    expect(screen.queryByText(CAMPAIGN_NAME)).toBeInTheDocument();
  });
  test('pagination', async () => {
    const elOnNextPage = API_RESPONSE.data.metrics.traffic_utm.utm_source[LIMIT + 1]?.label;
    render(<App data={API_RESPONSE.data.metrics.traffic_utm} />);
    expect(screen.queryByText(elOnNextPage)).not.toBeInTheDocument();
    const prevBtn = screen.getByRole('button', { name: 'Previous' });
    const nextBtn = screen.getByRole('button', { name: 'Next' });
    expect(prevBtn).toBeDisabled();
    expect(nextBtn).toBeEnabled();

    await userEvent.click(nextBtn);
    expect(screen.queryByText(elOnNextPage)).toBeInTheDocument();
    expect(prevBtn).toBeEnabled();
    expect(nextBtn).toBeDisabled();

    await userEvent.click(prevBtn);
    expect(screen.queryByText(elOnNextPage)).not.toBeInTheDocument();
  });
});
