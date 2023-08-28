import React from 'react';
import { rest } from 'msw';

import { getInitialUserOrgState } from 'common/tests/utils';
import { submerchantWithKYCAccess } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import SingleAddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleAddMerchant';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, userEvent, waitFor, server } from 'test-utils';
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
// import * as submerchantsActions from 'merchant/reducers/submerchant';
// const createSubmerchantAction = jest.spyOn(submerchantsActions, 'create');
const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  setShowHeaderAndTabs: jest.fn(),
  onInviteTabsBackClick: jest.fn(),
  onDismiss: jest.fn(),
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
  isPartnershipsInviteFlowEnabled: true,
};
const defaultOrgExtra = {};
describe('SingleAddMerchant', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<SingleAddMerchant {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
  beforeEach(() => {});

  const fillFormEssentials = async () => {
    const name = 'Test Name';
    const email = 'test@email.com';
    await userEvent.type(screen.getByLabelText('Client Name'), name);
    await userEvent.type(screen.getByLabelText('Email ID'), email);
    return { name, email };
  };

  const useCreateSuccessHandler = () => {
    server.use(
      rest.post('*/submerchants', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: submerchantWithKYCAccess,
          }),
          ctx.delay(50),
        );
      }),
    );
  };
  const useCreateErrorHandler = () => {
    server.use(
      rest.post('*/submerchants', (req, res, ctx) => {
        return res(
          ctx.status(400),
          ctx.json({
            status_code: 400,
            success: false,
            data: ['something went wrong!'],
          }),
          ctx.delay(50),
        );
      }),
    );
  };
  test('should correctly render typical add merchant flow', async () => {
    useCreateSuccessHandler();
    renderApp();

    const { email } = await fillFormEssentials();
    const contact_no = '9123123123';
    await userEvent.type(screen.getByLabelText('Contact number (optional)'), contact_no);
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    // Check that we moved to next step
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(defaultProps.onAddSuccess).toHaveBeenCalled();
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    // success screen contents
    expect(
      screen.getByText(/Razorpay account access link will be sent to your affiliate/),
    ).toBeInTheDocument();
    expect(screen.getByText(email)).toBeInTheDocument();
    expect(screen.getByText(`+91-${contact_no}`)).toBeInTheDocument();
  });

  test('should show success notification for non-reseller partners on submitting', async () => {
    useCreateSuccessHandler();
    isPartner.mockImplementation((type) => type !== 'reseller');
    renderApp();

    await fillFormEssentials();
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    // Check that we moved to next step
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(defaultProps.onAddSuccess).toHaveBeenCalled();
    expect(defaultProps.onDismiss).toHaveBeenCalled();

    // success notification contents
    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'success',
      message: 'Submerchant created successfully',
    });
  });

  test('should show notification on api error in sending an invite', async () => {
    const message = 'Something went wrong';
    useCreateErrorHandler();
    renderApp();
    await fillFormEssentials();
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({ message, type: 'error' });
  });
});
