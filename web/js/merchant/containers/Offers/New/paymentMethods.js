import Input from 'common/new-ui/Input';

export default ({
  allPaymentMethodsAllowed,
  getFormOnChangeHandler,
  getFormElementValidations,
  isSelectedPaymentMethod,
  iins,
  paymentNetwork,
  maxPaymentCount,
  paymentMethodType,
  issuer,
  paymentMethod,
}) => {
  const paymentMethods = [
    { label: 'Select Payment method', name: '' },
    { label: 'Card', name: 'card' },
    { label: 'Net Banking', name: 'netbanking' },
    { label: 'Wallet', name: 'wallet' },
    { label: 'UPI', name: 'upi' },
    { label: 'EMI', name: 'emi' },
    { label: 'Pay Later', name: 'paylater' },
  ];
  const paymentIssuers = [
    { label: 'Select Issuers', name: '' },
    { label: 'HDFC Bank', name: 'HDFC' },
    { label: 'HSBC Bank', name: 'HSBC' },
    { label: 'ICICI Bank', name: 'ICIC' },
    { label: 'INDUSIND Bank', name: 'INDB' },
    { label: 'Kotak Mahindra Bank', name: 'KKBK' },
    { label: 'Ratnakar Bank', name: 'RATN' },
    { label: 'Standard Chartered Bank', name: 'SCBL' },
    { label: 'Axis Bank', name: 'UTIB' },
    { label: 'Yes Bank', name: 'YESB' },
    { label: 'Citi Bank', name: 'CITI' },
    { label: 'State Bank of India', name: 'SBIN' },
    { label: 'Bank of Baroda Bank', name: 'BARB' },
  ];

  const paymentNetworks = [
    { label: 'Select Network', name: '' },
    { label: 'Visa', name: 'VISA' },
    { label: 'RuPay', name: 'RUPAY' },
    { label: 'MasterCard', name: 'MC' },
    { label: 'Diners Club', name: 'DICL' },
    { label: 'Maestro', name: 'MAES' },
    { label: 'American Express', name: 'AMEX' },
  ];

  let walletIssuers = [
    { label: 'Select Issuers', name: '' },
    { label: 'Paytm', name: 'paytm' },
    { label: 'PAYZAPP', name: 'payzapp' },
    { label: 'MOBIKWIK', name: 'mobikwik' },
    { label: 'PayU Money', name: 'payumoney' },
    { label: 'OLA Money', name: 'olamoney' },
    { label: 'Airtel Money', name: 'airtelmoney' },
    { label: 'Amazon Pay', name: 'amazonpay' },
    { label: 'Freecharge', name: 'freecharge' },
    { label: 'JIO Money', name: 'jiomoney' },
    { label: 'SBI buddy', name: 'sbibuddy' },
    { label: 'OPEN WALLET', name: 'openwallet' },
    { label: 'M PESA', name: 'mpesa' },
    { label: 'Phone Pe', name: 'phonepe' },
    { label: 'Paypal', name: 'paypal' },
  ];

  return (
    <React.Fragment>
      {!allPaymentMethodsAllowed && (
        <Input.Select
          required
          label="Payment Method"
          name="payment_method"
          options={paymentMethods}
          placeholder="Select Payment Method"
          onChange={getFormOnChangeHandler('stateResetter')([
            'payment_method_type',
            'issuer',
            'payment_network',
            'max_payment_count',
            'iins',
          ])}
          defaultValue={paymentMethod}
          validator={getFormElementValidations('payment_method')}
        />
      )}

      {isSelectedPaymentMethod('wallet') && (
        <Input.Select
          label="Issuer"
          name="issuer"
          defaultValue={issuer}
          placeholder="Select Bank"
          options={walletIssuers}
          onChange={getFormOnChangeHandler()}
        />
      )}

      {(isSelectedPaymentMethod('card', 'emi') && (
        <React.Fragment>
          <Input.Select
            label="Card Type"
            name="payment_method_type"
            defaultValue={paymentMethodType}
            options={(() => {
              return isSelectedPaymentMethod('emi')
                ? [{ label: 'Credit Card', name: 'credit' }]
                : [
                    { label: 'Both Credit and Debit Cards', name: '' },
                    { label: 'Credit Card', name: 'credit' },
                    { label: 'Debit Card', name: 'debit' },
                  ];
            })()}
            onChange={getFormOnChangeHandler()}
          />
          <Input.Select
            label="Bank"
            name="issuer"
            defaultValue={issuer}
            placeholder="Select Bank"
            options={paymentIssuers}
            onChange={getFormOnChangeHandler()}
          />
          <Input.Select
            label="Network"
            name="payment_network"
            defaultValue={paymentNetwork}
            placeholder="Select network"
            options={paymentNetworks}
            onChange={getFormOnChangeHandler()}
          />
          <Input
            label="Max Usage Per Card"
            name="max_payment_count"
            defaultValue={maxPaymentCount}
            type="number"
            validator={getFormElementValidations('max_payment_count')}
            placeholder="Max times a card can be used to avail this offer"
            onChange={getFormOnChangeHandler()}
          />
          <Input
            label="IINs"
            defaultValue={iins}
            onChange={getFormOnChangeHandler('iins')}
            placeholder="6 digit IINs for cards. Separated by comma if more than one"
            description={iins && iins.join(', ')}
            onChange={getFormOnChangeHandler()}
          />
        </React.Fragment>
      )) ||
        null}

      {(isSelectedPaymentMethod('netbanking') && (
        <Input.Select
          label="Issuer"
          name="issuer"
          defaultValue={issuer}
          placeholder="Payment Instrument Issuer/Bank Name"
          options={paymentIssuers}
          onChange={getFormOnChangeHandler()}
        />
      )) ||
        null}
    </React.Fragment>
  );
};
