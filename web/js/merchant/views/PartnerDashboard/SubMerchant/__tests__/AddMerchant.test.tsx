import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent, server, delay } from 'common/services/test/test-utils';
import { rest } from 'msw';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import * as api from 'merchant/views/PartnerDashboard/SubMerchant/api';
import {
  referralData as referralDataFixture,
  fileUploadResponse,
  orgDetails,
} from './mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as analytics from 'common/utils/analytics';
import {
  createSubmerchantInviteSuccessHandler,
  fetchReferralsHandler,
} from './mocks/once-handlers';
const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');

// TODO : covered only Capital use case, have to cover others later

const storeData = store.getState();
const isPartner = jest.fn();
const isOrgAllowedFunctionality = jest.fn();
const findTag = jest.fn();

const getStateSpy = jest.spyOn(store, 'getState');
getStateSpy.mockImplementation(() => {
  const clonedStore = cloneDeep(storeData);
  clonedStore.session.user = {
    ...clonedStore.session.user,
    isOrgRZP: true,
    isPartner,
    isOrgAllowedFunctionality,
    isPartnershipForCapitalEnabled: true,
    isPartnershipFUX: true,
    findTag,
  };
  return clonedStore;
});

const state = {
  session: {
    user: {
      id: 'K0KQSNE7BypZ5VE',
      isOrgRZP: true,
      isPartner,
      isOrgAllowedFunctionality,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      findTag: () => false,
      merchant: {
        country_code: 'IN',
      },
    },
  },
};

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
          <a href={sampleUrl}>
            <strong>Download sample file</strong>
          </a>
        </div>
      </div>
    );
  },
}));
const mockCloseModal = jest.fn();

describe('AddMerchant', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
          clicked: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });
  const renderApp = ({
    isPartnershipForCapitalEnabled = true,
    isPartnershipsInviteFlowEnabled = false,
    referralData = referralDataFixture as string | typeof referralDataFixture,
  } = {}) => {
    return render(
      <AddMerchant
        closeModal={mockCloseModal}
        referralData={referralData}
        addType={PRODUCT_TYPE.PG}
        org={orgDetails}
        isConfigTagEnabled={jest.fn()}
      />,
      {
        initialState: {
          session: {
            user: {
              ...state.session.user,
              isPartnershipForCapitalEnabled,
              isPartnershipsInviteFlowEnabled,
            },
          },
        },
      },
    );
  };
  const renderAppWithCapitalSecondStep = () => {
    return render(
      <AddMerchant
        closeModal={mockCloseModal}
        referralData={referralDataFixture}
        addType={PRODUCT_TYPE.CAPITAL}
        org={orgDetails}
        isConfigTagEnabled={jest.fn()}
      />,
      {
        initialState: {
          ...state,
        },
      },
    );
  };

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should show the note for resuming partner onboarding', () => {
    renderApp();
    expect(
      screen.getByText(
        'Note: New Business onboarding is temporarily paused! Your clients can submit their details so that their account can be activated at the earliest when we resume onboarding',
      ),
    ).toBeInTheDocument();
  });

  test('should show different footer text for partnerships invite flow', async () => {
    renderApp({ isPartnershipsInviteFlowEnabled: true, isPartnershipForCapitalEnabled: false });
    const merchantBox = screen.getByText('Razorpay Payments');
    await userEvent.click(merchantBox);
    const nextButton = screen.getByRole('button', { name: 'Next' });
    await userEvent.click(nextButton);
    expect(
      screen.getByText(
        'Razorpay account creation invite link will be sent via email and SMS(if contact number provided) to your affiliate',
      ),
    ).toBeInTheDocument();
  });

  test('should fetch referrals for reseller partner without initial referralData', async () => {
    isPartner.mockImplementation((type) => type === 'reseller');
    const state = { isApiCalled: false };
    server.use(fetchReferralsHandler(state));
    renderApp({
      referralData: '',
    });
    // No loader present for this api call
    await delay(1000);
    expect(state.isApiCalled).toBe(true);
    isPartner.mockReset();
  });

  test('should not fetch referrals for platform partner without initial referralData', async () => {
    isPartner.mockImplementation((type) => type === 'pure_platform');
    const state = { isApiCalled: false };
    server.use(fetchReferralsHandler(state));

    renderApp();
    // No loader present for this api call
    await delay(1000);
    expect(state.isApiCalled).toBe(false);
    isPartner.mockReset();
  });

  test('should send create invite call for partnerships invite flow', async () => {
    jest.setTimeout(10000);
    const createSubmerchantInviteSpy = jest.spyOn(api, 'createSubmerchantInvite');
    isPartner.mockImplementation((type) => type === 'reseller');
    server.use(createSubmerchantInviteSuccessHandler());

    renderApp({ isPartnershipsInviteFlowEnabled: true, isPartnershipForCapitalEnabled: false });
    const merchantBox = screen.getByText('Razorpay Payments');
    await userEvent.click(merchantBox);
    const nextButton = screen.getByRole('button', { name: 'Next' });
    await userEvent.click(nextButton);

    const name = 'Test Name';
    const email = 'test@email.com';
    const contact_no = '9123123123';
    await userEvent.type(screen.getByTestId('input-name'), name);
    await userEvent.type(screen.getByTestId('input-email'), email);
    await userEvent.type(screen.getByTestId('input-contact'), contact_no);

    const sendButton = screen.getByRole('button', { name: 'Send Invite' });
    await userEvent.click(sendButton);

    expect(createSubmerchantInviteSpy).toHaveBeenCalledWith({
      name,
      email,
      contact_no,
      product: 'primary',
      partner_id: 'K0KQSNE7BypZ5VE',
    });
    // Check that we moved to next step
    await waitFor(() => {
      expect(screen.queryByText('Inviting...')).not.toBeInTheDocument();
    });
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Submerchant Refer Via Email',
      }),
    );

    expect(screen.getByText('Merchant Added Successfully')).toBeInTheDocument();
    isPartner.mockReset();
  });

  test('should return separate sample batch file for partnerships invite flow', async () => {
    renderApp({ isPartnershipsInviteFlowEnabled: true, isPartnershipForCapitalEnabled: false });

    const merchantBox = screen.getByText('Razorpay Payments');
    await userEvent.click(merchantBox);
    const nextButton = screen.getByRole('button', { name: 'Next' });
    await userEvent.click(nextButton);
    await userEvent.click(screen.getByText('Invite Multiple Clients'));

    const sampleLink = screen.getByRole('link', { name: 'Download sample file' });
    expect(sampleLink).toHaveAttribute('href', '/files/sample_invite_submerchant_batch.xlsx');
  });

  test('should render correctly Corporate Card option with props', () => {
    renderApp();
    expect(screen.getByText('Line Of Credit')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like Line Of Credit'),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Next' })).toBeInTheDocument();
  });

  test('should not show Corporate card option if experiment is false', () => {
    renderApp({ isPartnershipForCapitalEnabled: false });

    const heading = screen.queryByText('Line Of Credit');
    expect(heading).not.toBeInTheDocument();
  });

  test('should navigate to next screen when merchant type is selected and next button is clicked', async () => {
    renderApp();
    const merchantBox = screen.getByText('Line Of Credit');
    await userEvent.click(merchantBox);

    const nextButton = screen.getByRole('button', { name: 'Next' });
    await userEvent.click(nextButton);

    await waitFor(() => {
      expect(screen.getByText('Add New Merchants - Line Of Credit')).toBeInTheDocument();
    });

    expect(screen.getByText('Invite Multiple Clients')).toBeInTheDocument();
    expect(screen.getByText('Invite using Links')).toBeInTheDocument();
    expect(screen.queryByText('Invite using Email')).not.toBeInTheDocument();
  });

  test('should navigate to next screen when addMerchantType is passed', () => {
    renderAppWithCapitalSecondStep();
    expect(screen.getByText('Invite Multiple Clients')).toBeInTheDocument();
    expect(screen.getByText('Invite using Links')).toBeInTheDocument();
    expect(screen.queryByText('Invite using Email')).not.toBeInTheDocument();
  });

  test('should close the modal when close button is clicked', async () => {
    renderAppWithCapitalSecondStep();
    const closeButton = screen.getByTestId('modal-header-close-btn');
    expect(closeButton).toBeInTheDocument();

    await userEvent.click(closeButton);
    await waitFor(() => {
      expect(mockCloseModal).toBeCalled();
    });
  });

  test('should copy the link', async () => {
    renderAppWithCapitalSecondStep();

    const inviteLink = screen.getByText('Invite using Links');
    await userEvent.click(inviteLink);

    await waitFor(() => {
      expect(
        screen.getByText('You can also copy and share the link via other mediums'),
      ).toBeInTheDocument();
    });
    const copyButton = screen.getByText('Copy Link');
    expect(copyButton).toBeInTheDocument();
    await userEvent.click(copyButton);
  });

  test('should upload submerchants successfully after uploading file and clicking on invite', async () => {
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
    renderAppWithCapitalSecondStep();
    const uploadButton = screen.getByTestId('upload-input');
    const str = JSON.stringify([{ name: 'razorpay' }]);
    const blob = new Blob([str]);
    const file = new File([blob], 'hello.xlsx', { type: 'image/csv' });
    expect(uploadButton).toBeInTheDocument();
    await userEvent.upload(uploadButton, file);

    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    const inviteButton = screen.getByRole('button', { name: 'Invite 2 contacts' });
    expect(inviteButton).toBeInTheDocument();
    await userEvent.click(inviteButton);

    await waitFor(() => {
      expect(
        screen.getByText(
          'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
        ),
      ).toBeInTheDocument();
    });
  });

  test('should show error notification if invite API fails after uploading file and clicking on invite', async () => {
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
    renderAppWithCapitalSecondStep();
    const uploadButton = screen.getByTestId('upload-input');
    const str = JSON.stringify([{ name: 'razorpay' }]);
    const blob = new Blob([str]);
    const file = new File([blob], 'hello.xlsx', { type: 'image/csv' });
    expect(uploadButton).toBeInTheDocument();
    await userEvent.upload(uploadButton, file);

    await waitFor(() => {
      expect(screen.getByText('2 contacts have been identified.')).toBeInTheDocument();
    });
    const inviteButton = screen.getByRole('button', { name: 'Invite 2 contacts' });
    expect(inviteButton).toBeInTheDocument();
    await userEvent.click(inviteButton);

    await waitFor(() => {
      expect(screen.getByText('Failed to invite.')).toBeInTheDocument();
    });
  });
});
