import Onboarding from 'merchant/views/PaymentHandle/views/Onboarding';
import { handleInfo as data } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/common';

HTMLCanvasElement.prototype.getContext = jest.fn();

export const paymentHandle = {
  handleInfo: {
    loading: false,
    data,
    error: null,
  },
};

jest.mock('merchant/views/PaymentHandle/views/Onboarding/EditPaymentHandle', () => () => (
  <div>Edit Payment Handle Modal</div>
));

jest.mock('common/new-ui/Lottie', () => () => <div>loading lottie</div>);

export const App = (props = {}) => {
  return <Onboarding {...props} />;
};
