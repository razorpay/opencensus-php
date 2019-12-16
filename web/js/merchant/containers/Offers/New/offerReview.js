import Input from 'common/new-ui/Input';

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
      return `Netbanking`;

    case 'wallet':
      return `All wallets`;
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
    payment_method,
    starts_at,
    ends_at,
    payment_network,
    payment_method_type,
    issuer,
    percent_rate,
    max_cashback,
  },
}) => {
  return (
    <div class="Subscription--New-review">
      <div class="Payments">
        {progressionList([
          <div>
            <p>
              <strong>Description:</strong>
            </p>
            {getDualColumnTable('Display Text', '10% off on all HDFC cards')}
            {getDualColumnTable('Offer Terms', terms)}
          </div>,
          <div>
            <p>
              <strong>Discount Type:</strong>
            </p>
            {getDualColumnTable(
              discount_type + ' discount',
              discount_type == 'flat'
                ? `Flat discount of ${flat_cashback} on a minimum purchase of ${min_amount}`
                : `${percent_rate}% discount upto ${max_cashback} on a minimum purchase of ${min_amount}`
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
              `${starts_at.format('DD-MM-YY, HH:MM a')} to ${ends_at.format(
                'DD-MM-YY, HH:MM a'
              )}`
            )}
          </div>,
        ])}
        <Input.Check
          className={'Input--vTop'}
          fieldLabel={'Offer available for all users on checkout.'}
          name="checkout_visibility"
        />
      </div>
    </div>
  );
};
