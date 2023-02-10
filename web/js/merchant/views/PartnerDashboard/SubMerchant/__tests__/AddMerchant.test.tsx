import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent, server } from 'common/services/test/test-utils';
import { rest } from 'msw';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import { referralData, fileUploadResponse } from './mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

// TODO : covered only Capital use case, have to cover others later

const storeData = store.getState();
const isPartner = jest.fn();
const isOrgAllowedFunctionality = jest.fn();

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
  };
  return clonedStore;
});

const state = {
  session: {
    user: {
      isOrgRZP: true,
      isPartner,
      isOrgAllowedFunctionality,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
    },
  },
};

jest.mock('merchant/containers/BatchNew/Validate', () => ({
  __esModule: true,
  default: ({ validateBatch, onValidation, clickToUploadAnalytics }) => {
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
  const renderApp = ({ isPartnershipForCapitalEnabled = true } = {}) => {
    return render(<AddMerchant closeModal={mockCloseModal} referralData={referralData} />, {
      initialState: {
        session: {
          user: {
            ...state.session.user,
            isPartnershipForCapitalEnabled,
          },
        },
      },
    });
  };
  const renderAppWithCapitalSecondStep = () => {
    return render(
      <AddMerchant
        closeModal={mockCloseModal}
        referralData={referralData}
        addType={PRODUCT_TYPE.CAPITAL}
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

  test('should render correctly Corporate Card option with props', () => {
    renderApp();

    expect(screen.getByText('Corporate Credit Card')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like Corporate Cards'),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Next' })).toBeInTheDocument();
  });

  test('should not show Corporate card option if experiment is false', () => {
    renderApp({ isPartnershipForCapitalEnabled: false });

    const heading = screen.queryByText('Corporate Credit Card');
    expect(heading).not.toBeInTheDocument();
  });

  test('should navigate to next screen when merchant type is selected and next button is clicked', async () => {
    renderApp();
    const merchantBox = screen.getByText('Corporate Credit Card');
    await userEvent.click(merchantBox);

    const nextButton = screen.getByRole('button', { name: 'Next' });
    await userEvent.click(nextButton);

    await waitFor(() => {
      expect(screen.getByText('Add New Merchants - Corporate Card')).toBeInTheDocument();
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
