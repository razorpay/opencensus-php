import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';

import CardsCollection from './Card';
import {
  CASH_ADVANCE_ADVANTAGES,
  APPLICATION_DISABLED_STATES,
  APPLICATION_STATE_SEQUENCE_STAGES,
  APPLICATION_NAVIGATION_CONFIG,
  APPLICATION_STATES,
  INITIAL_APPLICATION_STATE,
  PRODUCT_CONFIG,
} from './constants';
import { isLOCEMIProduct } from 'merchant/views/Capital/utils';
import { trackLandingonCashAdvanceV2, trackApplyNow, trackApplicationStatus } from './TrackEvents';
import './CashAdvance.styl';
import { getApplicationByParamData } from 'merchant/reducers/capital';
import { CAPITAL_PRODUCT_CODES } from 'merchant/views/Capital/Loans/constants';
import { getFormattedApplicationData } from './utils';
import RightSideNavigation from './RightSideTracking';
import StepComponent from './StepComponent';
import DisabledStateComponent from './DisabledState';

const CashAdvance = (props) => {
  const { showApplyNow, applicationId, productCode } = props;
  const [applicationData, setApplicationData] = useState(INITIAL_APPLICATION_STATE);
  const currentNavigationStatus = applicationData?.navigation?.current;
  const productConfig =
    PRODUCT_CONFIG[productCode] || PRODUCT_CONFIG[CAPITAL_PRODUCT_CODES.CASH_ADVANCE];

  const getApplicationData = async () => {
    const response = await getApplicationByParamData({
      application_id: applicationId,
    });
    const { data = {} } = response;
    const payload = getFormattedApplicationData(data);
    setApplicationData(payload);
  };

  function toggleClasses(nodes, className) {
    for (const each of nodes) {
      each?.classList?.toggle(className);
    }
  }

  useEffect(() => {
    trackLandingonCashAdvanceV2();
    const body = document.querySelector('body');
    const testModeLabel = body.querySelector('.highlight-test-mode-container');
    const nodes = [body, testModeLabel].filter((item) => item);

    toggleClasses(nodes, 'dark-background');

    return () => toggleClasses(nodes, 'dark-background');
  }, []);

  useEffect(() => {
    if (!showApplyNow && applicationId) {
      getApplicationData();
    }
  }, [applicationId]);

  const handleRedirection = (objectName = '') => {
    showApplyNow ? trackApplyNow() : trackApplicationStatus(objectName);
    if (currentNavigationStatus === APPLICATION_STATES.STATE_COMPLETED) {
      // withdrawal dashboard
      if (isLOCEMIProduct(productCode)) {
        window.open(productConfig.dashboardUrl, '_self');
        return;
      }
      props.history.push(productConfig.dashboardUrl);
      return;
    }
    // application URL
    window.open(productConfig.applicationUrl, '_self');
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
          <div className="heading">{productConfig.content.heading}</div>
          <div className="divider" />
          <div className={`sub-heading ${!showApplyNow ? 'with-border' : ''}`}>
            {productConfig.content.subheading}
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
                  <div className="right-heading">{productConfig.content.title}</div>
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
                    productCode={productCode}
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
                <a target="_blank" href={productConfig.faqUrl} rel="noreferrer noopener">
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
  applicationId: PropTypes.string,
  productCode: PropTypes.oneOf(['LOC', 'LOC_EMI']),
};

export default withRouter(CashAdvance);
