import { formatFormItems } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/helpers';
import {
  FORM_ITEMS_WITH_LATE_PAYMENT,
  FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE,
  FORM_ITEMS_WITH_OUT_LATE_PAYMENT,
} from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/Wysiwyg/constants';

describe('formatFormItems', () => {
  it('should add "Late Payment Due Date" if "Late Payment Charges" is available', () => {
    expect(formatFormItems(FORM_ITEMS_WITH_LATE_PAYMENT)).toStrictEqual(
      FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE,
    );
  });

  it('should not add "Late Payment Due Date" if "Late Payment Charges" is missing', () => {
    expect(formatFormItems(FORM_ITEMS_WITH_OUT_LATE_PAYMENT)).toStrictEqual(
      FORM_ITEMS_WITH_OUT_LATE_PAYMENT,
    );
  });

  it('should return "Late Payment Due Date" in edit flow', () => {
    expect(formatFormItems(FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE)).toStrictEqual(
      FORM_ITEMS_WITH_LATE_PAYMENT_AND_DUE_DATE,
    );
  });
});
