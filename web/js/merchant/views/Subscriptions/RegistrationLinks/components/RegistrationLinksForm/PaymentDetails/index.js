/* eslint-disable react/jsx-no-undef */
/* eslint-disable react/jsx-pascal-case */
import { PowerSelect } from 'react-power-select';
import Input from 'common/new-ui/Input';

import DocsLink from 'merchant/components/DocsLink';

import AmountScreen from './Amount';
import NACH from './NACH';
import Emandate from './Emandate';
import UPI from './UPI';
import { checkIfAmount, getPaymentMethodOptions, DOCUMENTATION_LINKS } from './utils';

export default (props) => {
  const {
    showNACHAccountTypes,
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
    notes,
    isEsignEnabled,
  } = props;
  return (
    <>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
        handlePaymentMethod={handlePaymentMethod}
        onBlurElement={onBlurElement}
        isEsignEnabled={isEsignEnabled}
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
        <AmountScreen
          amount={amount}
          onBlurElement={onBlurElement}
          placeholder="Minimum 1"
          amountValidator={amountValidator}
        />
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
        />
      )}

      {isNACHPayment && (
        <NACH
          showNACHAccountTypes={showNACHAccountTypes}
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
        defaultValue={notes}
      />
    </>
  );
};

function getDocLinkForSelectedPayment(method) {
  if (!method || method === 'nach') return null;
  const { title, href } = DOCUMENTATION_LINKS[method];
  if (!title || !href) return null;
  return <DocsLink url={href} title={title} style={{ padding: '4px 0px' }} />;
}

function PaymentMethod({
  avlblMethods,
  mandateMethod,
  handlePaymentMethod,
  onBlurElement,
  isEsignEnabled,
}) {
  if (avlblMethods.length) {
    const optionsList = getPaymentMethodOptions(isEsignEnabled);
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
            optionComponent={(params) => paymentMethodOption(params, optionsList)}
            selectedOptionComponent={(params) => paymentMethodSelected(params, optionsList)}
            onBlur={onBlurElement}
            selected={mandateMethod}
            onChange={handlePaymentMethod}
          />
          {getDocLinkForSelectedPayment(mandateMethod)}
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

function paymentMethodSelected({ option }, optionsList) {
  const { icon, desc, method } = optionsList[option];

  return (
    <div class="PaymentMethodOption">
      <i class={`i i-${icon}`} />
      <span class="method">{method}</span>
      <span class="desc">{desc}</span>
    </div>
  );
}

function paymentMethodOption({ option }, optionsList) {
  const { icon, desc, method } = optionsList[option];

  return (
    <div class="PaymentMethodOption">
      <i class={`i i-${icon}`} />
      <div className="title">
        <span class="method">{method}</span>
        <div className="desc">{desc}</div>
      </div>
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
  return null;
}
