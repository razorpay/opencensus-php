import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';
import { NOOP } from 'merchant/views/Capital/Loans/constants';
import LegalSignIcon from '../../../../../../icons/merchant/legal.svg';
import Gromor from '../../../../../../icons/merchant/gromor.svg';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import moment from 'moment';
import GromorRedirectConfirmation from 'merchant/views/Capital/components/Modals/GromorRedirectConfirmation';
import { openModal } from 'merchant_common/reducers/modals';
import { getDateSuffix } from 'merchant/views/Capital/utils';

const GromorAgreementModal = ({
  openModal,
  onClose,
  withdrawalConfigurationDetails,
  eSignUrl,
  name,
  email_id,
  due_at,
}) => {
  const timeStamp = new Date(due_at);
  var monthName = moment(timeStamp).format('MMMM');
  var date = timeStamp.getDate();

  const {
    configuration: { interest = 0, credit_limit = 0, end_day_limit = 0 } = {},
  } = withdrawalConfigurationDetails;

  const RedirectToLeegality = () => {
    openModal({
      component: (
        <GromorRedirectConfirmation
          onClose={onClose}
          eSignUrl={eSignUrl}
          name={name}
          email_id={email_id}
        />
      ),
      className: 'leegality-redirect-wrapper',
    });
  };

  return (
    <div className="gromor-agreement-wrapper">
      <div className="title-wrapper flex">
        <img src={LegalSignIcon} alt="legal-sign" className="gromor-legal-sign-icon" />
        <div className="title-container">
          <p className="title">Update in lender agreement</p>
        </div>
        <button className="close" onClick={onClose}>
          <i className="i i-close" />
        </button>
      </div>
      <p className="agreement-info text">
        {`We are updating our terms & conditions to a new lending partner. Please review & sign the new loan agreement by <${monthName}  ${date}${getDateSuffix(
          due_at,
        )}>.`}
      </p>
      <p className="credit-title text">You continue to enjoy the following credit offer</p>
      <div className="credit-info-container">
        <div className="credit-limit-container flex">
          <p className="credit text">Credit Limit</p>
          <strong>
            <Amount value={credit_limit} currency="INR" parentQuerySelector={`.Modal--medium`} />
          </strong>
        </div>
        <div className="flex details-wrapper">
          <div className="rate-of-interest border-right">
            <div className="details-heading">Rate of Interest</div>
            <p className="detail">
              <span>{parseInt(interest) / 100}</span>% per day
            </p>
          </div>
          <div className="tenure border-right">
            <div className="details-heading">Tenure</div>
            <p className="detail">
              upto <span> {end_day_limit} days</span>
            </p>
          </div>
          <div className="finance">
            <div className="details-heading">Financed by</div>
            <img src={Gromor} alt="gromor" className="gromor-icon" />
          </div>
        </div>
      </div>

      <Button.Primary className="cta text" onClick={RedirectToLeegality}>
        {'View & Sign Agreement'}
        <i className="i i-chevron-right" />
      </Button.Primary>
    </div>
  );
};

GromorAgreementModal.propTypes = {
  onClose: PropTypes.func,
  due_at: PropTypes.string,
};

GromorAgreementModal.defaultProps = {
  onClose: NOOP,
  due_at: '',
};

const mapDispatchToProps = {
  openModal,
};

export default withRouter(connect(null, mapDispatchToProps)(GromorAgreementModal));
