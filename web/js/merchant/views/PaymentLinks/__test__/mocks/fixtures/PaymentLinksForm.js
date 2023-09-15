import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import PaymentLinksForm from 'merchant/views/PaymentLinks/BatchUpload/components/PaymentLinksForm';

const CheckBoxField = ({ name, id, onChange }) => {
  return <input type="checkbox" name={name} id={id} onChange={onChange} />;
};

jest.mock('redux-form', () => ({
  Field: (props) => <CheckBoxField {...props} />,
}));

const mockObj = {
  active: true,
};

jest.mock('common/utils/rzp-utils', () => ({
  ...jest.requireActual('common/utils/rzp-utils'),
  findBy: () => jest.fn(() => mockObj),
  getCommonAnalyticsProperties: jest.fn(),
}));

export const App = (props = {}) => {
  return (
    <Provider
      store={storeWithInitialState({ session: { user: { isPaymentlinksV2Enabled: true } } })}
    >
      <PaymentLinksForm {...props} />
    </Provider>
  );
};
