import React from 'react';
import ImgTopBg from 'assets/onboarding/top_bg.png';
import Image from 'common/ui/Image';
import ShopifyImg from 'assets/app-store/partner-logos/shopify.png';
import WooCommerceImg from 'assets/app-store/partner-logos/woocommerce.png';
import MagnetoImg from 'assets/app-store/partner-logos/magento.png';

const ProductOnboardingCard = () => {
  const PaymentGateway = () => (
    <div className="card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Payment Gateway</div>
            <div className="description">
              Integrate a fully customizable payment checkout for your website or app
            </div>
          </div>
          <div>
            <div className="placeholder" />
          </div>
        </div>
        <div>
          <div className="plugins">
            <Image src={ShopifyImg} isWebP />
            <Image src={WooCommerceImg} isWebP />
            <Image src={MagnetoImg} isWebP />
            <span>& more plugins available</span>
          </div>
          <div className="btn-container">
            <button type="button" className="btn btn-primary">
              Get API keys
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  const PaymentButtons = () => (
    <div className="card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Payment Buttons</div>
            <div className="description">
              Add a quick checkout button for one-time and recurring payments on your website
            </div>
          </div>
          <div>
            <div className="placeholder" />
          </div>
        </div>
        <div>
          <div>5 minute integration</div>
          <div className="btn-container">
            <button type="button" className="btn btn-outline">
              Create Payment Button
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  const PaymentHandle = () => (
    <div className="card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Razorpay.me</div>
            <div className="description">Share your link with anyone and collect a payment</div>
          </div>
          <div>
            <div className="placeholder" />
          </div>
        </div>
        <div>
          <div className="handle-link">https://razorpay.me/@abcassociates</div>
          <div className="btn-container multiple-btn">
            <button type="button" className="btn btn-outline">
              Copy link
            </button>
            <button type="button" className="btn btn-outline">
              Customize
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  const PaymentLinks = () => (
    <div className="card">
      <div className="wrapper">
        <div className="content">
          <div>
            <div className="header">Payment Links</div>
            <div className="description">
              Create an instant, one-time, custom link to share with a customer
            </div>
          </div>
          <div>
            <div className="placeholder" />
          </div>
        </div>
        <div className="btn-container">
          <button type="button" className="btn btn-outline">
            Create payment link
          </button>
        </div>
      </div>
    </div>
  );

  return (
    <div className="product-onboarding-card">
      <div className="illustration-top">
        <Image src={ImgTopBg} alt="Top" isWebP />
      </div>
      <div className="illustration-bottom">
        <img src="https://cdn.razorpay.com/static/assets/product-led-onboarding/bottom-bg.svg" />
      </div>
      <div className="illustration-right">
        <img src="https://cdn.razorpay.com/static/assets/product-recommendation/overview.svg" />
      </div>
      <p className="title">Congratulations Vignesh Kumar! You can start collecting payments</p>
      <div className="card-container">
        <PaymentGateway />
        <PaymentButtons />
        <PaymentHandle />
        <PaymentLinks />
      </div>
    </div>
  );
};

export default ProductOnboardingCard;
