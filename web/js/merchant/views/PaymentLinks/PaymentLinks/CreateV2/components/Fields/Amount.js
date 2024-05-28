import Input from 'common/new-ui/Input';
import { getAmountFieldPlaceholder } from 'common/utils/rzp-utils';
import { validateAmount } from 'common/utils/validators';
import { FORM_CLASS_NAME } from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/FormWizard';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

const Amount = (props) => {
  const { defaultCurrency, defaultAmount, disableCurrencySelect, disabled } = props;

  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop amount"
      label="Amount"
      required
      disabled={disabled}
    >
      <div class="Input-content pt-8">
        <Input.CurrencySelect
          autoRender
          name="currency"
          defaultValue={defaultCurrency}
          disabled={disableCurrencySelect}
          parentQuerySelector=".Modal-body"
          onChange={track.lj.fields.currency}
        />
        <Input
          autoRender
          required
          name="amount"
          placeholder={getAmountFieldPlaceholder(defaultCurrency)}
          defaultValue={defaultAmount}
          validator={(value) => amountValidator(value, defaultCurrency)}
          onBlur={callTrackers(props)}
          currency={defaultCurrency} //this is a hack to trigger validation on currency change
        />
      </div>
    </Input.Group>
  );
};

function amountValidator(value, currency) {
  return validateAmount(value, null, currency);
}

function callTrackers(props) {
  return () => {
    track.lj.fields.amount({
      modified: props.isIntentDuplicate,
    });

    const invalidAmountField = document.querySelector(
      `.${FORM_CLASS_NAME} .amount .Input.Input--required.is-invalid`,
    );
    if (invalidAmountField) {
      track.lj.form.fail({ response: 'Please fill out this field' });
    }
  };
}

export default Amount;
