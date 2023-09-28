import BatchList, {
  STATUS_VALUES,
} from 'merchant/views/Transactions/v1/BatchRefunds/components/BatchList';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import {
  mock_refund_items,
  batchListInitProps,
} from 'merchant/views/Transactions/v1/BatchRefunds/__test__/mock';
import * as selfServeAnalytics from 'common/utils/selfServeAnalytics';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { batchDownload } from 'merchant/reducers/batches';

jest.mock('merchant/views/Transactions/v1/BatchRefunds/HeaderActions', () => ({ children }) => (
  <div>{children}</div>
));
jest.mock('merchant/views/Transactions/v1/BatchRefunds/components/BatchListFilter', () => () => (
  <div>BatchListFilter Component</div>
));
jest.mock('merchant/reducers/batches', () => ({
  ...jest.requireActual('merchant/reducers/batches'),
  batchDownload: jest.fn(),
}));
const initProps = {
  ...batchListInitProps,
  gaEvents: {
    trackSampleFileDownload: jest.fn(),
  },
};

const App = ({ ...props }) => {
  return (
    <Provider
      store={storeWithInitialState({
        app: {
          isMobileResolution: true,
        },
        session: {
          mode: 'live',
          org: { id: '123' },
          user: {
            isOrgAllowedFunctionality: () => true,
          },
        },
      })}
    >
      <BatchList {...initProps} {...props} />
    </Provider>
  );
};

describe('BatchRefunds - BatchList Component', () => {
  const openModal = jest.spyOn(ModalActions, 'openModal');
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');
  const selfServeTrackInitiate = jest.spyOn(selfServeAnalytics, 'selfServeTrackInitiate');

  window.open = jest.fn().mockReturnValue({
    close: jest.fn(),
    location: {},
  });
  const windowRef = window.open();

  afterEach(() => {
    selfServeTrackInitiate.mockClear();
    openModal.mockClear();
    showNotification.mockClear();
  });

  test('should render BatchList component', () => {
    render(<App />, {
      renderViaRouteGuard: false,
    });
    expect(screen.getByTestId('batchrefunds-batchlist')).toBeInTheDocument();
    // asserting presence of BatchListFilter
    expect(screen.getByText(/BatchListFilter Component/i)).toBeInTheDocument();
    expect(
      screen.getByRole('link', {
        name: /Download Sample File/i,
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('link', {
        name: /Documentation/i,
      }),
    ).toBeInTheDocument();
  });

  test('should call gaEvents trackSampleFileDownload on Download File Click', () => {
    render(<App />);
    const downloadSampleFileCTA = screen.getByRole('link', {
      name: /Download Sample File/i,
    });
    expect(downloadSampleFileCTA).toBeInTheDocument();
    fireEvent.click(downloadSampleFileCTA);
    expect(initProps.gaEvents.trackSampleFileDownload).toHaveBeenCalledWith('From List View');
  });

  test('should open batch upload modal on Click here to upload CTA', () => {
    render(<App />);
    const clickToUploadCTA = screen.getByRole('button', {
      name: /Click here to upload/i,
    });
    expect(clickToUploadCTA).toBeInTheDocument();
    fireEvent.click(clickToUploadCTA);
    expect(selfServeTrackInitiate).toHaveBeenCalledWith({
      selfServeAction: 'Batch refund File Uploaded',
      page: 'Batch Refunds',
      screen: 'Transactions',
    });
    expect(openModal).toHaveBeenCalled();
  });

  describe('Batch actions', () => {
    test('should render download buttons', () => {
      render(<App />, { showModal: true });
      const downloadBtns = screen.getAllByRole('button', {
        name: 'Download',
      });
      expect(downloadBtns.length).toBe(mock_refund_items.length);
    });

    describe('should call batchDownload on download CTA click', () => {
      test('should make window location href to download the the file when batch download is success', async () => {
        const mockSuccessResponse = {
          data: {
            url: 'https://test-download-url.com',
          },
        };
        batchDownload.mockReturnValue({
          type: 'BATCH_DOWNLOAD',
          payload: Promise.resolve(mockSuccessResponse),
        });
        render(<App />);
        const downloadBtn = screen.getAllByRole('button', {
          name: 'Download',
        })[0];
        fireEvent.click(downloadBtn);
        await waitFor(() => {
          expect(windowRef.location.href).toBe(mockSuccessResponse.data.url);
        });
      });
      test('should show error notification when batch download is a failure', async () => {
        const mockFailureResponse = {
          errors: ['some error'],
        };
        batchDownload.mockReturnValue({
          type: 'BATCH_DOWNLOAD',
          payload: Promise.reject(mockFailureResponse),
        });
        render(<App />);
        const downloadBtn = screen.getAllByRole('button', {
          name: 'Download',
        })[0];
        fireEvent.click(downloadBtn);
        await waitFor(() => {
          expect(windowRef.close).toHaveBeenCalled();
          expect(showNotification).toHaveBeenCalledWith({
            type: 'error',
            message: mockFailureResponse.errors,
          });
        });
      });
    });
  });
  test('should render cancel button if status is in STATUS_VALUES and call openModal on click', async () => {
    render(<App />);
    const cancelBtns = screen.getAllByRole('button', {
      name: /Cancel/i,
    });
    expect(cancelBtns.length).toBe(
      mock_refund_items.filter((item) => STATUS_VALUES.includes(item.status)).length,
    );
    fireEvent.click(cancelBtns[0]);
    await waitFor(() => {
      // openModal should be called to render ConnectedCancelConfirmation
      expect(openModal).toHaveBeenCalled();
    });
  });

  test('should render view all links CTA and call viewAll on click', async () => {
    const mockViewAll = jest.fn();
    render(<App viewAll={mockViewAll} issueAll={jest.fn()} issuableIdList="true" />);
    const viewAllLinksBtn = screen.getByRole('button', {
      name: /view all links/i,
    });
    expect(viewAllLinksBtn).toBeInTheDocument();
    fireEvent.click(viewAllLinksBtn);
    await waitFor(() => {
      expect(mockViewAll).toHaveBeenCalled();
    });
  });

  test('should render Issue all links CTA and call issueAll on click', async () => {
    const mockIssueAll = jest.fn();
    render(<App showBatchName={true} issueAll={mockIssueAll} />);
    const issueAllLinksBtn = screen.getByRole('button', {
      name: /Issue all links/i,
    });
    expect(issueAllLinksBtn).toBeInTheDocument();
    fireEvent.click(issueAllLinksBtn);
    await waitFor(() => {
      expect(mockIssueAll).toHaveBeenCalled();
    });
  });
});
