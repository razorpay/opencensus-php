/* eslint-disable react/jsx-pascal-case */
import { Amount } from '@razorpay/blade/components';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';
import { PowerSelect } from 'react-power-select';

import { useI18Service } from 'common/i18';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  DEFAULT_UPI_LIMIT,
  DEFAULT_TOUCH_N_GO_MIN_LIMIT,
  PAYMENT_METHODS,
  DEFAULT_TOUCH_N_GO_MAX_LIMIT,
} from 'merchant/views/Subscriptions/constants';

import AmountScreen from './Amount';
import Emandate from './Emandate';
import NACH from './NACH';
import UPI from './UPI';
import { checkIfAmount, getPaymentMethodOptions, DOCUMENTATION_LINKS } from './utils';

export default function PaymentDetailsForm(props) {
  const {
    notes,
    amount,
    bankName,
    accountType,
    availableMethods,
    isUPIPayment,
    isCardPayment,
    mandateMethod,
    emandateBanks,
    isNACHPayment,
    onBlurElement,
    isNachFormAval,
    formReference1,
    formReference2,
    skipBankDetails,
    isEsignEnabled,
    bankAccountIFSC,
    showAmountField,
    beneficiaryName,
    isEmandatePayment,
    handleNotesChange,
    bankAccountNumber,
    handlePaymentMethod,
    showNACHAccountTypes,
    isTPVEnabledMerchant,
    trackReceivedNACHForm,
    trackNACHToolTipHover,
    currency,
    isWalletPayment,
  } = props;
  let recurringMethods = availableMethods;
  // for TPV enabled Merchant, only emandate and UPI should be enabled
  if (isTPVEnabledMerchant) {
    recurringMethods = recurringMethods.filter((method) => ['emandate', 'upi'].includes(method));
  }
  return (
    <>
      <PaymentMethod
        mandateMethod={mandateMethod}
        availableMethods={recurringMethods}
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
          amountValidator={(value) => amountValidator(value, { currency })}
          currency={currency}
        />
      )}

      {isWalletPayment && (
        <AmountScreen
          amount={amount}
          onBlurElement={onBlurElement}
          placeholder={`Minimum ${DEFAULT_TOUCH_N_GO_MIN_LIMIT} ${currency}`}
          amountValidator={(value) => amountValidator(value, { isWalletPayment, currency })}
          currency={currency}
        />
      )}

      {isUPIPayment && (
        <UPI
          amount={amount}
          placeholder="Max 200000"
          onBlurElement={onBlurElement}
          amountValidator={(value) => amountValidator(value, { currency })}
          bankAccountIFSC={bankAccountIFSC}
          beneficiaryName={beneficiaryName}
          bankAccountNumber={bankAccountNumber}
          isTPVEnabledMerchant={isTPVEnabledMerchant}
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
        labelClass="Input-label"
        data-name="notes"
        onChange={handleNotesChange}
        onBlurTitle={() => onBlurElement(null, 'notes_key')}
        onBlurDesc={() => onBlurElement(null, 'notes_value')}
        onAddNew={() => onBlurElement(null, 'notes_add_new')}
        defaultValue={notes}
      />
    </>
  );
}

function getDocLinkForSelectedPayment(method) {
  if (!method || method === PAYMENT_METHODS.NACH || method === PAYMENT_METHODS.WALLET) return null;
  const { title, href } = DOCUMENTATION_LINKS[method];
  if (!title || !href) return null;
  return <DocsLink url={href} title={title} style={{ padding: '4px 0px' }} />;
}

function PaymentMethod({
  availableMethods,
  onBlurElement,
  mandateMethod,
  isEsignEnabled,
  handlePaymentMethod,
}) {
  const { isConfigTagEnabled } = useI18Service();
  if (availableMethods.length) {
    const optionsList = getPaymentMethodOptions(isEsignEnabled);
    return (
      <div class="Input">
        <div class="Input-label">Payment Method</div>

        <div class="Input-content">
          <PowerSelect
            showClear={false}
            searchEnabled={false}
            name="mandateMethod"
            options={availableMethods}
            placeholder="Method to be used for Registration Link "
            optionComponent={(params) => paymentMethodOption(params, optionsList)}
            selectedOptionComponent={(params) => paymentMethodSelected(params, optionsList)}
            onBlur={onBlurElement}
            selected={mandateMethod}
            onChange={handlePaymentMethod}
          />
          <ShowWhen
            additionalCondition={() => !isConfigTagEnabled('subscriptions.supported_bank_links')}
          >
            {getDocLinkForSelectedPayment(mandateMethod)}
          </ShowWhen>
        </div>
      </div>
    );
  }

  return <PaymentMethodPlaceHolder content={availableMethods[0]} />;
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

export function amountValidator(value, { isWalletPayment, currency }) {
  const validation = checkIfAmount(value);

  if (validation) {
    return validation;
  }

  if (isWalletPayment) {
    const amountValue = convertToMajorUnit(DEFAULT_TOUCH_N_GO_MAX_LIMIT, { currency });
    if (value > amountValue) {
      return (
        <>
          Amount should not be greater than{' '}
          <Amount
            color="feedback.text.negative.intense"
            value={amountValue}
            type="body"
            size="small"
            currency={currency}
          />
        </>
      );
    }

    return null;
  }

  if (value > DEFAULT_UPI_LIMIT) {
    return (
      <>
        Amount should not be greater than{' '}
        <Amount
          color="feedback.text.negative.intense"
          value={DEFAULT_UPI_LIMIT}
          type="body"
          size="small"
          currency={currency}
        />
      </>
    );
  }
  return null;
}
