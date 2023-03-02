import PaymentHandle from 'merchant/views/PaymentHandle';
import { handleInfo as data } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/common';

HTMLCanvasElement.prototype.getContext = jest.fn();

jest.mock('merchant/views/PaymentHandle/views/List', () => () => <div>Payment List</div>);

export const paymentHandle = {
  handleInfo: {
    loading: false,
    data,
    error: null,
  },
};

export const MockFetchErrorResponse = {
  status_code: 400,
  success: false,
  errors: [
    'Payment Handle does not exists for this merchant. Please create a new one',
    'Status Code: 400',
  ],
};

export const App = (props = {}) => {
  return <PaymentHandle {...props} />;
};
