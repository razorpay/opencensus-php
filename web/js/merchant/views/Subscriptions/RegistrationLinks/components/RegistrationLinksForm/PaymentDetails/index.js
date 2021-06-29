import { PowerSelect } from 'react-power-select';

import Input from 'common/new-ui/Input';

import AmountScreen from './Amount';
import NACH from './NACH';
import Emandate from './Emandate';
import UPI from './UPI';
import { checkIfAmount } from './utils';

export default (props) => {
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
    trackReceivedNACHForm,
    trackNACHToolTipHover,
    formReference1,
    formReference2,
    onBlurElement,
    handlePaymentMethod,
    isUPIPayment,
  } = props;

  return (
    <React.Fragment>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
        handlePaymentMethod={handlePaymentMethod}
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

      {isCardPayment && (
        <AmountScreen amount={amount} onBlurElement={onBlurElement} placeholder="0.00" />
      )}

      {isUPIPayment && (
        <UPI
          showTPV={props.showTPV}
          isTPVEnabled={props.isTPVEnabled}
          amount={amount}
          onBlurElement={onBlurElement}
          placeholder="Max 200000"
          amountValidator={amountValidator}
          handleTPV={props.handleTPV}
          bankAccountNumber={props.bankAccountNumber}
          bankAccountIFSC={props.bankAccountIFSC}
          handleTPV={props.handleTPV}
        />
      )}

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

function PaymentMethod({ avlblMethods, mandateMethod, handlePaymentMethod, onBlurElement }) {
  if (avlblMethods.length) {
    return (
      <div class="Input">
        <div class="Input-label">Payment Method</div>

        <div class="Input-content">
          <PowerSelect
            showClear={false}
            searchEnabled={false}
            name="mandateMethod"
            options={avlblMethods}
            placeholder="Method to be used for Registration Link "
            optionComponent={PaymentMethodOption}
            selectedOptionComponent={PaymentMethodOption}
            onBlur={onBlurElement}
            selected={mandateMethod}
            onChange={handlePaymentMethod}
          />
        </div>
      </div>
    );
  }

  return <PaymentMethodPlaceHolder content={avlblMethods[0]} />;
}

function PaymentMethodPlaceHolder({ content }) {
  return (
    <div class="Input Input--vTop">
      <div class="Input-label">Payment Method</div>
      <div class="Input-content">{content}</div>
    </div>
  );
}

const PAYMENT_METHODS_OPTIONS = {
  card: {
    method: 'Card',
    icon: 'card',
    desc: 'Through Credit and Debit Cards',
  },
  nach: {
    method: 'NACH',
    icon: 'bank',
    desc: 'Through a NACH Form',
  },
  emandate: {
    method: 'Emandate',
    icon: 'bank',
    desc: 'Through NetBanking Details',
  },
  upi: {
    method: 'UPI',
    icon: 'upi',
    desc: 'Through UPI mandates',
  },
};

function PaymentMethodOption({ option }) {
  const { icon, desc, method } = PAYMENT_METHODS_OPTIONS[option];

  return (
    <div class="PaymentMethodOption">
      <i class={`i i-${icon}`} /> <span class="method">{method}</span> {desc}
    </div>
  );
}

export { checkIfAmount };

function amountValidator(value) {
  const validation = checkIfAmount(value);

  if (validation) {
    return validation;
  }

  if (value > 200000) {
    return 'Amount should not be greater than 200000';
  }
}
