import Input from 'common/new-ui/Input';

import Card from './Card';
import NACH from './NACH';
import Emandate from './Emandate';
import { checkIfAmount } from './utils';

export default props => {
  const {
    showAmountField,
    amount,
    accountType,
    isNachFormAval,
    mandateMethod,
    avlblMethods,
    skipBankDetails,
    emandateBanks,
    handleNotesChange,
    isEmandatePayment,
    isCardPayment,
    isNACHPayment,
    bankAccountIFSC,
    bankName,
    beneficiaryName,
    bankAccountNumber,
    trackClickPaymentMethod,
    trackReceivedNACHForm,
    trackNACHToolTipHover,
    formReference1,
    formReference2,
    onBlurElement,
  } = props;

  return (
    <React.Fragment>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
        trackClickPaymentMethod={trackClickPaymentMethod}
        onBlurElement={onBlurElement}
      />

      {isEmandatePayment && (
        <Emandate
          amount={amount}
          showAmountField={showAmountField}
          emandateBanks={emandateBanks}
          skipBankDetails={skipBankDetails}
          bankName={bankName}
          accountType={accountType}
          bankAccountIFSC={bankAccountIFSC}
          beneficiaryName={beneficiaryName}
          bankAccountNumber={bankAccountNumber}
          onBlurElement={onBlurElement}
        />
      )}

      {isCardPayment && <Card amount={amount} onBlurElement={onBlurElement} />}

      {isNACHPayment && (
        <NACH
          isNachFormAval={isNachFormAval}
          bankName={bankName}
          accountType={accountType}
          bankAccountIFSC={bankAccountIFSC}
          beneficiaryName={beneficiaryName}
          bankAccountNumber={bankAccountNumber}
          trackReceivedNACHForm={trackReceivedNACHForm}
          trackNACHToolTipHover={trackNACHToolTipHover}
          formReference1={formReference1}
          formReference2={formReference2}
          onBlurElement={onBlurElement}
        />
      )}

      <Input.PairList
        name="notes"
        label="Internal Notes"
        class="Input--vTop"
        data-name="notes"
        onChange={handleNotesChange}
        onBlurTitle={() => onBlurElement(null, 'notes_key')}
        onBlurDesc={() => onBlurElement(null, 'notes_value')}
        onAddNew={() => onBlurElement(null, 'notes_add_new')}
      />
    </React.Fragment>
  );
};

function PaymentMethod({
  avlblMethods,
  mandateMethod,
  trackClickPaymentMethod,
  onBlurElement,
}) {
  if (avlblMethods.length) {
    return (
      <Input.Radio
        required
        label="Payment Method"
        name="mandateMethod"
        options={avlblMethods}
        defaultValue={mandateMethod}
        onChange={trackClickPaymentMethod}
        data-name="method"
        onBlur={onBlurElement}
        class="Input--vTop"
        description="Method to be used for Registration Link"
      />
    );
  }

  return <PaymentMethodPlaceHolder content={avlblMethods[0].label} />;
}

function PaymentMethodPlaceHolder({ content }) {
  return (
    <div class="Input Input--vTop">
      <div class="Input-label">Payment Method</div>
      <div class="Input-content">{content}</div>
    </div>
  );
}

export { checkIfAmount };
