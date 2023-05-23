import BatchUploadSubPage from 'merchant/views/PaymentPages/PaymentPages/BatchUploadSubPage/index';
import { render } from 'test-utils';
import store from 'merchant/store';
const globalState = store.getState();

const renderApp = (initialState = {}, props = {}) => {
  return render(<BatchUploadSubPage {...props} />, {
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: initialState?.session?.user ?? globalState?.session?.user,
        org: initialState?.session?.org ?? globalState?.session?.org,
      },
      wysiwyg: {
        ...globalState.wysiwyg,
        isBatchPaymentPages:
          initialState?.wysiwyg?.isBatchPaymentPages ?? globalState?.wysiwyg?.isBatchPaymentPages,
      },
    },
  });
};

export { renderApp };
