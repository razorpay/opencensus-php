import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import PaymentFor from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/PaymentFor';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'paymentFor').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'paymentFor').mockImplementation(() => {});

describe('Payment For - Unit Test', () => {
  afterEach(() => {
    track.lj.fields.paymentFor.mockClear();
    track.segment.fields.paymentFor.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<PaymentFor {...props} />);
  };

  test('should render payment for in the document', () => {
    renderApp();
    expect(screen.getByText('Payment For')).toBeInTheDocument();
  });

  test('should displays the input for paymentFor', async () => {
    renderApp();
    const paymentForInput = screen.getByPlaceholderText('Payment description');
    await fireEvent.blur(paymentForInput);
    expect(track.lj.fields.paymentFor).toHaveBeenCalled();
    expect(track.segment.fields.paymentFor).toHaveBeenCalled();
  });
});
