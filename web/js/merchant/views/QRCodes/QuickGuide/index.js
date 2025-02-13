/* eslint-disable babel/new-cap */
import React from 'react';
import { RZPFeatures, PossibleStatuses } from 'merchant/helpers/data';

import quickGuide, { getQuickGuideIsClosedFromLocalStorage } from 'merchant/components/QuickGuide';
import QuickStepGuide, {
  QuickGuideStep,
  QuickGuideTitle,
  QuickGuideCloseBtn,
} from 'merchant/components/QuickGuide/QuickStepGuide';

import { getQuickGuideData } from './data';
import { compose } from 'redux';

const { done, locked, active } = PossibleStatuses;

const Title = <QuickGuideTitle />;

class QRCodesQuickGuide extends React.Component {
  render() {
    const { qrCodeStatus, paymentReceiveStatus } = getStatus(this.props.qr_codes);

    const closeBtn = getCloseBtn(paymentReceiveStatus === done, this.props.onClickClose);

    const { className = '' } = this.props;

    let activeStep = 0;

    if (paymentReceiveStatus === done) {
      activeStep = 1;
    }

    return (
      <QuickStepGuide
        activeStep={activeStep}
        className={`QRCode ${className}`}
        title={Title}
        closeBtn={closeBtn}
      >
        <QuickGuideStep
          status={qrCodeStatus}
          step={`QRCode`}
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

export default compose(
  quickGuide({
    feature: RZPFeatures.QR_CODES,
    data_points: ['qr_codes'],
  }),
)(QRCodesQuickGuide);

function getCloseBtn(isCompleted, onClickClose) {
  return <QuickGuideCloseBtn isCompleted={isCompleted} onClick={onClickClose} />;
}

export const getQRCodeQuickGuideIsClosed = () => {
  return getQuickGuideIsClosedFromLocalStorage(RZPFeatures.QR_CODES);
};

function getStatus({ items, loading }) {
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

    // eslint-disable-next-line consistent-return
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
}
