import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

import CardsCollection from './Card';
import {
  CASH_ADVANCE_CONTENT,
  CASH_ADVANCE_ADVANTAGES,
  APPLICATION_DISABLED_STATES,
  APPLICATION_STATE_SEQUENCE_STAGES,
  APPLICATION_NAVIGATION_CONFIG,
  APPLICATION_STATES,
  INITIAL_APPLICATION_STATE,
  CASH_ADVANCE_LINK,
  CASH_ADVANCE_WITHDRAWAL_ROUTE,
  FAQ_URL,
} from './constants';
import { trackLandingonCashAdvanceV2, trackApplyNow, trackApplicationStatus } from './TrackEvents';
import './CashAdvance.styl';
import { getApplicationByParamData } from 'merchant/reducers/capital';
import { getFormattedApplicationData } from './utils';
import RightSideNavigation from './RightSideTracking';
import StepComponent from './StepComponent';
import DisabledStateComponent from './DisabledState';

const CashAdvance = (props) => {
  const { showApplyNow, applicationId } = props;
  const [applicationData, setApplicationData] = useState(INITIAL_APPLICATION_STATE);
  const currentNavigationStatus = applicationData?.navigation?.current;

  const getApplicationData = async () => {
    const response = await getApplicationByParamData({
      application_id: applicationId,
    });
    const { data = {} } = response;
    const payload = getFormattedApplicationData(data);
    setApplicationData(payload);
  };

  useEffect(() => {
    trackLandingonCashAdvanceV2();
  }, []);

  useEffect(() => {
    if (!showApplyNow && applicationId) {
      getApplicationData();
    }
  }, [applicationId]);

  const handleRedirection = (objectName = '') => {
    showApplyNow ? trackApplyNow() : trackApplicationStatus(objectName);
    if (currentNavigationStatus === APPLICATION_STATES.STATE_COMPLETED) {
      props.history.push(CASH_ADVANCE_WITHDRAWAL_ROUTE);
    } else {
      window.open(CASH_ADVANCE_LINK, '_self');
    }
  };

  const getCurrentStep = () => {
    const index = Object.keys(APPLICATION_STATE_SEQUENCE_STAGES).findIndex((stage) =>
      APPLICATION_STATE_SEQUENCE_STAGES[stage]?.includes(currentNavigationStatus),
    );
    return index === -1 ? 1 : index + 1;
  };

  const applicationStatus = applicationData?.navigation?.applicationStatus;
  const disabledApplicationExists = currentNavigationStatus in APPLICATION_DISABLED_STATES;
  const totalSteps = APPLICATION_NAVIGATION_CONFIG.length;
  const currentStep = getCurrentStep();

  return (
    <div className={`cash-advance-v2-wrapper ${showApplyNow ? 'default' : 'plain'}`}>
      <div className="cash-advance-v2-wrapper-flex">
        <div className="left-side">
          <div className="heading">{CASH_ADVANCE_CONTENT.heading}</div>
          <div className="divider" />
          <div className={`sub-heading ${!showApplyNow ? 'with-border' : ''}`}>
            {CASH_ADVANCE_CONTENT.subheading}
          </div>
          {showApplyNow && (
            <button className="btn btn-primary" onClick={handleRedirection}>
              Apply Now!
            </button>
          )}
          {showApplyNow && <CardsCollection cardRecords={CASH_ADVANCE_ADVANTAGES} />}
        </div>
        {!showApplyNow && (
          <div className="right-side">
            <div>
              <div className="heading-flex">
                <div>
                  <div className="right-heading">Your Cash Advance Application</div>
                  <div className="right-sub-heading">Cash_ID{applicationId}</div>{' '}
                </div>
                {!disabledApplicationExists && (
                  <StepComponent totalSteps={totalSteps} currentStep={currentStep} />
                )}
              </div>
              <div className="application-status">
                {disabledApplicationExists ? (
                  <DisabledStateComponent
                    currentNavigationStatus={currentNavigationStatus}
                    handleCtaClick={handleRedirection}
                  />
                ) : (
                  <RightSideNavigation
                    applicationStatus={applicationStatus}
                    currentNavigationStatus={currentNavigationStatus}
                    handleCtaClick={handleRedirection}
                  />
                )}
              </div>
            </div>
            <div className="right-footer">
              <div>
                <i className="fa fa-question-circle-o" />
                <a target="_blank" href={FAQ_URL} rel="noreferrer noopener">
                  Show FAQ
                </a>
              </div>
              <div>
                <img src={`${window.cdnBaseUrl}/logo_invert.svg`} alt="razorpay-logo" />
              </div>
            </div>
          </div>
        )}
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

export default withRouter(CashAdvance);
