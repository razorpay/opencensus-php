import Input from 'common/new-ui/Input';

const getTermsAndConditionsCheck = (value, onChange) => {
  return (
    <React.Fragment>
      <div className="horizontally-stacked-checkbox">
        <input
          defaultChecked={value === 'false' ? 0 : 1}
          type="checkbox"
          name="creation_terms_accepted"
          onChange={e => {
            e.target.value = e.target.checked;
            onChange(e);
          }}
        />
        <span>Terms and Conditions:</span>
        <p>
          I understand that the discount/cashback given in this offer will be
          borne by me and not razorpay
        </p>
      </div>
    </React.Fragment>
  );
};

const progressionList = progression => {
  return (
    <ul class="Workflow-list">
      {progression.map((x, i) => (
        <li className="Workflow-list--item" key={i}>
          {x}
        </li>
      ))}
    </ul>
  );
};

const BANK_MAP = {
  HDFC: 'HDFC Bank',
  HSBC: 'HSBC Bank',
  ICIC: 'ICICI Bank',
  INDB: 'INDUSIND Bank',
  KKBK: 'Kotak Mahindra Bank',
  RATN: 'Ratnakar Bank ',
  SCBL: 'Standard Chartered Bank',
  UTIB: 'Axis Bank',
  YESB: 'Yes Bank',
  CITI: 'Citi Bank',
  SBIN: 'State Bank of India',
  BARB: 'Bank of Baroda Bank',
};

const WALLET_MAP = {
  paytm: 'Paytm',
  payzapp: 'PAYZAPP',
  mobikwik: 'MOBIKWIK',
  payumoney: 'PayU Money',
  olamoney: 'OLA Money',
  airtelmoney: 'Airtel Money',
  amazonpay: 'Amazon Pay',
  freecharge: 'Freecharge',
  jiomoney: 'JIO Money',
  sbibuddy: 'SBI Buddy',
  openwallet: 'OPEN',
  mpesa: 'M PESA',
  phonepe: 'Phone Pe',
  paypal: 'Paypal',
};

const PAYMENT_NETWORK_MAP = {
  VISA: 'Visa',
  RUPAY: 'RuPay',
  MC: 'MasterCard',
  DICL: 'Diners Club',
  MAES: 'Maestro',
  AMEX: 'American Express',
};

const getDualColumnTable = (key, value, columnRatio = 0.25) => {
  return (
    <div
      className={'dual-column-table'}
      style={{ gridTemplateColumns: `${columnRatio}fr ${1 - columnRatio}fr` }}
    >
      {
        <span>
          {key}
          {key && ':'}
        </span>
      }
      <span>{value}</span>
    </div>
  );
};

const wordWithSpace = word => {
  return word ? word + ' ' : '';
};

const summarizePaymentMethodsData = (
  paymentMethod,
  cardType,
  paymentNetwork,
  issuer
) => {
  switch (paymentMethod) {
    case 'card':
      return `All ${wordWithSpace(BANK_MAP[issuer])}${wordWithSpace(
        PAYMENT_NETWORK_MAP[paymentNetwork]
      )}${wordWithSpace(cardType)}Cards`;
    case 'netbanking':
      return `Netbanking on all ${wordWithSpace(BANK_MAP[issuer])}accounts`;

    case 'wallet':
      return `All ${wordWithSpace(WALLET_MAP[issuer])}wallets`;
    case 'upi':
      return `UPI`;

    case 'emi':
      return `EMI on all ${wordWithSpace(BANK_MAP[issuer])}${wordWithSpace(
        PAYMENT_NETWORK_MAP[paymentNetwork]
      )}${wordWithSpace(cardType)}cards`;
    case 'cardless_emi':
      return `Cardless EMI`;
    case 'paylater':
      return `Paylater`;
  }
};

export default ({
  data: {
    terms,
    discount_type,
    flat_cashback,
    min_amount,
    display_text,
    payment_method,
    starts_at,
    ends_at,
    payment_network,
    payment_method_type,
    issuer,
    percent_rate,
    max_cashback,
    creation_terms_accepted,
  },
  currencySymbol,
  getFormOnChangeHandler,
}) => {
  return (
    <div class="Subscription--New-review">
      <div class="Payments">
        {progressionList([
          <div>
            <p>
              <strong>Description:</strong>
            </p>
            {getDualColumnTable('Display Text', display_text)}
            {getDualColumnTable('Offer Terms', terms)}
          </div>,
          <div>
            <p>
              <strong>Discount Type:</strong>
            </p>
            {getDualColumnTable(
              discount_type + ' discount',
              discount_type == 'flat'
                ? `Flat discount of ${currencySymbol} ${flat_cashback} on a minimum purchase of ${currencySymbol} ${min_amount}`
                : `${percent_rate}% discount upto ${currencySymbol} ${max_cashback} on a minimum purchase of ${currencySymbol} ${min_amount}`
            )}
          </div>,
          <div>
            <p>
              <strong>Applicable On:</strong>
            </p>
            {getDualColumnTable(
              'Payment Method',
              summarizePaymentMethodsData(
                payment_method,
                payment_method_type,
                payment_network,
                issuer
              )
            )}
          </div>,
          <div>
            <p>
              <strong>Offer Validation:</strong>
            </p>
            {getDualColumnTable(
              'Offer Validity',
              `${starts_at ? starts_at.format('DD-MM-YY, hh:mm a') : '--'} to ${
                ends_at ? ends_at.format('DD-MM-YY, hh:mm a') : '--'
              }`
            )}
          </div>,
        ])}
        {getTermsAndConditionsCheck(
          creation_terms_accepted,
          getFormOnChangeHandler
        )}
      </div>
    </div>
  );
};
