import React from 'react';
import store, { getUser } from '../../../merchant/store';
import {
  openModal as openModalProp,
  closeModal as closeModalProp,
} from 'merchant_common/reducers/modals';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import ThankYouModal from 'common/ui/GrowthServiceModal/ThankYouModal';
import GrowthServiceModal from 'common/ui/GrowthServiceModal';
import GrowthServiceCenterCTAModal from 'common/ui/GrowthServiceModal/CenterCTAModal';

const MODAL_TYPE = {
  DEFAULT: 'default',
  THANKYOU: 'thank-you',
  CENTERCTA: 'center-cta',
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
  const isExternal = /^http(s)?:\/\//.test(url);
  if (isExternal) {
    window.open(url, '_blank');
  } else {
    const closeModal = (payload) => store.dispatch(closeModalProp(payload));
    closeModal();
    history.push(url);
  }
};

/**
 * Send Saleforce event with given properties
 * @param {[]} properties - properties to be sent to Salesforce
 */
const gSSalesforceEvent = (properties) => {
  const user = getUser();
  sendDataToSalesForce(properties, user).catch((error) => {
    console.error(error);
  });
};

/**
 * Open default variant modal
 * @param {string} id - template id
 * @returns {*} - returns default variant modal
 */
const showGSModal = (id) => {
  const openModal = (payload) => store.dispatch(openModalProp(payload));
  const isMWeb = isMobileAndTablet();
  return openModal({
    component: <GrowthServiceModal template_id={id} />,
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
    component: <GrowthServiceCenterCTAModal template_id={id} />,
    className: !isMWeb ? 'gs-modal' : '',
  });
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
    size: 'medium',
    component: <ThankYouModal template_id={id} />,
    className: !isMWeb ? 'gs-medium-modal' : '',
  });
};

/**
 * Handle CTA click from Growth Service Assets
 * @param {*} data - Array of cta click events
 * @param {*} history - history object
 */
const growthServiceCTAHandler = (data, history) => {
  // iterate over data in handler
  data.forEach((item) => {
    if (item?.type === EVENT_TYPE.URL) {
      gSOpenUrl(item?.url, history);
    } else if (item?.type === EVENT_TYPE.SALESFORCESEVENT) {
      gSSalesforceEvent(item?.properties);
    } else if (item?.type === EVENT_TYPE.TEMPLATE) {
      // open modal
      if (item?.sub_asset?.type === 'MODAL') {
        if (item?.sub_asset?.variant == MODAL_TYPE.DEFAULT) {
          showGSModal(item?.sub_asset?.id);
        } else if (item?.sub_asset?.variant == MODAL_TYPE.CENTERCTA) {
          showGSCenterCTAModal(item?.sub_asset?.id);
        }
        if (item?.sub_asset?.variant == MODAL_TYPE.THANKYOU) {
          showThankYouModal(item?.sub_asset?.id);
        }
      }
    }
  });
};

export default growthServiceCTAHandler;
