import { screen, waitForLoadingToFinish, render, userEvent } from 'test-utils';
import store from 'merchant/store';
import BatchListContainer from 'merchant/views/PaymentPages/BatchUpload/List/index';
import FileSaver from 'file-saver';
const saveAsSpy = jest.spyOn(FileSaver, 'saveAs');
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
  beforeAll(() => {
    window.rzpQ = {
      onbr: () => {
        return {
          success: jest.fn(),
        };
      },
    };
  });

  test('should render "Download Sample File" option', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText('Download Sample File')).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
  });

  test('should show error message while parsing udf_schema', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_parsingerrortest',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(
      screen.getByText(/Issue while parsing the udf_schema, please try again later/i),
    ).toBeInTheDocument();
  });

  test('should show error message while fetching payment page details', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_apierrortest',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText(/The requested URL was not found on the server./i)).toBeInTheDocument();
  });

  test('should show error message while downloading sample file', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_apierrortest',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const downloadSampleFileBtn = screen.getByRole('button', { name: 'Download Sample File' });
    expect(downloadSampleFileBtn).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
    await userEvent.click(downloadSampleFileBtn);
    expect(screen.getByText(/Error while generating sample file./i)).toBeInTheDocument();
  });

  test('should able to downloading sample file', async () => {
    saveAsSpy.mockImplementation(() => jest.fn());
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const downloadSampleFileBtn = screen.getByRole('button', { name: 'Download Sample File' });
    expect(downloadSampleFileBtn).toBeInTheDocument();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
    await userEvent.click(downloadSampleFileBtn);
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'sample_pl_LpoFCooJAk0a2j.xlsx');
  });

  test('should able to clear filter', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    const clearBtn = screen.getByRole('button', { name: 'Clear' });
    expect(clearBtn).toBeInTheDocument();
    await userEvent.click(clearBtn);
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
  });

  test('should render upload modal', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, isAllowedView: jest.fn(() => false) },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      ...defaultProps,
      id: 'pl_LpoFCooJAk0a2j',
    };
    jest.setTimeout(30000);
    renderApp(initialState, props, true);
    await waitForLoadingToFinish();
    const uploadBtn = screen.getByRole('button', { name: 'Click here to upload' });
    expect(uploadBtn).toBeInTheDocument();
    await userEvent.click(uploadBtn);
    const uploadCTA = screen.getAllByRole('button', {
      name: /Click here to upload/,
    });
    expect(uploadCTA[0]).toBeInTheDocument();
    await userEvent.click(uploadCTA[0]);
    const startUploadingCTA = screen.getByRole('button', {
      name: /Start Uploading/,
    });
    expect(startUploadingCTA).toBeInTheDocument();
    await userEvent.click(startUploadingCTA);
    expect(screen.getByText('UPLOAD FILE')).toBeInTheDocument();
    expect(screen.getByText('Getting Started with Batch Uploads?')).toBeInTheDocument();
  });
});
