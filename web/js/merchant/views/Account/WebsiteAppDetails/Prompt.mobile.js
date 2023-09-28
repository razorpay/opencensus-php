import React, { useEffect } from 'react';
import { pages } from 'merchant/views/Account/WebsiteAppDetails/data';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { updateBannerAndModalVisibility } from 'merchant/reducers/websitecompliance';
import { withRouter } from 'common/deprecated/withRouter';

function PromptMobile({
  websiteComplianceModalVisibility,
  updateBannerAndModalVisibility,
  closeModal,
  user,
  history,
  screen,
}) {
  const onUpdateClick = () => {
    analyticsTrack({
      objectName: 'Website compliance modal',
      actionName: 'Interacted',
      screen,
      properties: {
        pageTitle: screen,
        websiteCompliance: true,
        bannerTitle: 'Update details about your website/app',
        modalType: !user.isWebsiteComplianceModalNonDismissible ? 'Dismissable' : 'non-dismissable',
        ctaName: 'Update or create page',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    closeModal();
    history.push('/website-app-details?from=modal');
  };

  useEffect(() => {
    // send analytics on modal load
    analyticsTrack({
      objectName: 'Website compliance modal',
      actionName: 'Loaded',
      screen,
      properties: {
        pageTitle: screen,
        websiteCompliance: true,
        bannerTitle: 'Update details about your website/app',
        modalType: !user.isWebsiteComplianceModalNonDismissible ? 'Dismissable' : 'non-dismissable',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  const onCloseClick = () => {
    analyticsTrack({
      objectName: 'Website compliance modal',
      actionName: 'Interacted',
      screen: 'Home page',
      properties: {
        pageTitle: 'Home page',
        websiteCompliance: true,
        bannerTitle: 'Update details about your website/app',
        modalType: !user.isWebsiteComplianceModalNonDismissible ? 'Dismissable' : 'non-dismissable',
        ctaName: 'Close',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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
        <div className="prompt-footer">Don’t have these details? We’ll help you create them</div>
        <div className="actions">
          <button className="btn btn-primary" onClick={onUpdateClick}>
            Update or create page
          </button>
          {!user.isWebsiteComplianceModalNonDismissible ? (
            <span className="btn btn-link" onClick={onCloseClick}>
              I'll do it later
            </span>
          ) : null}
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
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

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(PromptMobile));
