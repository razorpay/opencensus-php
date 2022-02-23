import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { isMobileDevice } from 'merchant/components/Home/data';
import { sendDataToSalesForce } from '../../utils/common-api';
import { getUser } from '../../../merchant/store';

const SubmissionSuccessfull = ({ handleClose }) => {
  return (
    <div className="rxca-submit-finish-modal">
      <div className="header">
        <div className="title">
          Congratulations! We're processing your request for a Current Account with RazorpayX.
        </div>
        <button type="button" className="close" onClick={handleClose}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="description removeBorderBottom">
        <p>
          Our banking experts will be reaching out to you shortly. In the meantime, we highly
          recommend you keep the required documents for creating a current account handy.
        </p>
        <a
          href="https://razorpay.com/docs/razorpayx/current-account/"
          target="_blank"
          rel="noreferrer noopener"
        >
          <Button.Primary className="btn btn-primary" type="button">
            View Documents Required
          </Button.Primary>
        </a>
      </div>
    </div>
  );
};

interface buttonTypes {
  label: string;
  id: string;
  url: string;
  style: string;
  target: boolean;
  reload: boolean;
}
interface l2_contentType {
  bg_image: string;
  m_image: string;
  buttons: Array<buttonTypes>;
}
const CarouselModal = ({
  modalClose,
  modalOpen,
  l2_content,
  history,
  tracking_data,
  banner_id,
  bannerOrder,
  bannerInitiatedEvent,
  bannerCardImp,
}): React.ReactElement => {
  const { bg_image, m_image, buttons = [] } = l2_content;

  // eslint-disable-next-line consistent-return
  const handleFirstCta = ({ url, target, reload }) => {
    if (url) {
      if (target) {
        window.open(url, '_blank');
      } else if (reload) {
        window.open(url);
      } else {
        history.push(url);
      }
      modalClose();
    } else {
      const user = getUser();
      const { IlBv2LyCzsyZqI: { variables: { result = '' } = {} } = {} } = user?.splitz_experiments; // TODO: remove this hardcode exp id once splitz evaluate api is ready

      return sendDataToSalesForce(
        {
          Campaign_ID: result ? result : 'Platform Growth - CA Awareness',
          product_name: 'Current_Account',
        },
        user,
      ).then((resp) => {
        const { success } = resp;
        if (success) {
          modalOpen({
            component: <SubmissionSuccessfull handleClose={modalClose} />,
          });
        }
      });
    }

    bannerInitiatedEvent(
      'carousel_banner_notification1_cta2',
      banner_id,
      bannerOrder,
      tracking_data,
    );
  };

  const handleModalClose = () => {
    modalClose();
    bannerCardImp('carousel_banner_not_interested', banner_id, bannerOrder);
  };
  return (
    <div className="carouselModal" id="carouselModal">
      <button type="button" className="xcaHeader__close" onClick={handleModalClose}>
        <i className="i i-close" />
      </button>
      <div className="carouselModal__image">
        <img src={isMobileDevice() ? m_image : bg_image} />
      </div>
      <div className="modalCta">
        {buttons.map(({ label, id: ctaId, url, style, target, reload }, index) => (
          <AsyncBtn.Primary
            key={ctaId}
            className={`btn ${style ? style : 'primaryCta'}`}
            onClick={index === 0 ? () => handleFirstCta({ url, target, reload }) : handleModalClose}
          >
            {label}
          </AsyncBtn.Primary>
        ))}
      </div>
    </div>
  );
};

export default withRouter<any, any>(
  connect(null, {
    modalClose: closeModal,
    modalOpen: openModal,
  })(CarouselModal),
);
