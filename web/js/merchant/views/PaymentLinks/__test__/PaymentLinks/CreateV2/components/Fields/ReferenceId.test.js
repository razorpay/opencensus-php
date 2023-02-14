import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen, fireEvent } from 'test-utils';
import ReferenceId from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/ReferenceId';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'receipt').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'receipt').mockImplementation(() => {});

describe('ReferenceId - Unit Test', () => {
  afterEach(() => {
    track.lj.fields.receipt.mockClear();
    track.segment.fields.receipt.mockClear();
  });

  const state = {
    session: {
      user: {
        isInvoiceReceiptMandatory: true,
      },
    },
  };

  const renderApp = (initialState = state, props = {}) => {
    return render(
      <Provider store={storeWithInitialState(initialState)}>
        <ReferenceId {...props} />
      </Provider>,
      {
        initialState: {
          session: {
            user: {
              isInvoiceReceiptMandatory: true,
            },
          },
        },
      },
    );
  };

  test('should render Reference Id label', () => {
    renderApp();
    expect(screen.getByText('Reference Id')).toBeInTheDocument();
  });

  test('should render Reference Id label', async () => {
    renderApp();
    const refernceId = screen.getByPlaceholderText('123456');
    await fireEvent.blur(refernceId);
    expect(track.lj.fields.receipt).toHaveBeenCalled();
    expect(track.segment.fields.receipt).toHaveBeenCalled();
  });
});
