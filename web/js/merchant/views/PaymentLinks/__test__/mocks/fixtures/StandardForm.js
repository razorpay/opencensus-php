import StandardForm from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/Forms/StandardForm';
import { render } from 'test-utils';
import store from 'merchant/store';
const stateSpy = jest.spyOn(store, 'getState');
const defaultState = stateSpy.mockReturnValue({
  session: {
    user: { isOrgAllowedFunctionality: jest.fn() },
    org: {
      features: ['enable_payer_name_for_pl'],
    },
  },
});
const formData = {};
const remindersConfig = {};
const renderApp = ({ initialState = {} } = {}) => {
  return render(
    <StandardForm showPayerName={true} formData={formData} remindersConfig={remindersConfig} />,
    {
      initialState: { ...defaultState, ...initialState },
    },
  );
};

export { renderApp, defaultState };
