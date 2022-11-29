import React from 'react';
import { trackCTAClick } from 'merchant/containers/Home/ProductOnboardingCard/events';
import ShopifyLogo from 'assets/app-store/partner-logos/shopify.png';
import WoocommerceLogo from 'assets/app-store/partner-logos/woocommerce.png';
import MagentoLogo from 'assets/app-store/partner-logos/magento.png';
import PaymentGatewayImage from 'assets/product-led-onboarding/payment-gateway.svg';

const PaymentGatewayCard = ({ history, product }) => {
  const rediretToApiKeys = () => {
    trackCTAClick('PG', { product });
    history.push('/api-keys');
  };
  return (
    <div className="plo-card" data-testid="payment-gateway-card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Payment Gateway</div>
            <div className="description">
              Integrate a fully customizable payment checkout for your website or app
            </div>
          </div>
          <div>
            <img className="card-illustration" src={PaymentGatewayImage} alt="" />
          </div>
        </div>
        <div>
          <div className="plugins">
            <img src={ShopifyLogo} />
            <img src={WoocommerceLogo} />
            <img src={MagentoLogo} />
            <span>& more plugins available</span>
          </div>
          <div className="btn-container">
            <button type="button" className="btn btn-primary" onClick={rediretToApiKeys}>
              Get API keys
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentGatewayCard;
