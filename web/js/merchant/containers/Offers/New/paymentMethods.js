import Input from 'common/new-ui/Input';

export default ({
  allPaymentMethodsAllowed,
  getFormOnChangeHandler,
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
    { label: 'Cardless EMI', name: 'cardless_emi' },
    { label: 'Pay Later', name: 'paylater' },
  ];
  const paymentIssuers = [
    { label: 'Select Issuers', name: '' },
    { label: 'HDFC Bank', name: 'HDFC' },
    { label: 'HSBC Bank', name: 'HSBC' },
    { label: 'ICICI Bank', name: 'ICIC' },
    { label: 'INDUSIND Bank', name: 'INDB' },
    { label: 'Kotak Mahindra Bank', name: 'KKBK' },
    { label: 'Ratnakar Bank Bank', name: 'RATN' },
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
  return (
    <React.Fragment>
      {!allPaymentMethodsAllowed && (
        <Input.Select
          label="Payment Method"
          name="payment_method"
          options={paymentMethods}
          placeholder="Payment Method"
          onChange={getFormOnChangeHandler()}
          defaultValue={paymentMethod}
        />
      )}

      {(isSelectedPaymentMethod('netbanking', 'card', 'emi') && (
        <Input.Select
          label="Issuer"
          name="issuer"
          defaultValue={issuer}
          placeholder="Payment Instrument Issuer/Bank Name"
          options={paymentIssuers}
        />
      )) ||
        null}
      {(isSelectedPaymentMethod('card', 'emi') && (
        <React.Fragment>
          <Input
            label="Maximum Usage Per Card"
            name="max_payment_count"
            defaultValue={maxPaymentCount}
            type="number"
            placeholder="Maximum usage of a card to avail this offer"
          />
          <Input.Select
            label="Card Type"
            name="payment_method_type"
            defaultValue={paymentMethodType}
            description="Card Type"
            onChange={getFormOnChangeHandler()}
            options={(() => {
              return isSelectedPaymentMethod('emi')
                ? [{ label: 'Credit Card', name: 'credit' }]
                : [
                    { label: 'Credit Card', name: 'credit' },
                    { label: 'Debit Card', name: 'debit' },
                  ];
            })()}
            required
          />
          <Input.Select
            label="Payment Method Network"
            name="payment_network"
            defaultValue={paymentNetwork}
            placeholder="Payment Method Type"
            options={paymentNetworks}
          />
          <Input
            label="IINs"
            defaultValue={iins}
            onChange={getFormOnChangeHandler('iins')}
            placeholder="6 digit IINs for cards. Separated by comma if more than one"
            description={iins && iins.join(', ')}
          />
        </React.Fragment>
      )) ||
        null}
    </React.Fragment>
  );
};
