import React from 'react';

import { useSplitzService } from 'common/splitz';
import { initialState as instrumentRequestInitialState } from 'merchant/reducers/instrumentRequests';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import International from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International';
import {
  getProductStatusHandler,
  fetchInternationalWorkflowStatus,
  fetchB2BFeatureStatus,
  fetchUserFeatures,
  fetchUser,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/__tests__/mocks/handlers';
import { isInternationalLeafItemDisabled } from 'merchant/views/AccountAndSettings/PaymentMethods/utils';
import { errorHandlers, render, screen, server, waitFor } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards',
  () => ({
    __esModule: true,
    default: ({ onQuestionnaireSubmitSuccess, productStatus }) => (
      <div data-testid="international-cards">
        <button onClick={onQuestionnaireSubmitSuccess}>Trigger questionnaire success</button>
        <p>Payment Gateway: {productStatus?.payment_gateway}</p>
        <p>PPLI: {productStatus?.invoices}</p>
      </div>
    ),
  }),
);

jest.mock('merchant/views/Settings/PaymentMethods/components/LocalWireTransfer', () => ({
  __esModule: true,
  default: ({ leafList }) => (
    <div data-testid="local-wire-transfer">
      <p>Local Wire Transfer: {leafList.name}</p>
      <p>Local Wire Transfer: {leafList.slug}</p>
    </div>
  ),
}));

jest.mock('merchant/views/Settings/PaymentMethods/components/InstantBankTransfer', () => ({
  __esModule: true,
  default: ({ leafList }) => (
    <div data-testid="instant-wire-transfer">
      <p>Instant Wire Transfer: {leafList.name}</p>
      <p>Instant Wire Transfer: {leafList.slug}</p>
    </div>
  ),
}));

jest.mock(
  'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SwiftBankTransfer',
  () => ({
    __esModule: true,
    default: ({ leafList }) => (
      <div data-testid="swift-bank-transfer">
        <p>Swift Bank Transfer: {leafList.name}</p>
        <p>Swift Bank Transfer: {leafList.slug}</p>
      </div>
    ),
  }),
);

jest.mock('merchant/views/Settings/PaymentMethods/components/Paypal', () => ({
  __esModule: true,
  default: ({ instrument }) => (
    <div data-testid="paypal">
      <p>Paypal name: {instrument.name}</p>
      <p>Paypal slug: {instrument.slug}</p>
    </div>
  ),
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/components/Section', () => ({
  __esModule: true,
  default: ({ type, showLoader, children }) =>
    showLoader ? (
      <div data-testid="loader" />
    ) : (
      <>
        <div>Payment method: {type}</div>
        {children}
      </>
    ),
}));

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: {
      showIntlMethodEnablement: {
        variables: {
          result: 'off',
        },
      },
    },
  })),
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/utils', () => ({
  isInternationalLeafItemDisabled: jest.fn(),
}));

const renderApp = () =>
  render(<International />, {
    initialState: {
      instrumentRequests: {
        leafInstrument: instrumentRequestInitialState.pg[6],
      },
      session: {
        user: {
          international: true,
          id: 'test',
        },
      },
      unlockIntlPaymentMethods: {
        showMorePaymentMethodsSection: true,
      },
    },
  });

describe('International', () => {
  beforeEach(() => {
    server.use(
      getProductStatusHandler(),
      fetchInternationalWorkflowStatus(),
      fetchB2BFeatureStatus(),
      fetchUser(),
      fetchUserFeatures(),
    );
    jest.clearAllMocks();
  });

  test('should render all sections except local and instant wire transfer', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });
    expect(
      screen.getByText(`Payment method: ${PaymentMethodsFields.INTERNATIONAL}`),
    ).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByTestId('international-cards')).toBeInTheDocument();
    });

    // combine all leafList items
    const totalLeafList =
      instrumentRequestInitialState.pg[6].leafList.length +
      instrumentRequestInitialState.pg[6].leafList.find((item) => item.leafList).leafList.length -
      1;

    expect(screen.getAllByTestId('leaf-list-item')).toHaveLength(totalLeafList);

    expect(screen.getByTestId('paypal')).toBeInTheDocument();
    expect(screen.getByText(/Paypal name: Paypal/i)).toBeInTheDocument();
    expect(screen.getByText(/Paypal slug: paypal/i)).toBeInTheDocument();
    expect(screen.getByTestId('instant-wire-transfer')).toBeInTheDocument();
    expect(screen.getByTestId('swift-bank-transfer')).toBeInTheDocument();
    expect(screen.getByTestId('local-wire-transfer')).toBeInTheDocument();
    expect(screen.getByTestId('firc-banner')).toBeInTheDocument();
  });

  test('should not render any section isInternationalLeafItemDisabled for all sections', async () => {
    // eslint-disable-next-line
    // @ts-ignore
    isInternationalLeafItemDisabled.mockReturnValue(true);
    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });
    expect(screen.queryAllByTestId('leaf-list-item')).toHaveLength(0);
  });

  test('should show error notification if product status api fails', async () => {
    server.use(errorHandlers.internalServerError);
    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Failed to retrieve International Cards Info')).toBeInTheDocument();
  });

  test('Should call isInternationalLeafItemDisabled with correct params when exp is disabled', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });
    expect(isInternationalLeafItemDisabled).toHaveBeenCalledWith(
      expect.objectContaining({
        showMoreInternationalMethods: false,
      }),
    );
  });

  test('Should call isInternationalLeafItemDisabled with correct params when exp is enabled', async () => {
    // eslint-disable-next-line
    // @ts-ignore
    useSplitzService.mockImplementation(() => ({
      abExperiments: {
        showIntlMethodEnablement: {
          variables: {
            result: 'on',
          },
        },
      },
    }));

    renderApp();
    await waitFor(() => {
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });
    expect(isInternationalLeafItemDisabled).toHaveBeenCalledWith(
      expect.objectContaining({
        showMoreInternationalMethods: true,
      }),
    );
  });
});
