import '../styles/ProgressModal.styl';
import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import { APPLICATION_STATES } from 'merchant/views/Capital/Loans/constants';
import Timeline from './Timeline';
import {
  trackBackToDashboardClick,
  trackDocsUnderReviewModal,
  trackDocsUnderReviewModalClose,
  trackEvaluatingOfferModal,
  trackEvaluatingOfferModalClose,
} from '../analytics';
import { getTimelineData } from '../utils';

const ProgressModal = (props) => {
  const { status, closeModal } = props;
  const isSettlementScreen = window.location.pathname.includes('settlements');
  const { items, img } = getTimelineData(status);

  useEffect(() => {
    if (status) {
      if (status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS) {
        trackEvaluatingOfferModal({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Offer Evaluation Modal'
            : 'PG Home | Offer Evaluation Modal',
        });
      } else if (status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW) {
        trackDocsUnderReviewModal({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Document Review Modal'
            : 'PG Home | Document Review Modal',
        });
      }
    }
  }, [status]);

  const handleClick = () => {
    closeModal();
    if (status) {
      if (status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS) {
        trackBackToDashboardClick({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Offer Evaluation Modal'
            : 'PG Home | Offer Evaluation Modal',
        });
      } else if (status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW) {
        trackBackToDashboardClick({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Document Review Modal'
            : 'PG Home | Document Review Modal',
        });
      }
    }
  };

  const handleClose = () => {
    closeModal();
    if (status) {
      if (status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS) {
        trackEvaluatingOfferModalClose({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Offer Evaluation Modal'
            : 'PG Home | Offer Evaluation Modal',
        });
      } else if (status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW) {
        trackDocsUnderReviewModalClose({
          screen: isSettlementScreen
            ? 'PG Dashboard | Settlements | Document Review Modal'
            : 'PG Home | Document Review Modal',
        });
      }
    }
  };

  return (
    <div className="loc-nudges-progress-modal">
      <button className="close-btn" type="button" onClick={handleClose}>
        <i className="i i-close" />
      </button>

      <img
        className="rocket-img"
        src={`${window.cdnBaseUrl}/static/assets/capital/loc_nudges/${img}.png`}
        alt="is++"
      />

      <div className="bottom-section">
        <div className="title">Your Cash Advance Application</div>
        <Timeline items={items} />
        <Button.Primary className="back-btn" onClick={handleClick}>
          Back to Dashboard
        </Button.Primary>
      </div>
    </div>
  );
};

export default ProgressModal;

ProgressModal.propTypes = {
  status: PropTypes.string.isRequired,
  closeModal: PropTypes.func.isRequired,
};
