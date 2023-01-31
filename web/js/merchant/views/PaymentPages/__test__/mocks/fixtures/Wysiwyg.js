import PaymentPagesWysiwyg from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg';
import { render } from 'test-utils';
import store from 'merchant/store';
const stateSpy = jest.spyOn(store, 'getState');
const defaultState = stateSpy.mockReturnValue({
  session: {
    user: {},
    org: {
      features: [],
    },
  },
});

const renderApp = (initialState = {}, props = {}) => {
  return render(<PaymentPagesWysiwyg {...props} />, {
    initialState: { ...defaultState, ...initialState },
  });
};

export { renderApp };
