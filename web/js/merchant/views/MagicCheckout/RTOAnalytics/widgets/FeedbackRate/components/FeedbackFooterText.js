import { NavLink } from 'react-router-dom';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import {
  UPLOAD_ORDER_STATUS_TAB,
  MAGIC_SETTINGS_TAB,
  MAGIC_SETTINGS_TAB_DELIVERY_TRACKING,
  UPLOAD_ORDER_STATUS_TAB_V2,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const FeedbackFooterText = ({ footerStatus, isShippingProviderAvailable }) => {
  const footerTexts = {
    complete: (
      <span>
        <div className="custom-success-tick">
          <i className="i i-tick" />
        </div>
        <span className="feedback-footer-info">Delivery data is up-to-date</span>
      </span>
    ),
    empty: (
      <span>
        <i className="i i-warning empty-status" />
        <span className="feedback-footer-info">
          Please provide{' '}
          <NavLink
            className="feedbackRate-links"
            to={
              useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
                ? UPLOAD_ORDER_STATUS_TAB_V2
                : UPLOAD_ORDER_STATUS_TAB
            }
          >
            data by uploading
          </NavLink>
          {' or '}
          <NavLink
            className="feedbackRate-links"
            to={
              useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
                ? MAGIC_SETTINGS_TAB_DELIVERY_TRACKING
                : MAGIC_SETTINGS_TAB
            }
          >
            integrate with your delivery partner
          </NavLink>
        </span>
      </span>
    ),
    partial: (
      <small>
        <i className="i i-warning partial-status" />
        <span className="feedback-footer-info">
          Partial data received. Please{' '}
          <NavLink className="feedbackRate-links" to={UPLOAD_ORDER_STATUS_TAB}>
            upload here
          </NavLink>
          {!isShippingProviderAvailable && (
            <span>
              {' or '}
              <NavLink className="feedbackRate-links" to={MAGIC_SETTINGS_TAB}>
                integrate with your delivery partner
              </NavLink>
            </span>
          )}
        </span>
      </small>
    ),
  };

  return <>{footerTexts[footerStatus]}</>;
};

export default FeedbackFooterText;
