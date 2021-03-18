import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

import quickGuide, {
  setQuickGuideIsClosedInLocalStorage,
  getQuickGuideIsClosedFromLocalStorage,
} from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';

const { done, locked, active, loading } = PossibleStatuses;

@quickGuide({
  feature: RZPFeatures.QR_CODES,
  data_points: ['qr_codes'],
})
export default class QRCodesQuickGuide extends React.Component {
  render() {
    const { qrCodeStatus, paymentReceiveStatus } = getStatus(this.props.qr_codes);

    const closeBtn = getCloseBtn(paymentReceiveStatus === done, this.props.onClickClose);

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide activeStep={activeStep} class="QRCode" title={Title} closeBtn={closeBtn}>
        <QuickGuideStep
          status={qrCodeStatus}
          step="QRCode"
          feature={RZPFeatures.QR_CODES}
          {...getQuickGuideData.QRCodes(qrCodeStatus)}
        />

        <QuickGuideStep
          status={paymentReceiveStatus}
          step="PaymentReceive"
          feature={RZPFeatures.QR_CODES}
          {...getQuickGuideData.ReceivePayments(paymentReceiveStatus)}
        />
      </QuickStepGuide>
    );
  }
}

const Title = <QuickGuideTitle />;

function getCloseBtn(isCompleted, onClickClose) {
  return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={onClickClose} />;
}

export const getQRCodeQuickGuideIsClosed = (props) => {
  return getQuickGuideIsClosedFromLocalStorage(RZPFeatures.QR_CODES);
};

const getStatus = ({ items, loading }) => {
  let qrCodeStatus = loading;
  let paymentReceiveStatus = loading;

  if (loading) {
    return {
      qrCodeStatus,
      paymentReceiveStatus,
    };
  }

  if (items.length) {
    qrCodeStatus = done;
    paymentReceiveStatus = active;

    items.forEach((account) => {
      if (account.amount_paid > 0) {
        paymentReceiveStatus = done;

        return false;
      }
    });
  } else {
    qrCodeStatus = active;
    paymentReceiveStatus = locked;
  }

  return {
    qrCodeStatus,
    paymentReceiveStatus,
  };
};
