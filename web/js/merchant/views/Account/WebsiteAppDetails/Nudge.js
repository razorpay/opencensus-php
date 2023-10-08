import React, { useEffect } from 'react';
import { isMobileDevice } from 'merchant/components/Home/data';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  formatStatus,
  getStatusClass,
  isNudgeHardForWebsiteCompliance,
  isNudgeSoftForWebsiteCompliance,
  isPolicyWizardV2Enabled,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import { websiteComplianceEntryPointsData } from 'merchant/views/Account/WebsiteAppDetails/data';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { fetchMerchantWebsiteDetails } from 'merchant/reducers/websitecompliance';
import { withRouter } from 'common/deprecated/withRouter';
import { useSplitzService } from 'common/splitz';

/* renders only on mobile devices/resolutions */
function WebsiteAppDetailsNudge({
  activationData,
  websiteSectionDetailsData,
  fetchMerchantWebsiteDetails,
  showNotification,
  screen,
  user,
  history,
}) {
  const isMobileResolution = isMobileDevice();
  const splitz = useSplitzService();
  const shouldShowNudge =
    !isPolicyWizardV2Enabled({ user, activationData: activationData.data, splitz }) &&
    (isNudgeSoftForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data) ||
      isNudgeHardForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data));

  const nudgeType = isNudgeSoftForWebsiteCompliance(
    activationData.data,
    websiteSectionDetailsData.data,
  )
    ? 'soft'
    : 'hard';

  useEffect(() => {
    const { error } = websiteSectionDetailsData;
    if (error) {
      showNotification({
        type: 'error',
        message: error,
      });
    }
  }, [websiteSectionDetailsData, showNotification]);

  useEffect(() => {
    // send analytics on nudge load
    if (isMobileResolution && user.isWebsiteComplianceFlowEnabled && shouldShowNudge) {
      analyticsTrack({
        objectName: 'Website compliance banner',
        actionName: 'Loaded',
        screen,
        properties: {
          pageTitle: screen,
          websiteCompliance: true,
          bannerTitle: websiteComplianceEntryPointsData[nudgeType].title,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, []);

  useEffect(() => {
    if (!Object.keys(websiteSectionDetailsData.data).length && !websiteSectionDetailsData.error) {
      fetchMerchantWebsiteDetails();
    }
  }, []);

  if (
    !isMobileResolution ||
    !shouldShowNudge ||
    !user.isWebsiteComplianceFlowEnabled ||
    user?.website_policy_verification_status === 'verified'
  )
    return null;

  const onUpdateClick = () => {
    analyticsTrack({
      objectName: 'Website compliance banner',
      actionName: 'Interacted',
      screen,
      properties: {
        pageTitle: screen,
        websiteCompliance: true,
        bannerTitle: websiteComplianceEntryPointsData[nudgeType].title,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    history.push('/website-app-details?from=banner');
  };

  const renderStatus = () => {
    const { data, error } = websiteSectionDetailsData;
    if (error) return null;

    if (Array.isArray(data)) {
      const status = 'details required';
      return (
        <span className="website-app-info-status details-required">
          <p>{status.toUpperCase()}</p>
        </span>
      );
    }

    if (typeof data === 'object' && !Array.isArray(data)) {
      const formattedStatus = formatStatus(data.status);
      const statusClassName = getStatusClass(formattedStatus);
      return (
        <span className={`website-app-info-status ${statusClassName}`}>
          <p>{formattedStatus.toUpperCase()}</p>
        </span>
      );
    } else return null;
  };

  return (
    <div className="website-app-details-container">
      <div className="nudge-container">
        {renderStatus()}
        <div>{websiteComplianceEntryPointsData[nudgeType].title}</div>
        <div>
          Terms & Conditions, Privacy Policy, Contact Us, Cancellation and Refund Policy, and
          Shipping and Delivery Policy pages are required as per RBI guidelines.
        </div>
        <div className="nudge-footer">Don’t have these details? We’ll help you create them</div>
        <div>
          <button className="btn btn-primary" onClick={onUpdateClick}>
            Update or create page
          </button>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  activationData: state.websiteCompliance.activationData,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification: fnShowNotification,
      fetchMerchantWebsiteDetails,
    },
    dispatch,
  );

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(WebsiteAppDetailsNudge));
