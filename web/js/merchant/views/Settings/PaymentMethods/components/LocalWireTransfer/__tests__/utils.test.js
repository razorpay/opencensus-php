import { openSupport } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/utils';
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
