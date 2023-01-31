import AddAmountButton from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/AddAmountButton';
import { render } from 'test-utils';
import store from 'merchant/store';
const stateSpy = jest.spyOn(store, 'getState');
const defaultState = stateSpy.mockReturnValue({
  session: {
    user: {},
    org: {
      features: ['hide_dynamic_price_pp'],
    },
  },
});

let _hideDynamicPriceField = false;
const hideDynamicPriceField = (flag) => {
  _hideDynamicPriceField = flag;
};

const renderApp = ({ initialState = {} } = {}) => {
  return render(<AddAmountButton hideDynamicPriceField={_hideDynamicPriceField} />, {
    initialState: { ...defaultState, ...initialState },
  });
};

export { renderApp, defaultState, hideDynamicPriceField };
