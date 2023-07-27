import React, { useEffect } from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { Link } from 'react-router-dom';
import {
  isNudgeHardForWebsiteCompliance,
  isNudgeSoftForWebsiteCompliance,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import { connect } from 'react-redux';
import { websiteComplianceEntryPointsData } from 'merchant/views/Account/WebsiteAppDetails/data';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

/* Renders only on dWeb */
function WebsiteComplianceBanner({ activationData, websiteSectionDetailsData, user, screen }) {
  const shouldShowBanner =
    isNudgeSoftForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data) ||
    isNudgeHardForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data);

  const nudgeType = isNudgeSoftForWebsiteCompliance(
    activationData.data,
    websiteSectionDetailsData.data,
  )
    ? 'soft'
    : 'hard';
  const bannerColor = nudgeType === 'soft' ? 'warning' : 'danger';
  const bannerTitle = websiteComplianceEntryPointsData[nudgeType].title;

  const isWebsitePolicyVerified = user?.website_policy_verification_status === 'verified';
  const isWebsitePolicyFailed = user?.website_policy_verification_status === 'failed';

  useEffect(() => {
    // send analytics on banner load
    if (shouldShowBanner && user.isWebsiteComplianceFlowEnabled) {
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

  // if `isWebsitePolicyVerified` website policy verification status is verified don't show modal
  if (!shouldShowBanner || !user.isWebsiteComplianceFlowEnabled || isWebsitePolicyVerified)
    return null;

  return (
    <AnnouncementBanner
      title={bannerTitle}
      theme={bannerColor}
      canBeClosed={!isWebsitePolicyFailed} // if `isWebsitePolicyFailed` website policy verification status is failed, don't let them close banner
    >
      <div className="website-compliance-announcement-container">
        <div className="announcement-content">
          <p>
            <span>
              Terms & Conditions, Privacy Policy, Contact Us, Cancellation & Refund Policy, and
              Shipping & Delivery Policy pages are required as per RBI guidelines.
            </span>
            <span className="separator separator-small" />
            <span>Don’t have these details? We’ll help you create them.</span>
          </p>
        </div>
        <div className="separator" />
        <div className="cta">
          <Link
            onClick={() => {
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
            }}
            to="/website-app-details?from=banner"
          >
            Update Now
          </Link>
        </div>
      </div>
    </AnnouncementBanner>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  activationData: state.websiteCompliance.activationData,
});

export default connect(mapStateToProps, null)(WebsiteComplianceBanner);
