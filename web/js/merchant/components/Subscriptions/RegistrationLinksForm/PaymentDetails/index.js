import Input from 'component/Input';

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
  } = props;

  return (
    <React.Fragment>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
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
          nachBanks={emandateBanks} // Update banks list to nachBanks
          bankName={bankName}
          accountType={accountType}
          bankAccountIFSC={bankAccountIFSC}
          beneficiaryName={beneficiaryName}
          bankAccountNumber={bankAccountNumber}
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

function PaymentMethod({ avlblMethods, mandateMethod }) {
  if (avlblMethods.length) {
    return (
      <Input.Radio
        required
        label="Payment Method"
        name="mandateMethod"
        options={avlblMethods}
        defaultValue={mandateMethod}
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
