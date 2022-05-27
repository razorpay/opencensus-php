import '../styles/ApplicationBanner.styl';
import React, { useEffect, Suspense } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import Loader from 'common/ui/Loader';
import lazy from 'merchant/routes/LazyLoader';
import LoaderDots from 'common/ui/LoaderDots';
import { APPLICATION_STATES } from 'merchant/views/Capital/Loans/constants';
import {
  trackUnlockMoreFundsBanner,
  trackKnowMoreCtaClick,
  trackFewStepsLeftBanner,
  trackContinueApplyingCtaClick,
} from '../analytics';
import { getExpiresIn } from '../utils';
import { CASH_ADVANCE_BASE_URL } from '../../CashAdvance/constants';

const ProgressModal = lazy(() =>
  import(
    /* webpackChunkName: "CashAdvanceNudgesProgressModal" */ 'merchant/views/Capital/CashAdvanceNudges/components/ProgressModal'
  ),
);

const Banner = ({ status, handleClick, expiresIn, progress }) => {
  const headerClassName = progress ? 'progress-banner heading-wrapper' : 'heading-wrapper';
  const screen = window.location.pathname.includes('settlements')
    ? 'PG Dashboard | Settlements | Instant Settlements Modal'
    : 'PG Home | Instant Settlements Modal';
  const commonProperties = {
    screen,
    status,
    expiresIn,
  };

  useEffect(() => {
    if (progress) {
      trackFewStepsLeftBanner(commonProperties);
    } else {
      trackUnlockMoreFundsBanner({
        screen,
      });
    }
  }, [screen]);

  const handleButtonClick = () => {
    if (progress) {
      trackContinueApplyingCtaClick(commonProperties);
    } else {
      trackKnowMoreCtaClick({
        screen,
      });
    }
    handleClick();
  };

  return (
    <div className="loc-nudge-application-banner">
      <div className={headerClassName}>
        {progress ? (
          <>
            <h6 className="banner-heading">Few steps remaining</h6>
            <span>expires in {expiresIn} days</span>
          </>
        ) : (
          <h6 className="banner-heading">Need more money?</h6>
        )}
      </div>

      <div className="unlock-wrapper">
        <div className="unlock-heading">
          Unlock a <span>Cash Advance</span> of upto
        </div>
        <div className="unlock-amount">
          <span>&#8377;</span>10,00,000
        </div>
      </div>

      <button className="banner-button" onClick={handleButtonClick}>
        {progress ? 'Continue Applying' : 'Apply Now'}
      </button>
    </div>
  );
};

Banner.propTypes = {
  status: PropTypes.string,
  handleClick: PropTypes.func,
  expiresIn: PropTypes.number,
  progress: PropTypes.bool,
};

const ApplicationBanner = (props) => {
  const { history, applications, openModal, closeModal } = props;

  const application = applications?.data?.applications?.[0];
  const isLoading = applications?.loading;
  const expiresIn = getExpiresIn(application?.created_at);

  const handleClick = () => {
    const isValidStatus =
      application?.status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW ||
      application?.status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS;
    if (isValidStatus) {
      openModal({
        component: (
          <Suspense fallback={<Loader />}>
            <ProgressModal status={application?.status} closeModal={closeModal} />
          </Suspense>
        ),
        size: 'medium',
        disableClose: true,
      });
    } else {
      history.push(CASH_ADVANCE_BASE_URL);
    }
  };

  const renderContent = () => {
    const applicationInProgress =
      application &&
      !(
        application?.status === APPLICATION_STATES.RZP_REJECTED ||
        application?.status === APPLICATION_STATES.CLOSED
      );
    if (applicationInProgress) {
      return (
        <Banner
          progress={true}
          status={application?.status}
          handleClick={handleClick}
          expiresIn={expiresIn}
        />
      );
    } else {
      return <Banner handleClick={handleClick} />;
    }
  };

  if (isLoading) {
    return (
      <div className="custom-loader">
        <LoaderDots />
      </div>
    );
  }
  return renderContent();
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  products: state.loanApplicationDetails.products,
  applications: state.loanApplicationDetails.applications,
});

export default withRouter(
  connect(mapStateToProps, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
  })(ApplicationBanner),
);
