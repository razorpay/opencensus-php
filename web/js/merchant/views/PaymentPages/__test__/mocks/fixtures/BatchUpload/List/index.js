import { render } from 'test-utils';
import BatchListContainer from 'merchant/views/PaymentPages/BatchUpload/List/index';
import store from 'merchant/store';

const globalState = store.getState();
const id = 'pl_LpoFCooJAk0a2j';
const title = 'PP Title';

// component is not mapped with withRouter, hence match props is passed directly
const defaultProps = {
  match: {
    params: {
      id,
      title,
    },
  },
  location: {
    search: '',
  },
};

const renderApp = (initialState = {}, props = {}, showModal = false) => {
  render(<BatchListContainer {...defaultProps} {...props} />, {
    showModal,
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: {
          ...(initialState?.session?.user ?? globalState?.session?.user),
          isOrgAllowedFunctionality: () => true,
        },
        org: initialState?.session?.org ?? globalState?.session?.org,
      },
      wysiwyg: globalState.wysiwyg,
    },
    renderOptions: {
      initialEntries: ['/paymentpages/batchuploads/pl_LpoFCooJAk0a2j/batch%20pp%20t%201501'],
      path: '/paymentpages/batchuploads/pl_LpoFCooJAk0a2j/batch%20pp%20t%201501',
    },
  });
};

export { renderApp, defaultProps };
