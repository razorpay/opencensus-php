import { useState } from 'react';
import { SeamlessHowto } from './SeamlessComponents';

export const SeamlessOption = ({ providers, selectedProvider }) => {
  const [isCollapsed, setIsCollapsed] = useState(false);

  const collapseHowTo = () => {
    setIsCollapsed(!isCollapsed);
  };

  const gatewayName = providers[selectedProvider]['Gateway Name'].data_value;

  return (
    <div className="col-xs-12">
      <div className="row" style={{ marginTop: '10px', marginBottom: '15px' }}>
        <div className="col-xs-2" />
        <div className="col-xs-10">
          <div className="enable-seamless-info-msg">
            <div className="seamless-header">
              <i className="i i-info-outline" />
              {selectedProvider === 'ingenico'
                ? `${gatewayName} Prerequisites*`
                : 'Enable seamless option*'}
            </div>
            <div className="seamless-desc">
              {selectedProvider === 'ingenico'
                ? `Your ${gatewayName} account should have the required features enabled to use optimizer.`
                : `Your ${gatewayName} account should have the seamless option enabled to use optimizer.`}
            </div>
            <div className="seamless-how-to-block">
              <div className="seamless-how-to">
                <i className="i i-help" />
                <span onClick={collapseHowTo}>
                  {selectedProvider === 'ingenico'
                    ? `Steps to enable ${gatewayName}`
                    : `How to enable seamless option on ${gatewayName}?`}
                  <img
                    src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                    className={`arrow-img ${isCollapsed ? '' : 'arrow-img-rotate'}`}
                  />
                </span>
              </div>
              {isCollapsed && (
                <div className="seamless-how-to-details">
                  <SeamlessHowto gatewayName={gatewayName} selectedProvider={selectedProvider} />
                </div>
              )}
            </div>
          </div>
          <div className="seamless-note">*Please proceed next, if you have already enabled</div>
        </div>
      </div>
    </div>
  );
};
