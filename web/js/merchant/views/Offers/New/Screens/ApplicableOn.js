import Input from 'common/new-ui/Input';
import {
  PAYMENT_METHODS,
  PaymentMethodsOptions,
  PaymentIssuersOptions,
  PaymentNetworksOptions,
  WalletIssuersOptions,
  MAX_DISCOUNT,
  CREDIT_DEBIT_CARDS_OPTIONS,
  CREDIT_CARDS_OPTIONS,
} from 'merchant/views/Offers/constants';

export default class ApplicableOn extends React.Component {
  get currentSelectedPaymentMethod() {
    const { payment_method } = this.props.formData;
    return {
      isCard: payment_method === PAYMENT_METHODS.Card,
      isNetBanking: payment_method === PAYMENT_METHODS.NetBanking,
      isWallet: payment_method === PAYMENT_METHODS.Wallet,
      isUPI: payment_method === PAYMENT_METHODS.UPI,
      isEMI: payment_method === PAYMENT_METHODS.EMI,
      isPayLater: payment_method === PAYMENT_METHODS.PayLater,
      isCardLessEmi: payment_method === PAYMENT_METHODS.CardLessEmi,
    };
  }

  render() {
    const { props } = this;
    const { formData } = props;

    const PaymentMethodTypeOptions = this.currentSelectedPaymentMethod.isEMI
      ? CREDIT_CARDS_OPTIONS
      : CREDIT_DEBIT_CARDS_OPTIONS;

    return (
      <React.Fragment>
        <Input.Select
          required
          name="payment_method"
          label="Payment Method"
          options={PaymentMethodsOptions}
          placeholder="Select Payment Method"
          defaultValue={formData.payment_method}
          validator={validatePaymentMethod}
          disabled={props.isFormLocked}
        />

        {this.currentSelectedPaymentMethod.isWallet && (
          <Input.Select
            name="issuer"
            label="Issuer"
            defaultValue={formData.issuer}
            placeholder="Select Bank"
            options={WalletIssuersOptions}
            disabled={props.isFormLocked}
          />
        )}

        {(this.currentSelectedPaymentMethod.isCard || this.currentSelectedPaymentMethod.isEMI) && (
          <React.Fragment>
            <Input.Select
              name="payment_method_type"
              label="Card Type"
              defaultValue={formData.payment_method_type}
              options={PaymentMethodTypeOptions}
              disabled={props.isFormLocked}
            />

            <Input.Select
              name="issuer"
              label="Bank"
              placeholder="Select Bank"
              defaultValue={formData.issuer}
              disabled={props.isFormLocked}
              options={PaymentIssuersOptions}
            />

            <Input.Select
              label="Network"
              name="payment_network"
              placeholder="Select network"
              defaultValue={formData.payment_network}
              disabled={props.isFormLocked}
              options={PaymentNetworksOptions}
            />

            <Input
              type="number"
              label="Max Usage Per Card"
              name="max_payment_count"
              defaultValue={formData.max_payment_count}
              placeholder="Max times a card can be used to avail this offer"
              validator={validateMaxPaymentCount}
              disabled={props.isFormLocked}
            />

            <Input
              name="iins"
              label="IINs"
              defaultValue={formData.iins}
              placeholder="6 digit IINs for cards. Separated by comma if more than one"
              description={formData.iins && formData.iins.join(', ')}
              disabled={props.isFormLocked}
            />
          </React.Fragment>
        )}

        {this.currentSelectedPaymentMethod.isNetBanking && (
          <Input.Select
            label="Issuer"
            name="issuer"
            defaultValue={formData.issuer}
            placeholder="Payment Instrument Issuer/Bank Name"
            options={PaymentIssuersOptions}
            disabled={props.isFormLocked}
          />
        )}
      </React.Fragment>
    );
  }
}

function validatePaymentMethod(val) {
  if (!val) {
    return 'Payment method cannot be empty';
  }
}

function validateMaxPaymentCount(val) {
  if (!val) return;

  if (!new RegExp('^[0-9]+$').test(val)) {
    return 'Please enter a number';
  }

  val = parseFloat(val);
  if (val > MAX_DISCOUNT) {
    return `Maximum value allowed is ${MAX_DISCOUNT}`;
  }
}
