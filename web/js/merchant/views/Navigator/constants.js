import { Link } from '@razorpay/blade/components';

import { CommonPoints } from 'merchant/views/Navigator/components/Provider/SeamlessComponents/CommonPoints';

export const NETBANKING_FEATURES = 'Netbanking Features';
export const UPI_FEATURES = 'UPI Features';

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

export const WALLET_AUTO_DEBIT_KEY = 'ENABLE_AUTO_DEBIT';

export const SKIP_VALIDATION_KEYS = [
  'Gateway Name',
  'TPV',
  'optimizer_seamless_disabled',
  'Sodexo',
  WALLET_AUTO_DEBIT_KEY,
];

export const SKIP_PAYTM_AUTO_DEBIT_VALIDATION_KEYS = ['CLIENT_KEY', 'CLIENT_SECRET']; // These fields are only required if the wallet auto debit is enabled on paytm

export const PROVIDER_KEYS = {
  SODEXO: 'Sodexo',
};

export const METHODS = {
  CARD: 'card',
  SODEXO: 'sodexo',
};

export const TPV_OPTIONS = {
  0: 'Non TPV',
  1: 'TPV Only',
  2: 'Both (TPV and Non TPV)',
};

export const HAS_UPI_FEATURES = ['upi_mindgate', 'upi_icici', 'upi_axis'];
export const HAS_NETBANKING_FEATURES = ['atom', 'netbanking_axis'];

/** Seamless option constants - Start **/
export const SEAMLESS_PROVIDERS = ['paytm', 'payu'];

export const SEAMLESS_OPTIONS = [
  { label: 'Instant (beta)', value: true },
  { label: 'Server-to-Server', value: false },
];

export const INSTANT_PROVIDER_UNSUPPORTED_METHODS = {
  paytm: ['upi'],
  payu: ['emi', 'emandate'],
};

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
            Cards, Netbanking, Paytm Wallet.
          </p>
        </div>
      ),
      buttonText: 'Please note that `Instant` integration mode does not support the following:',
      listPoints: (
        <ul>
          <li>
            <b>UPI Payments via Paytm</b>&nbsp;
            <span>
              - UPI payments via Paytm PG are currently not supported on this Integration mode. By
              default UPI payments will be routed to Razorpay PG. You can &nbsp;
              <a
                href="https://razorpay.com/docs/payments/optimizer/create-custom-rule"
                target="_blank"
                rel="noopener noreferrer"
              >
                configure a new rule
              </a>
              &nbsp; to change this if required.
            </span>
          </li>
          <li>
            <b>Refunds for Paytm payments</b>&nbsp;
            <span>
              - Refunds for Paytm PG payments will need to be processed from your Paytm dashboard.
              Please visit &nbsp;
              <a
                href="https://dashboard.paytm.com/login/"
                target="_blank"
                rel="noopener noreferrer"
              >
                https://dashboard.paytm.com/login/
              </a>
            </span>
          </li>
        </ul>
      ),
      footerLink: 'https://razorpay.com/docs/payments/optimizer/paytm-instant',
      footerText: (
        <p>
          If you are keen on offering all payment methods and supporting refunds from Razorpay
          dashboard, please explore ‘Server-to-Server’ integration mode.
        </p>
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
};

/** Seamless option constants - End **/
