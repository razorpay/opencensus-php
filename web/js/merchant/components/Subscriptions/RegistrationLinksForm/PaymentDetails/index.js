import Input from 'common/new-ui/Input';

import Card from './Card';
import NACH from './NACH';
import Emandate from './Emandate';
import { checkIfAmount } from './utils';

export default props => {
  const {
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
  } = props;

  return (
    <React.Fragment>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
        trackClickPaymentMethod={trackClickPaymentMethod}
      />

      {isEmandatePayment && (
        <Emandate
          emandateBanks={emandateBanks}
          skipBankDetails={skipBankDetails}
          bankName={bankName}
          bankAccountIFSC={bankAccountIFSC}
          beneficiaryName={beneficiaryName}
          bankAccountNumber={bankAccountNumber}
        />
      )}

      {isCardPayment && <Card amount={amount} />}

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
        />
      )}

      <Input.PairList
        name="notes"
        label="Internal Notes"
        class="Input--vTop"
        onChange={handleNotesChange}
      />
    </React.Fragment>
  );
};

function PaymentMethod({
  avlblMethods,
  mandateMethod,
  trackClickPaymentMethod,
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
