import { loadWaitlistForm } from 'merchant/views/MagicCheckout/utils/waitlistForm';
import { createPopup } from '@typeform/embed';

const USER = {
  merchantId: 'xyz',
  email: 'test@gmail.com',
};

jest.mock('@typeform/embed', () => {
  return {
    createPopup: jest.fn().mockImplementation(() => {
      return {
        toggle: jest.fn(),
      };
    }),
  };
});

describe('test loadWaitlistForm', () => {
  test('should call the typeform open modal function', () => {
    loadWaitlistForm(USER);
    expect(createPopup).toHaveBeenCalled();
  });
});
