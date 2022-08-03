import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Loader from 'common/ui/Loader';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import {
  formatStatus,
  getStatusClass,
  getLatestNeedsClarificationComment,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import ViewComments from 'merchant/views/Account/WebsiteAppDetails/ViewComments';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  fetchActivationDetails,
  fetchMerchantWebsiteDetails,
} from 'merchant/reducers/websitecompliance';

function WebsiteAppDetails({
  activationData,
  websiteSectionDetailsData,
  showNotification,
  openModal,
}) {
  const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });
  const from = params.from; // modal, banner email, sms, whatsapp

  useEffect(() => {
    // send analytics on wizard entry load
    if (Object.keys(activationData.data).length) {
      const analyticsObj = {
        objectName: 'Website wizard visit',
        actionName: 'Loaded',
        screen: 'Website/App details',
        properties: {
          previousPageUrl: document.referrer,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
      if (from) {
        analyticsObj.properties.source = from;
      }
      analyticsTrack({
        analyticsObj,
      });
    }
  }, [activationData, from]);

  useEffect(() => {
    const { error } = websiteSectionDetailsData;
    if (error) {
      showNotification({
        type: 'error',
        message: error,
      });
    }
  }, [websiteSectionDetailsData, showNotification]);

  if (!activationData.data && !activationData.error)
    return (
      <div className="website-app-details-container">
        <Loader />
      </div>
    );

  if (activationData.error) return null;

  const businessWebsiteUrl = activationData.business_website || '--';
  const appStoreUrl = activationData.appstore_url || '--';
  const playStoreUrl = activationData.playstore_url || '--';

  const getCTAText = () => {
    const { data, error } = websiteSectionDetailsData;

    if (error) return '';

    if (Array.isArray(data)) return `Update or create page`;

    if (typeof data === 'object' && !Array.isArray(data)) {
      const formattedStatus = formatStatus(data.status);
      switch (formattedStatus) {
        case 'under review':
        case 'rejected':
        case 'approved':
          return 'View/update details';
        case 'details required':
          return 'Update or create page';
        case 'action required':
          return 'Update details';
        default:
          return '';
      }
    } else {
      return '';
    }
  };

  const ctaText = getCTAText();

  const onButtonClick = () => {
    const analyticsObj = {
      objectName: 'Website wizard visit',
      actionName: 'Clicked',
      screen: 'Website/App details',
      properties: {
        CTAName: ctaText,
        previousPageUrl: document.referrer,
        websiteUrl: businessWebsiteUrl,
        appStoreUrl,
        playStoreUrl,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    };
    if (from) {
      analyticsObj.properties.source = from;
    }
    analyticsTrack({
      analyticsObj,
    });
    window.open('https://easy.razorpay.com/website-compliance', '_blank').focus();
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

  const latestNeedsClarificationComments = getLatestNeedsClarificationComment(
    activationData.kyc_clarification_reasons,
  );

  const showShouldNeedsClatificationComments = () => {
    if (activationData.kyc_clarification_reasons) {
      if (latestNeedsClarificationComments.length > 0) return true;
      else return false;
    } else {
      return false;
    }
  };

  const onViewClick = () =>
    openModal({
      size: 'small',
      component: <ViewComments comments={latestNeedsClarificationComments} />,
    });

  return (
    <div className="website-app-details-container">
      <div className="section-content">
        <div className="section-header">
          {renderStatus()}
          <p className="text">
            Update your business information now as per RBI guidelines to avoid settlements being
            put on-hold.
          </p>
          {showShouldNeedsClatificationComments() ? (
            <div className="comment">
              {latestNeedsClarificationComments[0].reason_code}{' '}
              <p onClick={onViewClick}>View more</p>
            </div>
          ) : null}
        </div>
        <div className="section-body">
          <div>
            <span>Website</span>
            <p>{businessWebsiteUrl}</p>
          </div>
          <div>
            <span>Android app</span>
            <p>{playStoreUrl}</p>
          </div>
          <div>
            <span>iOS app</span>
            <p>{appStoreUrl}</p>
          </div>
        </div>
        <div className="section-footer">
          {ctaText ? <button onClick={onButtonClick}>{ctaText}</button> : null}
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  activationData: state.websiteCompliance.activationData,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification: fnShowNotification,
      ...ModalActions,
      fetchActivationDetails,
      fetchMerchantWebsiteDetails,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(WebsiteAppDetails);
