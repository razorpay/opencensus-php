import { useState } from 'react';
import { SeamlessHowto } from './SeamlessComponents';

import { PREREQUISITES_SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/constants';

export const SeamlessOption = ({ type = 'warning', providers, selectedProvider }) => {
  const [isCollapsed, setIsCollapsed] = useState(false);

  const collapseHowTo = () => setIsCollapsed(!isCollapsed);

  const gatewayName = providers?.[selectedProvider]?.['Gateway Name']?.data_value;

  return (
    <div className="feedback-card">
      <div className={`enable-seamless-info-msg ${type}`}>
        <div className="seamless-header">
          <i className="i i-info-outline" />
          <span>
            {PREREQUISITES_SUPPORTED_GATEWAYS.includes(selectedProvider)
              ? `${gatewayName} Prerequisites*`
              : 'Enable seamless option*'}
          </span>
        </div>
        <div className="seamless-desc">
          {PREREQUISITES_SUPPORTED_GATEWAYS.includes(selectedProvider)
            ? `Your ${gatewayName} account should have the required features enabled to use optimizer.`
            : `Your ${gatewayName} account should have the seamless option enabled to use optimizer.`}
        </div>
        <div className="seamless-how-to-block">
          <button
            className="btn btn-text p-0 seamless-how-to"
            type="button"
            role="button"
            onClick={collapseHowTo}
          >
            <span>
              {PREREQUISITES_SUPPORTED_GATEWAYS.includes(selectedProvider)
                ? `Steps to enable ${gatewayName}`
                : `How to enable seamless option on ${gatewayName}?`}
            </span>
            <img
              src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
              className={`arrow-img ${isCollapsed ? '' : 'arrow-img-rotate'}`}
            />
          </button>
          {isCollapsed && (
            <div className="seamless-how-to-details">
              <SeamlessHowto gatewayName={gatewayName} selectedProvider={selectedProvider} />
            </div>
          )}
        </div>
      </div>
      <div className="seamless-note">*Please proceed next, if you have already enabled</div>
    </div>
  );
};
