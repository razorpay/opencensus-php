import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

export function getSingleInstrumentBlockDetails(block: PaymentConfigInstrument | undefined) {
  const methodName = block?.method ?? '';
  switch (methodName) {
    case 'wallet':
      return {
        isSingleInstrument: !!(block?.wallets && block.wallets.length === 1),
        code: block?.wallets?.[0] ?? '',
      };
    case 'netbanking':
      return {
        isSingleInstrument: !!(block?.banks && block.banks.length === 1),
        code: block?.banks?.[0] ?? '',
      };
    case 'paylater':
      return {
        isSingleInstrument: !!(block?.providers && block.providers.length === 1),
        code: block?.providers?.[0] ?? '',
      };
    case 'cardless_emi':
      return {
        isSingleInstrument: !!(block?.providers && block.providers.length === 1),
        code: block?.providers?.[0] ?? '',
      };
    case 'upi':
      return {
        isSingleInstrument: !!(
          block?.flows?.length === 1 &&
          block?.flows[0] === 'intent' &&
          block?.apps?.length === 1
        ),
        code: block?.apps?.[0] ?? '',
      };
    default:
      return {
        isSingleInstrument: false,
        code: '',
      };
  }
}

export function getSingleInstrumentTitle(instrumentCode: string, instrument: any) {
  let allInstrumentsInMethod: any[] = [];
  const method = instrument.name ?? '';
  switch (method) {
    case 'wallet':
      allInstrumentsInMethod = instrument.wallets;
      return (
        allInstrumentsInMethod.find((instrument) => instrument.code === instrumentCode)?.name ?? ''
      );

    case 'netbanking':
      allInstrumentsInMethod = instrument.banks;
      return allInstrumentsInMethod.find((bank) => bank.code === instrumentCode)?.name ?? '';
    case 'paylater':
      allInstrumentsInMethod = instrument.providers;
      return (
        allInstrumentsInMethod.find((provider) => provider.code === instrumentCode)?.name ?? ''
      );
    case 'cardless_emi':
      allInstrumentsInMethod = instrument.providers.cardless;
      return (
        allInstrumentsInMethod.find((provider) => provider.code === instrumentCode)?.details
          ?.name ?? ''
      );
    case 'upi':
      allInstrumentsInMethod = instrument.apps;
      return (
        allInstrumentsInMethod.find((instrument) => instrument.code === instrumentCode)?.app_name ??
        ''
      );
    default:
      return '';
  }
}
