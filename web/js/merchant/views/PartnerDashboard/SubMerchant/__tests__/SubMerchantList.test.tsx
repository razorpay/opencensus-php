import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'common/services/test/test-utils';
import SubMerchantList from 'merchant/views/PartnerDashboard/SubMerchant/List';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

// TODO : covered only Capital use case, have to cover others later

const isPartner = (x) => x !== 'pure_platform';
const isPartnerIntent = jest.fn();
const isFeatureEnabled = jest.fn();
const instantActivation = { isWhitelistFlow: false };
const state = {
  session: {
    user: {
      userRole: 'owner',
      isAuthenticated: true,
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      findTag: () => false,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
    },
  },
};

const renderOptions = {
  initialEntries: [{ pathname: '/partners/submerchants', state: undefined }],
  path: '/partners/submerchants',
};

jest.mock('merchant/views/PartnerDashboard/SubMerchant/AddMerchant', () => ({
  __esModule: true,
  default: ({ closeModal, addType }) => {
    return (
      <div>
        <button onClick={closeModal}>close</button>
        {addType === 'capital' ? (
          <div>Add New Merchants - Line Of Credit</div>
        ) : (
          <div>Add New Merchants - RazorpayX</div>
        )}
      </div>
    );
  },
}));

jest.mock('merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink', () => ({
  __esModule: true,
  default: ({ closeModal }) => {
    return (
      <div>
        <button onClick={closeModal}>close</button>
        <div>Share Referral Link</div>
        <button>Copy Link refer</button>
      </div>
    );
  },
}));

describe('List', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      component: () => {},
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });

  const renderApp = (renderOptions) => {
    return render(<SubMerchantList />, {
      showModal: true,
      initialState: {
        ...state,
      },
      ...renderOptions,
    });
  };

  test('should render component with default props', () => {
    renderApp(renderOptions);

    expect(screen.getByText('Payments')).toBeInTheDocument();
    expect(screen.getByText('Line Of Credit')).toBeInTheDocument();
  });

  test('should render Add merchant modal after clicking Add button', async () => {
    renderApp(renderOptions);

    const addButton = screen.getByRole('button', { name: /Add New Clients/i });
    expect(addButton).toBeInTheDocument();
    await userEvent.click(addButton);

    await waitFor(() => {
      expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
    });
  });

  test('should render Refer merchant modal after clicking Refer button', async () => {
    renderApp(renderOptions);

    const referButton = screen.getByRole('button', { name: /Share Referral Link/i });
    expect(referButton).toBeInTheDocument();
    await userEvent.click(referButton);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Copy Link refer/i })).toBeInTheDocument();
    });
  });

  test('should show modal when addType is passed is history', async () => {
    const capitalRenderOptions = {
      ...renderOptions,
      initialEntries: [
        { pathname: '/partners/submerchants/capital', state: { addType: PRODUCT_TYPE.CAPITAL } },
      ],
    };

    renderApp(capitalRenderOptions);

    await waitFor(() => {
      expect(screen.getByText('Add New Merchants - Line Of Credit')).toBeInTheDocument();
    });
  });
});
