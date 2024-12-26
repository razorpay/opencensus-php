import { render, waitFor } from 'test-utils';

import PaymentMethod from 'merchant/views/Settings/PaymentMethods';

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            enable_web_scrapper: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

describe('PaymentMethod', () => {
  test('should render sub heading text - Raise a request to your bank relationship manager for such payment methods., when org feature flag hide_instrument_request is set', async () => {
    const { getByText } = render(<PaymentMethod />, {
      initialState: {
        session: { user: { isInstrumentRequestHidden: true } },
      },
    });

    await waitFor(() =>
      expect(
        getByText(/Raise a request to your bank relationship manager for such payment methods./),
      ).toBeInTheDocument(),
    );
  });

  test('should render sub heading text - Raise a request directly from here to enable such payment methods., when org feature flag hide_instrument_request is set', async () => {
    const { getByText } = render(<PaymentMethod />, {
      initialState: {
        session: { user: { isInstrumentRequestHidden: false } },
      },
    });

    await waitFor(() =>
      expect(
        getByText(/Raise a request directly from here to enable such payment methods./),
      ).toBeInTheDocument(),
    );
  });
});
