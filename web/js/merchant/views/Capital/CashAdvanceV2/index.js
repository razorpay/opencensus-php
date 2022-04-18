import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import Card from './Card';
import { CASH_ADVANCE_CONTENT, CASH_ADVANCE_ADVANTAGES } from './constants';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import CashAdvanceRedirectionModal from './RedirectionModal';
import { trackLandingonCashAdvanceV2, trackApplyNow, trackContinueNow } from './TrackEvents';
import './CashAdvance.styl';

const CashAdvance = (props) => {
  const { showApplyNow, fnOpenModal, fnCloseModal } = props;
  const buttonText = showApplyNow ? 'Apply Now!' : 'Continue Application';

  useEffect(() => {
    trackLandingonCashAdvanceV2();
  }, []);

  const handleRedirection = () => {
    showApplyNow ? trackApplyNow() : trackContinueNow();
    fnOpenModal({
      size: 'medium',
      component: <CashAdvanceRedirectionModal onClose={fnCloseModal} />,
    });
  };

  return (
    <div className="cash-advance-v2-wrapper">
      <div className="heading">{CASH_ADVANCE_CONTENT.heading}</div>
      <div className="divider" />
      <div className="sub-heading">{CASH_ADVANCE_CONTENT.subheading}</div>
      <button className="btn btn-primary" onClick={handleRedirection}>
        {buttonText}
      </button>
      <div className="description">{CASH_ADVANCE_CONTENT.description}</div>
      <div className="card-section-wrapper">
        {CASH_ADVANCE_ADVANTAGES.map((item, index) => {
          return (
            <Card
              key={index}
              title={item.title}
              subTitle={item.subTitle}
              imagePath={item.imagePath}
            />
          );
        })}
      </div>
    </div>
  );
};

CashAdvance.defaultProps = {
  showApplyNow: true,
};

CashAdvance.propTypes = {
  showApplyNow: PropTypes.bool,
};

const mapDispatchToProps = (dispatch) => ({
  fnOpenModal: bindActionCreators(openModal, dispatch),
  fnCloseModal: bindActionCreators(closeModal, dispatch),
});

export default connect(null, mapDispatchToProps)(CashAdvance);
