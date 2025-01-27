import { Link, ArrowUpRightIcon } from '@razorpay/blade/components';

import { CommonPoints } from 'merchant/views/Navigator/components/Provider/SeamlessComponents/CommonPoints';

export const NETBANKING_FEATURES = 'Netbanking Features';
export const UPI_FEATURES = 'UPI Features';

export const INTERNATIONAL_GATEWAYS = ['checkout_dot_com_optimizer', 'airwallex_optimizer'];
export const BANK_GATEWAYS = [
  'upi_icici',
  'upi_mindgate',
  'upi_axis',
  'netbanking_axis',
  'hdfc',
  'cybersource_hdfc',
  'cybersource_axis',
  'axis_migs',
  'netbanking_icici',
  'netbanking_hdfc',
];

export const GATEWAY_CATEGORIES = {
  aggregators: 'Aggregators',
  international_gateways: 'International Gateways',
  bank_gateways: 'Bank Gateways',
};

export const RAZORPAY_GATEWAY_KEY = 'optimizer_razorpay';
export const RECOMMENDED_GATEWAYS = ['payu'];

export const INTEGRATION_AUDIT_COVERED_GATEWAY = ['payu', 'cashfree', 'paytm'];

export const METHODS = {
  CARD: 'card',
  EMANDATE: 'emandate',
  EMI: 'emi',
  NETBANKING: 'netbanking',
  UPI: 'upi',
  WALLET: 'wallet',
  SODEXO: 'sodexo',
};

export const METHODS_MAP = {
  card: 'Card',
  emandate: 'E-Mandate',
  emi: 'EMI',
  netbanking: 'Netbanking',
  upi: 'UPI',
  wallet: 'Wallet',
  sodexo: 'Pluxee',
  cod: 'Cash on Delivery (COD)',
  paylater: 'Pay Later',
};

export const INIT_PROVIDER_STATE = {
  Provider_name: '',
  Description: '',
  Gateway: '',
  Gateway_details: {
    'Payment Methods': [],
  },
};

export const INIT_FORM_STATE = {
  isEdit: true,
  provider: {},
  selectedProvider: '',
  steps: {
    1: {
      edit: false,
      show: true,
    },
    2: {
      edit: true,
      show: true,
    },
    3: {
      edit: false,
      show: true,
    },
  },
};

export const PROVIDER_KEYS = {
  GATEWAY_NAME: 'Gateway Name',
  GATEWAY_ACQUIRER: 'Gateway Acquirer',
  SEAMLESS_KEY: 'optimizer_seamless_disabled',
  SODEXO: 'Sodexo',
  RECURRING: 'Recurring',
  MANDATORY_METHODS: 'Mandatory Methods',
  ROUTE: 'optimizer_route',
};

export const SKIP_INPUT_FOR_PROVIDER_KEYS = [
  PROVIDER_KEYS.GATEWAY_NAME,
  PROVIDER_KEYS.GATEWAY_ACQUIRER,
  PROVIDER_KEYS.SODEXO,
  PROVIDER_KEYS.SEAMLESS_KEY,
  PROVIDER_KEYS.MANDATORY_METHODS,
];

export const WALLET_AUTO_DEBIT_KEY = 'ENABLE_AUTO_DEBIT';

export const SKIP_VALIDATION_KEYS = [
  'Gateway Name',
  'TPV',
  'optimizer_seamless_disabled',
  PROVIDER_KEYS.SODEXO,
  WALLET_AUTO_DEBIT_KEY,
  PROVIDER_KEYS.RECURRING,
  PROVIDER_KEYS.MANDATORY_METHODS,
  PROVIDER_KEYS.ROUTE,
];

export const SKIP_PAYTM_AUTO_DEBIT_VALIDATION_KEYS = ['CLIENT_KEY', 'CLIENT_SECRET']; // These fields are only required if the wallet auto debit is enabled on paytm

export const SKIP_PHONEPE_CARD_VALIDATION_KEYS = ['Encryption certificate', 'Encryption key id']; // These fields are only required if the card payment method is enabled on phonepe

export const TPV_OPTIONS = {
  0: 'Non TPV',
  1: 'TPV Only',
  2: 'Both (TPV and Non TPV)',
};

export const HAS_UPI_FEATURES = [
  'upi_mindgate',
  'upi_icici',
  'upi_axis',
  'billdesk_optimizer',
  'pay10',
  'optimizer_razorpay',
  'easebuzz_optimizer',
  'zaakpay',
  'cashfree',
  'payu',
  'pinelabs',
  'ingenico',
  'phonepe',
];

export const HAS_NETBANKING_FEATURES = [
  'atom',
  'netbanking_axis',
  'billdesk_optimizer',
  'pay10',
  'netbanking_icici',
  'netbanking_hdfc',
  'optimizer_razorpay',
  'easebuzz_optimizer',
  'zaakpay',
  'cashfree',
  'payu',
  'ingenico',
  'atom',
  'phonepe',
];

export const ACCOUNT_TYPE_OPTIONS = [
  { label: 'Regular', value: false },
  { label: 'Banking VAS', value: true },
];

/** Seamless option constants - Start **/
export const SEAMLESS_PROVIDERS = ['paytm', 'payu', 'cashfree'];

export const SEAMLESS_OPTIONS = [
  { label: 'Instant (beta)', value: true },
  { label: 'Server-to-Server', value: false },
];

export const INSTANT_PROVIDER_UNSUPPORTED_METHODS = {
  paytm: [],
  payu: ['emi', 'emandate'],
  cashfree: ['card'],
};

export const PREREQUISITES_SUPPORTED_GATEWAYS = [
  'ingenico',
  'getsimpl_optimizer',
  'netbanking_icici',
  'netbanking_hdfc',
];

export const SEAMLESS_NOT_SUPPORTED = ['checkout_dot_com_optimizer', RAZORPAY_GATEWAY_KEY];

export const SEAMLESS_CONTENT = {
  paytm: {
    disable: {
      headerText: 'Enable Instant (beta)',
      infoBlock: (
        <div>
          <p>
            Go live with your Paytm PG account instantly via &rsquo;Instant&rsquo; integration mode.
          </p>
          <p>
            This is a beta release and supports the following payment methods - Debit Cards, Credit
            Cards, UPI, Netbanking, Paytm Wallet.
          </p>
          <p className="seamless-refund-enable-header">
            Action Required: Please enable refunds API on your Paytm account.
          </p>
          <p>
            Please reach out to the Paytm support team (pg.support@paytmpayments.com) and ask them
            to enable refunds via API for your Paytm account. For a sample email template and other
            details please refer to the &nbsp;
            <Link
              href="https://razorpay.com/docs/payments/optimizer/paytm-instant"
              icon={ArrowUpRightIcon}
              iconPosition="right"
              rel="noreferrer noopener"
              target="_blank"
              variant="anchor"
              size="small"
            >
              document
            </Link>
          </p>
        </div>
      ),
    },
    enable: {
      headerText: 'Enable Server-to-Server',
      infoBlock: (
        <div>
          <p>
            To integrate your Paytm PG account via ‘Server-to-Server’ mode, please ensure the below
            mentioned steps have been completed.
          </p>
        </div>
      ),
      buttonText: 'Prerequisites',
      listPoints: [
        '{gatewayName} has enabled ‘seamless’',
        '‘Disable retry’ has been enabled',
        '‘Refund processing’ has been enabled',
        'Production & Staging URL webhooks have been configured',
        'UPI Intent and collect flows have been enabled',
      ],
      footerLink: 'https://razorpay.com/docs/payments/optimizer/paytm-s2s',
      footerText: null,
    },
  },
  payu: {
    disable: {
      headerText: 'Enable Instant (beta)',
      infoBlock: (
        <div>
          <p>
            Go live with your Payu PG account instantly via &rsquo;Instant&rsquo; integration mode.
          </p>
          <p>
            This is a beta release and supports the following payment methods - Cards, Netbanking,
            UPI, Wallet.
          </p>
        </div>
      ),
      buttonText: 'Prerequisites:',
      listPoints: (
        <ul>
          <li>
            Configure the necessary webhooks on the Payu dashboard as mentioned&nbsp;
            <Link
              href="https://razorpay.com/docs/payments/optimizer/payu-instant"
              target="_blank"
              rel="noopener noreferrer"
              size="small"
            >
              here
            </Link>
          </li>
          <li>
            Please ensure that all necessary methods have been enabled on your Payu account (eg: UPI
            Intent)
          </li>
        </ul>
      ),
      footerLink: 'https://razorpay.com/docs/payments/optimizer/payu-instant',
      footerText: null,
    },
    enable: {
      headerText: 'Enable Server-to-Server',
      infoBlock: (
        <div>
          <p>Your PayU account should have the seamless option enabled to use optimizer.</p>
        </div>
      ),
      buttonText: 'How to enable seamless option on PayU?',
      listPoints: (
        <ol>
          <CommonPoints gatewayName="PayU" />
          <li>
            If you are going to use UPI as a payment method following steps will have to configured:
            <ol type="a">
              <li>
                configure webhook URL as{' '}
                <Link
                  href="https://api.razorpay.com/v1/callback/payu"
                  target="_blank"
                  rel="noopener noreferrer"
                  size="small"
                >
                  {`https://api.razorpay.com/v1/callback/payu`}
                </Link>{' '}
                to receive UPI response
              </li>
              <li>enable UPI on seamless with the flag “txn_s2s_flow=4”</li>
            </ol>
          </li>
        </ol>
      ),
      footerLink: null,
      footerText: null,
    },
  },
  cashfree: {
    disable: {
      headerText: 'Enable Instant (beta)',
      infoBlock: (
        <div>
          <p>
            Go live with your Cashfree PG account instantly via &rsquo;Instant&rsquo; integration
            mode.
          </p>
          <p>
            This is a beta release and supports the following payment methods - UPI and Netbanking.
          </p>
        </div>
      ),
      buttonText: 'Prerequisites:',
      listPoints: (
        <ul>
          <li>
            Please ensure that all necessary methods have been enabled on your Cashfree account (eg:
            UPI Intent)
          </li>
        </ul>
      ),
      notePoints: [
        'Card & Wallet Payments via Cashfree PG are currently not supported on Instant onboarding. Please setup a rule to route all Card & Wallet payments to Razorpay PG.',
      ],
      footerLink: 'https://razorpay.com/docs/payments/optimizer/cashfree-instant',
    },
    enable: {
      headerText: 'Enable Server-to-Server',
      infoBlock: (
        <div>
          <p>Your Cashfree account should have the seamless option enabled to use optimizer.</p>
        </div>
      ),
      buttonText: 'How to enable seamless option on Cashfree?',
      listPoints: (
        <ol>
          <CommonPoints gatewayName="Cashfree" />
        </ol>
      ),
    },
  },
};

/** Seamless option constants - End **/
