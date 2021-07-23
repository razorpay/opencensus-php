import { useState } from 'react';

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
              Enable seamless option*
            </div>
            <div className="seamless-desc">
              Your {gatewayName} account should have the seamless option enabled to use optimizer.
            </div>
            <div className="seamless-how-to-block">
              <div className="seamless-how-to">
                <i className="i i-help" />
                <span onClick={collapseHowTo}>
                  How to enable seamless option on {gatewayName}?
                  <img
                    src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                    className={`arrow-img ${isCollapsed ? '' : 'arrow-img-rotate'}`}
                  />
                </span>
              </div>
              {isCollapsed && (
                <div className="seamless-how-to-details">
                  <ol>
                    <li>
                      Write to your {gatewayName} relationship manager asking to upgrade your
                      account to Seamless Enabled. Mention that you are using Razorpay as the
                      technology company to handle sensitive card data.
                    </li>
                    <li>
                      Copy Razorpay in the email and we will provide the supporting document from
                      our end.
                    </li>
                    <li>{gatewayName} will enable seamless on your account.</li>
                    <li>
                      If you are going to use UPI as a payment method following steps will have to
                      be configured:
                      <ol type="a">
                        <li>
                          configure webhook URL as{' '}
                          <a
                            href={`https://api.razorpay.com/v1/callback/${selectedProvider}`}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            {`https://api.razorpay.com/v1/callback/${selectedProvider}`}
                          </a>{' '}
                          to receive UPI response
                        </li>
                        <li>enable UPI on seamless with the flag “txn_s2s_flow=4”</li>
                      </ol>
                    </li>
                  </ol>
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
