import {
  openSupport,
  hasMCCInEligibleError,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/utils';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('Tests for utils', () => {
  test('Test for openSupport', () => {
    openSupport();
    expect(CreateTicketEmitter.emit).toHaveBeenCalled();
  });
});

describe('Test hasMCCInEligibleError', () => {
  test('Should return true if MCC code is not eligible', () => {
    const error = 'we do not support ACH and SWIFT account for the MCC 1124';

    expect(hasMCCInEligibleError(error)).toBe(true);
  });

  test('Should return false if error is falsy value', () => {
    let error = null;
    expect(hasMCCInEligibleError(error)).toBe(false);

    error = undefined;
    expect(hasMCCInEligibleError(error)).toBe(false);

    error = '';
    expect(hasMCCInEligibleError(error)).toBe(false);
  });
});
