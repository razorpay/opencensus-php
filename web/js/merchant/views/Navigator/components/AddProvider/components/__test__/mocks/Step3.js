import { Step3 } from 'merchant/views/Navigator/components/AddProvider/components/Step3';
import { SUPPORTED_GATEWAYS } from './constants';

export const PAYU_PROVIDER = {
  isEdit: true,
  selectedProvider: 'payu',
  providers: SUPPORTED_GATEWAYS,
  provider: {
    Provider_name: 'payu test 1',
    Description: 'test',
    Gateway: 'payu',
    Gateway_details: {
      'Payment Methods': [],
    },
  },
  validationErrors: {},
  changeGatewayDetails: jest.fn(),
  changeGatewayWallets: jest.fn(),
  changeEnableAutoDebitSwitch: jest.fn(),
  user: {
    isPaytmAutoDebitEnabled: true,
  },
};

export const PAYTM_PROVIDER = {
  isEdit: true,
  selectedProvider: 'paytm',
  providers: SUPPORTED_GATEWAYS,
  provider: {
    Provider_name: 'paytm test 1',
    Description: 'test',
    Gateway: 'paytm',
    Gateway_details: {
      'Payment Methods': ['wallet'],
      wallet_metadata: {
        wallets: ['paytm'],
      },
      ENABLE_AUTO_DEBIT: false,
    },
  },
  validationErrors: {},
  changeGatewayDetails: jest.fn(),
  changeGatewayWallets: jest.fn(),
  changeEnableAutoDebitSwitch: jest.fn(),
  user: {
    isPaytmAutoDebitEnabled: true,
  },
};

export const NETBANKING_AXIS_PROVIDER = {
  isEdit: true,
  selectedProvider: 'netbanking_axis',
  providers: SUPPORTED_GATEWAYS,
  provider: {
    Provider_name: 'netbanking axis test 1',
    Description: 'test',
    Gateway: 'netbanking_axis',
    Gateway_details: {
      'Payment Methods': [],
      TPV: 0,
    },
  },
  validationErrors: {},
  changeGatewayDetails: jest.fn(),
  changeGatewayWallets: jest.fn(),
};

export const App = (props) => {
  return <Step3 {...props} />;
};
