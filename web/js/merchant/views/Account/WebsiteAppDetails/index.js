import Loader from 'common/ui/Loader';
import LoaderDots from 'common/ui/LoaderDots';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import isEmpty from 'lodash/isEmpty';
import {
  fetchActivationDetails,
  fetchMerchantWebsiteDetails,
} from 'merchant/reducers/websitecompliance';
import EditWebsiteDetailsModal from 'merchant/views/Account/Profile/components/EditWebsiteDetailsModal';
import { websiteComplianceEntryPointsData } from 'merchant/views/Account/WebsiteAppDetails/data';
import {
  formatStatus,
  getLatestNeedsClarificationComment,
  getStatusClass,
  isUrlFieldEmpty,
  isPolicyWizardV2Enabled,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import ViewComments from 'merchant/views/Account/WebsiteAppDetails/ViewComments';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useSplitzService } from 'common/splitz';

function WebsiteAppDetails({
  activationData,
  websiteSectionDetailsData,
  showNotification,
  openModal,
  closeModal,
  fetchActivationDetails,
  fetchMerchantWebsiteDetails,
  user,
}) {
  const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });
  const from = params.from; // modal, banner, email, sms, whatsapp, nc & merchant dashboard

  const splitz = useSplitzService();

  const isExpEnabled = isPolicyWizardV2Enabled({
    splitz,
    user,
    activationData: activationData.data,
  });

  useEffect(() => {
    // send analytics on wizard entry load
    if (
      Object.keys(activationData.data).length &&
      Object.keys(websiteSectionDetailsData.data).length
    ) {
      const { data } = websiteSectionDetailsData;
      let status;
      if (Array.isArray(data)) status = 'details required';

      if (typeof data === 'object' && !Array.isArray(data)) status = formatStatus(data.status);

      analyticsTrack({
        objectName: isExpEnabled ? 'Business Policy Section' : 'Website compliance visit',
        actionName: 'Loaded',
        screen: 'Website/App details',
        properties: {
          websiteCompliance: true,
          pageTitle: 'Website/App details',
          previousPageUrl: document.referrer,
          [isExpEnabled ? 'status' : 'websiteComplianceStatus']: status,
          from: from ? from : 'Merchant dashboard',
          websiteUrl: activationData.data.business_website || '--',
          appStoreUrl: activationData.data.appstore_url || '--',
          playStoreUrl: activationData.data.playstore_url || '--',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, [activationData, websiteSectionDetailsData, from]);

  // fetch details if not present already
  useEffect(() => {
    if (!Object.keys(activationData.data).length && !activationData.error) {
      fetchActivationDetails();
    }
  }, []);

  // fetch details if not present already
  useEffect(() => {
    if (!Object.keys(websiteSectionDetailsData.data).length && !websiteSectionDetailsData.error) {
      fetchMerchantWebsiteDetails();
    }
  }, []);

  useEffect(() => {
    const { error } = websiteSectionDetailsData;
    if (error) {
      showNotification({
        type: 'error',
        message: error,
      });
    }
  }, [websiteSectionDetailsData, showNotification]);

  if (activationData.loading && !activationData.error)
    return (
      <div className="website-app-details-container">
        <Loader />
      </div>
    );

  if (activationData.error) return null;

  const { business_website, appstore_url, playstore_url } = activationData.data;

  const businessWebsiteUrl = business_website || '--';
  const appStoreUrl = appstore_url || '--';
  const playStoreUrl = playstore_url || '--';

  const isWebsiteMerchant = !isUrlFieldEmpty(activationData.data);

  const shouldEnableForNoCode = isExpEnabled && !isWebsiteMerchant;

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
        case 'verified':
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

  const onWebsiteAdd = () => {
    fetchActivationDetails();
  };

  const onButtonClick = () => {
    analyticsTrack({
      objectName: isExpEnabled ? 'Wizard Visit' : 'Website compliance visit',
      actionName: 'Clicked',
      screen: 'Website/App details',
      properties: {
        websiteCompliance: true,
        pageTitle: 'Website/App details',
        ctaName: ctaText,
        previousPageUrl: document.referrer,
        websiteUrl: businessWebsiteUrl,
        appStoreUrl,
        playStoreUrl,
        from: from ? from : 'Merchant dashboard',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    if (!shouldEnableForNoCode && isUrlFieldEmpty(activationData.data)) {
      openModal({
        size: 'small',
        component: <EditWebsiteDetailsModal onClose={closeModal} onWebsiteAdd={onWebsiteAdd} />,
      });
    } else {
      window.open(
        isExpEnabled
          ? `${window.EASY_ONBOARDING_URL}/onboarding/policy?source=dashboard`
          : `${window.EASY_ONBOARDING_URL}/website-compliance`,
        '_self',
      );
    }
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
    activationData.data.kyc_clarification_reasons,
  );

  const showShouldNeedsClarificationComments = () => {
    if (
      activationData.data.kyc_clarification_reasons &&
      activationData?.data?.activation_status === 'needs_clarification'
    ) {
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

  const { data } = websiteSectionDetailsData;
  const formattedStatus = formatStatus(data.status);
  const title = websiteComplianceEntryPointsData[formattedStatus].title;

  if (!shouldEnableForNoCode && isUrlFieldEmpty(activationData.data) && !activationData.loading) {
    return (
      <div className="website-app-details-container">
        <div className="section-content">
          <div className="section-header">
            <p>Collect payments on your website or app</p>
          </div>
          <div className="section-body">
            <span>
              Update your business now as per RBI guidelines to avoid settlements being put on hold
            </span>
          </div>
          <div className="section-footer">
            <button onClick={onButtonClick}>Add website or app</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="website-app-details-container">
      <div className="section-content">
        <div className="section-header">
          {websiteSectionDetailsData.loading ? <LoaderDots /> : renderStatus()}
          <p className="text">{data && !isEmpty(data) ? title : <LoaderDots />}</p>
          {showShouldNeedsClarificationComments() ? (
            <div className="comment">
              {latestNeedsClarificationComments[0].reason_code}{' '}
              {latestNeedsClarificationComments?.length > 1 ? (
                <p onClick={onViewClick} style={{ cursor: 'pointer' }}>
                  View more
                </p>
              ) : null}
            </div>
          ) : null}
        </div>
        {isWebsiteMerchant ? (
          <div className="section-body">
            <div>
              <span>Website</span>
              <p>{activationData.loading ? <LoaderDots /> : businessWebsiteUrl}</p>
            </div>
            <div>
              <span>Android app</span>
              <p>{activationData.loading ? <LoaderDots /> : playStoreUrl}</p>
            </div>
            <div>
              <span>iOS app</span>
              <p>{activationData.loading ? <LoaderDots /> : appStoreUrl}</p>
            </div>
          </div>
        ) : null}
        <div className="section-footer">
          {ctaText ? (
            <button onClick={onButtonClick} disabled={websiteSectionDetailsData.loading}>
              {websiteSectionDetailsData.loading ? <LoaderDots /> : ctaText}
            </button>
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
