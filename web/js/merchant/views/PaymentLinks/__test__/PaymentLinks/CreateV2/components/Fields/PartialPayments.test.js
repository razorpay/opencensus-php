import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen, userEvent } from 'test-utils';
import PartialPayments from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/PartialPayments';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'partialPayment').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'partialPayment').mockImplementation(() => {});

describe('PartialPayments - Unit Test', () => {
  afterEach(() => {
    track.lj.fields.partialPayment.mockClear();
    track.segment.fields.partialPayment.mockClear();
  });

  const state = {
    session: {
      user: {
        isMinimumFirstPaymentEnabled: true,
      },
    },
  };

  const renderApp = (initialState = state, props = {}) => {
    return render(
      <Provider store={storeWithInitialState(initialState)}>
        <PartialPayments {...props} defaultValue="1" />
      </Provider>,
      {
        initialState: {
          session: {
            user: {
              isMinimumFirstPaymentEnabled: true,
            },
          },
        },
      },
    );
  };

  test('should render enable partial payment', () => {
    renderApp();
    expect(screen.getByText('Partial Payment')).toBeInTheDocument();
    expect(screen.getByText('Enable Partial Payment')).toBeInTheDocument();
  });

  test('should displays the input for first payment minimum amount when partial payment is enabled', async () => {
    renderApp();
    const checkbox = screen.queryAllByRole('checkbox')[0];
    expect(checkbox).toBeInTheDocument();
    await userEvent.click(checkbox);
    await userEvent.tab();
    expect(track.lj.fields.partialPayment).toHaveBeenCalled();
    expect(track.segment.fields.partialPayment).toHaveBeenCalled();
  });

  test('should get the checkbox where amount is "0.00" present in the document', async () => {
    renderApp();
    const checkbox = screen.queryAllByRole('checkbox')[0];
    expect(checkbox).toBeInTheDocument();
    await userEvent.click(checkbox);
    expect(screen.getByPlaceholderText('0.00')).toBeInTheDocument();
  });
});
