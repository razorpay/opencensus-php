import React, { useState } from 'react';
import { rest } from 'msw';

import { getInitialUserOrgState } from 'common/tests/utils';
import { fileUploadResponse } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import BulkInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab';
import * as analytics from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import * as kycAccessFtux from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, userEvent, server, waitFor } from 'test-utils';

const getHasSelectedKycAccessSpy = jest.spyOn(kycAccessFtux, 'getHasSelectedKycAccess');
const setHasSelectedKycAccessSpy = jest.spyOn(kycAccessFtux, 'setHasSelectedKycAccess');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const trackInviteFlowValidationErrorSpy = jest.spyOn(analytics, 'trackInviteFlowValidationError');
const DummyBatchValidate = ({
  validateBatch,
  onValidation,
  onValidationFail,
  clickToUploadAnalytics = () => {},
  sampleUrl,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [validationError, setValidationError] = useState('');
  return (
    <div>
      <input
        type="file"
        data-testid="upload-input"
        onChange={() => {
          setIsLoading(true);
          validateBatch()
            .then((response) => {
              setIsLoading(false);
              onValidation(response.data, 'uploadFile');
              return response.data;
            })
            .catch((error) => {
              setIsLoading(false);
              const errorMsg = error.errors[0] ?? '';
              setValidationError(errorMsg);
              if (onValidationFail) onValidationFail(errorMsg);
              return error;
            });
          clickToUploadAnalytics();
        }}
      />
      <div>
        {/* from instructions */}
        <a href={sampleUrl}>
          <strong>sample file</strong>
        </a>
        {/* Mimic validation errors */}
        <div>{isLoading ? 'Dummy Loading' : validationError}</div>
      </div>
    </div>
  );
};

jest.mock('merchant/containers/BatchNew/Validate', () => ({
  __esModule: true,
  default: DummyBatchValidate,
}));

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  onInviteTabsBackClick: jest.fn(),
  setShowHeaderAndTabs: jest.fn(),
  onDismiss: jest.fn(),
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('BulkInviteTab', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    render(<BulkInviteTab {...defaultProps} {...props} />, { initialState: { session } });
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
    await waitFor(() => {
      expect(screen.queryByText('Dummy Loading')).not.toBeInTheDocument();
    });
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
  const useValidateBatchErrorHandler = () => {
    server.use(
      rest.post('*/merchant/api/test/batches/validate', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 400,
            success: false,
            errors: ['bulk validation error', 'Status Code: 400'],
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

  test('should fire analytics on file validation error', async () => {
    useValidateBatchErrorHandler();
    renderApp();
    await userUploadFile();
    expect(screen.getByText('bulk validation error')).toBeInTheDocument();
    expect(trackInviteFlowValidationErrorSpy).toHaveBeenCalledWith({
      errorMessage: 'bulk validation error',
      fieldEdited: 'file',
      inviteFlow: 'BULK_UPLOAD',
      productType: 'primary',
    });
  });

  test('should invite submerchants successfully after uploading file and clicking on send invites', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);
    useValidateBatchSuccessHandler();
    useCreateBatchSuccessHandler();
    renderApp();
    await userUploadFile();
    expect(screen.getByText('I want to assist my client with their KYC')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: 'Send Invites' }));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Invites successfully sent',
      });
    });
  });

  test('should show error notification if invite API fails after uploading file and clicking on invite', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);

    useValidateBatchSuccessHandler();
    useCreateBatchErrorHandler();
    renderApp();

    await userUploadFile();
    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    await userEvent.click(screen.getByRole('button', { name: 'Send Invites' }));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Failed to invite.',
      });
    });
  });

  test('should show ftux opt-in success screen on selecting yes in kyc access', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    useValidateBatchSuccessHandler();
    useCreateBatchSuccessHandler();
    renderApp();
    expect(screen.queryByText('I want to assist my client with their KYC')).not.toBeInTheDocument();
    await userUploadFile();

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invites' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(true);
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    expect(screen.getByText('Invite successfully sent')).toBeInTheDocument();
    expect(screen.getByText('What next?')).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Got it' }));
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show ftux opt-out form on success screen on selecting no in kyc access', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    useValidateBatchSuccessHandler();
    useCreateBatchSuccessHandler();
    renderApp();
    await userUploadFile();

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invites' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(false);
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    expect(screen.getByText('Before you finish, we have something to ask')).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Submit & Close' }));
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test.todo('Analytics events for other product types');
});
