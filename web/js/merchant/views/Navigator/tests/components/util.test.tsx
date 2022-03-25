import { createMappedProviders, findProviderName } from 'merchant/views/Navigator/components/util';
import { TERMINAL_PROVIDERS } from 'merchant/views/Navigator/tests/data/mockData';

const isAddProviderEnabled = true; // razorx experiment for self serve

test('Map providers for dropdown', () => {
  const MAPPED_PROVIDERS = createMappedProviders(isAddProviderEnabled, [], TERMINAL_PROVIDERS);

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
