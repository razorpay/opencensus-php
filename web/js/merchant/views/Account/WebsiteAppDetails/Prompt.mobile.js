import React, { useEffect } from 'react';
import { pages } from 'merchant/views/Account/WebsiteAppDetails/data';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { updateBannerAndModalVisibility } from 'merchant/reducers/websitecompliance';

function PromptMobile({
  websiteComplianceModalVisibility,
  updateBannerAndModalVisibility,
  closeModal,
}) {
  const onUpdateClick = () => {
    const analyticsObj = {
      objectName: 'Website wizard modal',
      actionName: 'Interacted',
      screen: 'Home page',
      properties: {
        bannerTitle: 'Update details about your website/app',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    };
    analyticsTrack({
      analyticsObj,
    });
    window.open('website-app-details?from=modal', '_self');
  };

  useEffect(() => {
    // send analytics on modal load
    const analyticsObj = {
      objectName: 'Website wizard modal',
      actionName: 'Loaded',
      screen: 'Home page',
      properties: {
        bannerTitle: 'Update details about your website/app',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    };
    analyticsTrack({
      analyticsObj,
    });
  }, []);

  const onCloseClick = () => {
    closeModal();
    const previousViewCount = Number(
      websiteComplianceModalVisibility.data.website_incomplete_soft_nudge_count,
    );
    const modalPayload = {
      website_incomplete_soft_nudge_count: websiteComplianceModalVisibility.data
        .website_incomplete_soft_nudge_count
        ? previousViewCount - 1
        : 4,
      website_incomplete_soft_nudge_timestamp: Math.round(Date.now() / 1000).toString(), // unix time
    };
    updateBannerAndModalVisibility(modalPayload);
  };

  return (
    <div className="website-app-details-container">
      <div className="prompt-container">
        <div className="image-container">
          <img src="https://cdn.razorpay.com/static/assets/website-compliance/Update.png" />
        </div>
        <div className="prompt-title">Update details about your website/app</div>
        <div className="prompt-description">
          According to RBI guidelines, we require the following pages on your website/app:
        </div>
        <ul className="page-listing">
          {pages.map((page, idx) => {
            return <li key={`${page}_${idx}`}>{page}</li>;
          })}
        </ul>
        <div className="prompt-footer">Don’t have these details? We’ll help you create them.</div>
        <div className="actions">
          <button className="btn btn-primary" onClick={onUpdateClick}>
            Update or create page
          </button>
          <span className="btn btn-link" onClick={onCloseClick}>
            I'll do it later
          </span>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  activationData: state.websiteCompliance.activationData,
  websiteComplianceModalVisibility: state.websiteCompliance.bannerAndModalVisibility,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      updateBannerAndModalVisibility,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PromptMobile);
