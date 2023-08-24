import React from 'react';
import { render, screen, userEvent, server, waitFor } from 'test-utils';
import BulkAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkAddMerchant';
import { getInitialUserOrgState } from 'common/tests/utils';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { rest } from 'msw';

import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fileUploadResponse } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

jest.mock('merchant/containers/BatchNew/Validate', () => ({
  __esModule: true,
  default: ({ validateBatch, onValidation, clickToUploadAnalytics, sampleUrl }) => {
    return (
      <div>
        <input
          type="file"
          data-testid="upload-input"
          onChange={() => {
            validateBatch();
            onValidation({ file_id: 'files1234', processable_count: 2 }, 'uploadFile');
            clickToUploadAnalytics();
          }}
        />
        <div>
          {/* from top link */}
          <a href={sampleUrl}>
            <strong>Download sample file</strong>
          </a>
        </div>
      </div>
    );
  },
}));

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  onInviteTabsBackClick: jest.fn(),
  onDismiss: jest.fn(),
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('BulkAddMerchant', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    render(<BulkAddMerchant {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
  beforeEach(() => {});

  const userUploadFile = async () => {
    const uploadButton = screen.getByTestId('upload-input');
    const str = JSON.stringify([{ name: 'razorpay' }]);
    const blob = new Blob([str]);
    const file = new File([blob], 'hello.xlsx', { type: 'image/csv' });
    expect(uploadButton).toBeInTheDocument();
    await userEvent.upload(uploadButton, file);
  };

  const useValidateBatchSuccessHandler = () => {
    server.use(
      rest.post('*/merchant/api/test/batches/validate', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: fileUploadResponse,
          }),
          ctx.delay(50),
        );
      }),
    );
  };

  const useCreateBatchSuccessHandler = () => {
    server.use(
      rest.post('*/merchant/api/test/batches', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: { status: 'success' },
          }),
          ctx.delay(50),
        );
      }),
    );
  };

  const useCreateBatchErrorHandler = () => {
    server.use(
      rest.post('*/merchant/api/test/batches', (req, res, ctx) => {
        return res(
          ctx.status(400),
          ctx.json({
            status_code: 400,
            success: true,
            data: ['error'],
          }),
          ctx.delay(50),
        );
      }),
    );
  };
  test('should upload submerchants successfully after uploading file and clicking on invite', async () => {
    useValidateBatchSuccessHandler();
    useCreateBatchSuccessHandler();
    renderApp();
    await userUploadFile();

    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: 'Invite 2 contacts' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'success',
      message:
        'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
    });
  });

  test('should show error notification if invite API fails after uploading file and clicking on invite', async () => {
    useValidateBatchSuccessHandler();
    useCreateBatchErrorHandler();
    renderApp();
    await userUploadFile();
    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: 'Invite 2 contacts' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'error',
      message: 'Failed to invite.',
    });
  });

  test.todo('Analytics events for other product types');
});
