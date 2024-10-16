import { titleCase } from '@dashboard/shared-utils/rzp-utils';
import { Provider } from './types';

const LOGO_PATH = 'static/assets/merchant-dash/providers';

const getLogoPath = (logoName: string, extension = 'png'): string =>
  `${window.cdnBaseUrl}/${LOGO_PATH}/${logoName}.${extension}`;

export const gatewayLogos = {
  razorpay: getLogoPath('razorpay'),
  smart_router: getLogoPath('razorpay'),
  optimizer_razorpay: getLogoPath('razorpay'),
  payu: getLogoPath('payu'),
  paytm: getLogoPath('paytm'),
  billdesk_optimizer: getLogoPath('bill-desk'),
  atom: require('apps/self-serve/src/assets/atom.png'),
  fss: getLogoPath('fss'),
  cybersource: getLogoPath('cybersource'),
  cybersource_hdfc: getLogoPath('cybersource'),
  cybersource_axis: getLogoPath('cybersource'),
  cashfree: getLogoPath('cashfree', 'svg'),
  ccavenue: getLogoPath('ccavenue', 'svg'),
  upi_mindgate: getLogoPath('hdfc'),
  pinelabs: getLogoPath('pinelabs'),
  ingenico: require('apps/self-serve/src/assets/ingenico.png'),
  axis_migs: getLogoPath('axis'),
  upi_axis: getLogoPath('axis'),
  hdfc: getLogoPath('hdfc'),
  upi_icici: getLogoPath('icici'),
  netbanking_axis: getLogoPath('axis'),
  checkout_dot_com_optimizer: require('apps/self-serve/src/assets/cko.png'),
  easebuzz_optimizer: require('apps/self-serve/src/assets/easebuzz_optimizer.png'),
  wallet_payzapp: require('apps/self-serve/src/assets/payzapp.png'),
  pay10: require('apps/self-serve/src/assets/pay10.png'),
};

export const findProviderDetails = (
  terminalProviders: any[],
  terminal_id: string | null,
  settled_by: string | null,
): Provider | null => {
  let provider: Provider | null = null;
  if (terminal_id === 'Razorpay') {
    provider = {
      Provider_name: 'Razorpay',
      Gateway: 'razorpay',
    };
  } else if (terminalProviders?.length > 0 && terminal_id) {
    provider = terminalProviders.filter((p) => p.Terminal_id === terminal_id)[0];
  }
  if (!provider && settled_by) {
    provider = {
      Provider_name: titleCase(settled_by),
      Gateway: settled_by === 'Razorpay' ? 'razorpay' : settled_by,
    };
  }
  return provider;
};
