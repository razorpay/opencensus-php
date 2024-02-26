import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import BulkOAuthInvite from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkOAuthInvite';
import { MockBatchValidate } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/__tests__/mocks/fixtures';
import {
  useCreateBatchSuccessHandler,
  useCreateBatchErrorHandler,
  useValidateBatchSuccessHandler,
  useValidateBatchErrorHandler,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/__tests__/mocks/once-handlers';
import * as analytics from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, userEvent, waitFor } from 'test-utils';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const trackInviteFlowValidationErrorSpy = jest.spyOn(analytics, 'trackInviteFlowValidationError');

jest.mock('merchant/containers/BatchNew/Validate', () => ({
  __esModule: true,
  default: (props) => <MockBatchValidate {...props} />,
}));

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  goToAppSelectionStep: jest.fn(),
  setShowHeaderAndTabs: jest.fn(),
  onDismiss: jest.fn(),
  selectedApp: {
    application_id: 'string_application_id',
    client_id: 'string_client_id',
    redirect_uri: 'string_redirect_uri',
    name: 'string_name',
  },
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('BulkOAuthInvite', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    render(<BulkOAuthInvite {...defaultProps} {...props} />, { initialState: { session } });
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

  test('should show success screen after uploading file and clicking on send invites', async () => {
    useValidateBatchSuccessHandler();
    useCreateBatchSuccessHandler();
    renderApp();
    await userUploadFile();

    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });

    // Check change button
    await userEvent.click(screen.getByText('Change'));
    expect(defaultProps.goToAppSelectionStep).toHaveBeenCalled();

    await userEvent.click(screen.getByRole('button', { name: 'Send Invites' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);
    expect(screen.getByText('Invite successfully sent')).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Got it' }));
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show error notification if invite API fails after uploading file and clicking on invite', async () => {
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
});
