import { screen, userEvent, waitFor } from 'test-utils';
import * as devices from 'merchant/components/Home/data';
import { props, renderApp } from './mocks/fixtures/PaymentMethodSplit';

describe('PaymentMethodSplit', () => {
  test('should show doughnut chart and payment methods', async () => {
    renderApp();
    const doughnutChart = screen.getByText('Doughnut chart');
    expect(doughnutChart).toBeInTheDocument();
    await userEvent.hover(doughnutChart);
    props.paymentByMethod.forEach(({ value, expectedLabel }) => {
      expect(screen.getByText(expectedLabel)).toBeInTheDocument();
      expect(screen.getByText(`${value}%`)).toBeInTheDocument();
    });
  });

  test('should show doughnut chart and payment methods on mobile view', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => true);
    const paymentByMethod = [props.paymentByMethod[0]];
    renderApp({
      isMobile: true,
      paymentByMethod,
    });
    const doughnutChart = screen.getByText('Doughnut chart');
    expect(doughnutChart).toBeInTheDocument();
    await userEvent.hover(doughnutChart);
    paymentByMethod.forEach(({ expectedLabel }) => {
      expect(screen.getByText(expectedLabel)).toBeInTheDocument();
    });
  });

  describe('Success rate banner', () => {
    test('should show success rate banner when shouldShowSrBanner is true', async () => {
      renderApp({
        shouldShowSrBanner: true,
      });
      await waitFor(() => {
        expect(screen.getByText(`${props.successRateData}% success rate`)).toBeInTheDocument();
      });
    });
    test('should not show success rate banner when shouldShowSrBanner is false', async () => {
      renderApp({
        shouldShowSrBanner: false,
      });
      await waitFor(() => {
        expect(
          screen.queryByText(`${props.successRateData}% success rate`),
        ).not.toBeInTheDocument();
      });
    });
  });
});
