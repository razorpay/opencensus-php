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
function WebsiteComplianceBanner({ activationData, websiteSectionDetailsData, user }) {
  const shouldShowBanner =
    isNudgeSoftForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data) ||
    isNudgeHardForWebsiteCompliance(activationData.data, websiteSectionDetailsData.data);

  const nudgeType = isNudgeSoftForWebsiteCompliance() ? 'soft' : 'hard';
  const bannerColor = nudgeType === 'soft' ? 'warning' : 'danger';
  const bannerTitle = websiteComplianceEntryPointsData[nudgeType].title;

  useEffect(() => {
    // send analytics on banner load
    if (shouldShowBanner && user.isWebsiteComplianceFlowEnabled) {
      const analyticsObj = {
        objectName: 'Website wizard banner',
        actionName: 'Loaded',
        screen: 'Home page',
        properties: {
          bannerTitle: websiteComplianceEntryPointsData[nudgeType].title,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
      analyticsTrack({
        analyticsObj,
      });
    }
  }, []);

  if (!shouldShowBanner || !user.isWebsiteComplianceFlowEnabled) return null;

  return (
    <AnnouncementBanner title={bannerTitle} theme={bannerColor} canBeClosed={false}>
      <div className="website-compliance-announcement-container">
        <div className="announcement-content">
          <p>
            Terms & Conditions, Privacy Policy, Contact Us, Cancellation & Refund Policy, and
            Shipping and Delivery Policy pages & required as per RBI guidelines.
          </p>
        </div>
        <div className="separator" />
        <div className="cta">
          <Link
            onClick={() => {
              const analyticsObj = {
                objectName: 'Website wizard banner',
                actionName: 'Interacted',
                screen: 'Home page',
                properties: {
                  bannerTitle: websiteComplianceEntryPointsData[nudgeType].title,
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              };
              analyticsTrack({
                analyticsObj,
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
