import Input from 'common/new-ui/Input';
import Banner from 'common/ui/Banner';

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
    formReference1,
    formReference2,
    currentSelectedMethod,
  } = props;

  let bannerText;

  if (currentSelectedMethod.card) {
    bannerText =
      'Registeration links will not be authorised for Yes Bank Accounts and Cards';
  }

  return (
    <React.Fragment>
      <PaymentMethod
        mandateMethod={mandateMethod}
        avlblMethods={avlblMethods}
        trackClickPaymentMethod={trackClickPaymentMethod}
      />

      {bannerText && (
        <Banner>
          {bannerText}{' '}
          <a
            class="highlight"
            target="_blank"
            href="https://lp.razorpay.com/unregistered-businesses-faqs-0"
          >
            Know more
            <i class="i i-external-link" style={{ marginLeft: '5px' }} />
          </a>
        </Banner>
      )}

      {isEmandatePayment && (
        <Emandate
          emandateBanks={emandateBanks}
          skipBankDetails={skipBankDetails}
          bankName={bankName}
          accountType={accountType}
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
          formReference1={formReference1}
          formReference2={formReference2}
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
