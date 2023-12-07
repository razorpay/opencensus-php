import {
  createMappedProviders,
  filterProvidersByExperiment,
  findProviderName,
} from 'merchant/views/Navigator/components/util';
import { TERMINAL_PROVIDERS } from 'merchant/views/Navigator/tests/data/mockData';

test('Map providers for dropdown', () => {
  const MAPPED_PROVIDERS = createMappedProviders(TERMINAL_PROVIDERS);

  expect(MAPPED_PROVIDERS).toStrictEqual([
    {
      id: 'paytm_IPRZ2Vu2SsoN31',
      name: 'paytm',
      value: 'paytm_IPRZ2Vu2SsoN31',
    },
    {
      id: 'upi_mindgate_ItToeGDgUPERxi',
      name: 'HDFC upi mindgate',
      value: 'upi_mindgate_ItToeGDgUPERxi',
    },
    {
      id: 'pinelabs_Ivm039qfHI0lgx',
      name: 'pinelabs_poc',
      value: 'pinelabs_Ivm039qfHI0lgx',
    },
    {
      id: 'ingenico_IwhQmEjXH6qA5v',
      name: 'ingenico_1',
      value: 'ingenico_IwhQmEjXH6qA5v',
    },
    {
      id: 'razorpay',
      name: 'razorpay',
      value: 'razorpay',
    },
  ]);
});

test('Find provider name from terminal id', () => {
  const CHECK_RAZORPAY = findProviderName(TERMINAL_PROVIDERS, 'razorpay');
  expect(CHECK_RAZORPAY).toStrictEqual('razorpay');

  const CHECK_EXTERNAL_PROVIDER = findProviderName(TERMINAL_PROVIDERS, 'IPRZ2Vu2SsoN31');
  expect(CHECK_EXTERNAL_PROVIDER).toStrictEqual('paytm');

  const CHECK_PROVIDER_NOT_EXIST = findProviderName(TERMINAL_PROVIDERS, 'IwhQmEjXH6qA5o');
  expect(CHECK_PROVIDER_NOT_EXIST).toStrictEqual('IwhQmEjXH6qA5o');
});

describe('filterProvidersByExperiment - util', () => {
  // Mock data for testing
  const mockGateways = {
    atom: {
      'Gateway Name': { data_type: 'string', data_value: 'Atom', terminals_key: '' },
    },
    paytm: {
      'Gateway Name': { data_type: 'string', data_value: 'PayTm', terminals_key: '' },
    },
  };

  test('should handle missing or undefined experiments', () => {
    const filteredProviders = filterProvidersByExperiment(mockGateways, undefined);

    // Assert that all providers are included when experiments are missing or undefined
    expect(filteredProviders).toEqual(mockGateways);
  });

  test('should handle missing or undefined gateways', () => {
    const filteredProviders = filterProvidersByExperiment(undefined, undefined);

    // Assert that all providers are included when experiments are missing or undefined
    expect(filteredProviders).toEqual({});
  });

  test('should handle missing experiment result', () => {
    const SPLITZ_AB_EXPERIMENTS = { atom_gateway: { variables: {} } };

    const filteredProviders = filterProvidersByExperiment(mockGateways, SPLITZ_AB_EXPERIMENTS);

    // Assert that all providers are included when experiment result is missing
    expect(filteredProviders).toEqual(mockGateways);
  });

  test('should filter providers based on experiments', () => {
    const SPLITZ_AB_EXPERIMENTS = { atom_gateway: { variables: { result: 'off' } } };

    const filteredProviders = filterProvidersByExperiment(mockGateways, SPLITZ_AB_EXPERIMENTS);

    // Assert that "Atom" is filtered out because the experiment result is 'off'
    expect(filteredProviders).toEqual({
      paytm: {
        'Gateway Name': { data_type: 'string', data_value: 'PayTm', terminals_key: '' },
      },
    });
  });

  test('should handle "on" experiment result', () => {
    const SPLITZ_AB_EXPERIMENTS = { atom_gateway: { variables: { result: 'on' } } };

    const filteredProviders = filterProvidersByExperiment(mockGateways, SPLITZ_AB_EXPERIMENTS);

    // Assert that "Atom" gateway is included when experiment result is 'off'
    expect(filteredProviders).toEqual(mockGateways);
  });
});
