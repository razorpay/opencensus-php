import { screen, render } from 'test-utils';
import store from 'merchant/store';
import BatchDetailsContainer from 'merchant/views/PaymentPages/BatchUpload/index';

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

const renderApp = (initialState = {}, props = {}) => {
  render(<BatchDetailsContainer {...defaultProps} {...props} />, {
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
    renderOptions: {
      historyOptions: {
        initialEntries: ['/paymentpages/batchuploads/pl_LpoFCooJAk0a2j/batch%20pp%20t%201501'],
      },
      path: '/paymentpages/batchuploads/pl_LpoFCooJAk0a2j/batch%20pp%20t%201501',
    },
  });
};

describe('Batch Payment Page - Batch Details', () => {
  test('should render Batch details page with navigation details', () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    renderApp(initialState);
    const batchPaymentPagesLink = screen.getByRole('link', {
      name: 'Batch Payment Pages',
    });
    const titleLink = screen.getByRole('link', {
      name: 'PP Title',
    });
    const batchDetailsText = screen.getByText('Batch Details');
    expect(batchPaymentPagesLink).toBeInTheDocument();
    expect(titleLink).toBeInTheDocument();
    expect(batchDetailsText).toBeInTheDocument();
  });

  test('should render Batch details page with default navigation details', () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: false },
      },
      wysiwyg: { isBatchPaymentPages: false },
    };
    const props = {
      match: {
        params: {
          ...defaultProps.match.params,
          title: null,
        },
      },
    };
    renderApp(initialState, props);
    const batchPaymentPagesLink = screen.getByRole('link', {
      name: 'Batch Payment Pages',
    });
    const titleLink = screen.getByRole('link', {
      name: 'Title',
    });
    const batchDetailsText = screen.getByText('Batch Details');
    expect(batchPaymentPagesLink).toBeInTheDocument();
    expect(titleLink).toBeInTheDocument();
    expect(batchDetailsText).toBeInTheDocument();
  });
});
