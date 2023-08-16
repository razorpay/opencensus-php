import React, { Suspense } from 'react';

import GrowthServiceModal from 'common/ui/GrowthServiceModal';
import GrowthServiceCenterCTAModal from 'common/ui/GrowthServiceModal/CenterCTAModal';
import ThankYouModal from 'common/ui/GrowthServiceModal/ThankYouModal';
import Loader from 'common/ui/Loader';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { stringToLiteral } from 'merchant/models/GrowthService/commonUtils';
import lazy from 'merchant/routes/LazyLoader';
import store, { getUser } from 'merchant/store';
import {
  openModal as openModalProp,
  closeModal as closeModalProp,
} from 'merchant_common/reducers/modals';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';

const PricingSubscriptionComponent = isMobileAndTablet()
  ? lazy(() =>
      import(
        /* webpackChunkName: 'PricingSubscriptionMWebComponent Mweb' */ 'common/ui/PricingSubscription/Mobile/BottomSheetPricingContainer'
      ),
    )
  : lazy(() =>
      import(
        /* webpackChunkName: 'PricingSubscriptionComponent Dweb ' */ 'common/ui/PricingSubscription/PricingSubscriptionComponent'
      ),
    );

export const SUB_ASSET_TYPE = {
  MODAL: 'MODAL',
  PRICINGBUNDLE: 'pricing_bundle',
};

export const MODAL_VARIANT = {
  DEFAULT: 'default',
  THANKYOU: 'thank-you',
  CENTERCTA: 'center-cta',
};

export const PRICING_BUNDLE_VARIANT = {
  DEFAULT: 'default',
  READ_ONLY: 'read-only',
};

const EVENT_TYPE = {
  URL: 'url',
  SALESFORCESEVENT: 'salesforce_event',
  TEMPLATE: 'template',
};

/**
 * open internal or external url
 * @param {string} url - url to be opened
 * @param {*} history - history object
 */
const gSOpenUrl = (url, history) => {
  const processedUrl = stringToLiteral(url);

  const isExternal = /^http(s)?:\/\//.test(processedUrl);
  if (isExternal) {
    window.open(processedUrl, '_blank');
  } else {
    const closeModal = (payload) => store.dispatch(closeModalProp(payload));
    closeModal();
    history.push(processedUrl);
  }
};

/**
 * Open default variant modal
 * @param {*} id - template id
 * @param {*} tracking_id Tracking_id passed from Parent Asset
 * @returns {*} - returns default variant modal
 */
const showGSModal = (id, tracking_id) => {
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const isMWeb = isMobileAndTablet();
  return openModal({
    closeOnOverLay: true,
    component: <GrowthServiceModal template_id={id} tracking_id={tracking_id} />,
    className: !isMWeb ? 'gs-modal' : '',
  });
};

/**
 * Open center-cta variant modal
 * @param {string} id - template id
 * @returns {*} - returns center-cta variant modal
 */
const showGSCenterCTAModal = (id) => {
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const isMWeb = isMobileAndTablet();
  return openModal({
    closeOnOverLay: true,
    component: <GrowthServiceCenterCTAModal template_id={id} />,
    className: !isMWeb ? 'gs-modal' : '',
  });
};

const showPricingBundleModal = (id, variant) => {
  const RenderPricingUi = () => (
    <Suspense fallback={<Loader />}>
      <PricingSubscriptionComponent templateId={id} variant={variant} />,
    </Suspense>
  );
  if (isMobileAndTablet()) {
    // Open Pricing Bundle BottomSheet mobile
    return <RenderPricingUi />;
  } else {
    // Open Pricing Bundle Modal desktop
    const openModal = (payload) => store.dispatch(openModalProp(payload));
    return openModal({
      component: <RenderPricingUi />,
      className: 'pricing-bundle-loader',
    });
  }
};

/**
 * Open thank-you variant modal
 * @param {string} id - template id
 * @returns {*} - returns thank-you variant modal
 */
const showThankYouModal = (id) => {
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const isMWeb = isMobileAndTablet();
  return openModal({
    closeOnOverLay: true,
    size: 'medium',
    component: <ThankYouModal template_id={id} />,
    className: !isMWeb ? 'gs-medium-modal' : '',
  });
};

const growthServiceEventHandler = (data, history, tracking_id) => {
  // iterate over data in handler
  data?.forEach((item) => {
    if (item?.type === EVENT_TYPE.URL) {
      gSOpenUrl(item?.url, history);
    } else if (item?.type === EVENT_TYPE.TEMPLATE) {
      // open modal
      if (item?.sub_asset?.type === SUB_ASSET_TYPE.MODAL) {
        if (item?.sub_asset?.variant == MODAL_VARIANT.DEFAULT) {
          showGSModal(item?.sub_asset?.id, tracking_id);
        } else if (item?.sub_asset?.variant == MODAL_VARIANT.CENTERCTA) {
          showGSCenterCTAModal(item?.sub_asset?.id);
        }
        if (item?.sub_asset?.variant == MODAL_VARIANT.THANKYOU) {
          showThankYouModal(item?.sub_asset?.id);
        }
      } else if (item.sub_asset?.type === SUB_ASSET_TYPE.PRICINGBUNDLE) {
        showPricingBundleModal(item.sub_asset?.id, item.sub_asset?.variant);
      }
    }
  });
};

/**
 * @param {*} properties - properties to be sent to Salesforce
 * @param {*} data -  Array of cta click events
 * @param {*} history - history object
 * @param {*} tracking_id - Tracking_id passed from Parent Asset
 * @param {*} tracking - tracking passed lumberjack tracking call
 * @returns {*} - Promise from SF call
 */
const gSSalesforceEvent = (properties, data, history, tracking_id, tracking) => {
  const user = getUser();
  const showNotification = (payload) => store.dispatch(showNotificationProp(payload));
  return sendDataToSalesForce(properties, user)
    .then(() => {
      return growthServiceEventHandler(data, history, tracking_id);
    })
    .catch((_) => {
      showNotification({
        type: 'error',
        message: 'An error occurred in connecting to the server',
        hidePrevious: true,
      });
      tracking.trackEvent(
        window.rzpQ?.merchantActions().initiated('merchant_dashboard.salesforce.failure', {
          trackingID: tracking_id,
          pageUrl: window.location.href,
        }),
      );
    });
};

/**
 * * Handle CTA click from Growth Service Assets
 * @param {*} data - Array of cta click events
 * @param {*} history - history object
 * @param {*} tracking_id - Tracking_id passed from Parent Asset
 * @param {*} tracking - tracking passed lumberjack tracking call
 * @returns {*} - Promise from SF call
 */
const growthServiceCTAHandler = (data, history, tracking_id, tracking) => {
  // iterate over data in handler & check for Saleforce Event Type
  let SFEvent = false;
  let SFProperties = {};
  data?.forEach((item) => {
    if (item?.type === EVENT_TYPE.SALESFORCESEVENT) {
      SFEvent = true;
      SFProperties = item?.properties;
    }
  });

  // if SFEvent is true, send SFProperties to Salesforce & wait for other
  // events to be handled until SFEvent is completed.
  if (SFEvent) {
    return gSSalesforceEvent(SFProperties, data, history, tracking_id, tracking);
  } else {
    return growthServiceEventHandler(data, history, tracking_id);
  }
};
export default growthServiceCTAHandler;
