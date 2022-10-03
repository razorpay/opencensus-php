import React from 'react';
import Button from 'common/new-ui/Button';
import { isMobileDevice } from 'merchant/components/Home/data';
import ShopifyBg from 'assets/shopify_migration/shopify_bg.png';
import Clock from 'assets/shopify_migration/shopify_clock.png';
import HeroIllustration from 'assets/shopify_migration/hero_Illustration.png';

const ShopifyMigrationPopUp = ({ closeModal, nextPopUpFunc }) => {
  const routeToShopify = (e) => {
    closeModal();
    window.location =
      e.target.name === 'find-out-more'
        ? 'https://razorpay.com/blog/razorpay-secure-keep-accepting-payments-on-your-shopify-store-now-and-well-past-31st-july/?utm_source=pop-up&utm_medium=dashboard&utm_campaign=Shopify+v1.1+GTM'
        : 'https://accounts.shopify.com/store-login?redirect=settings%2Fpayments%2Falternative-providers%2F1058839';
  };

  const handleClose = () => {
    closeModal();
    nextPopUpFunc();
  };

  return (
    <div className="Shopify-Migration">
      <button type="button" className="close btn" onClick={handleClose}>
        <i className="i i-close" />
      </button>
      <div className="shopify-bg-container">
        <img src={ShopifyBg} className="style-bg" />
      </div>
      <div className="content">
        {!isMobileDevice() && (
          <div className="timer">
            <img src={Clock} alt="timer" />
          </div>
        )}

        <div className="payments-text">Don't disrupt payments on your Shopify store</div>
        <hr className="below-text-line" />

        <div className="bottom-shopify-text">
          {isMobileDevice()
            ? 'You need to mandatorily upgrade to the new Razorpay Secure app before '
            : 'Upgrade to the new Razorpay Secure app before '}
          <span className="deadline-style">July 31st</span> to continue accepting payments on your
          Shopify Store.
        </div>

        <div className="content__btn">
          <Button.Primary
            type="button"
            name="upgrade-now"
            className="accept-payment"
            onClick={routeToShopify}
          >
            Upgrade Now
          </Button.Primary>

          <Button
            type="button"
            name="find-out-more"
            className="find-out-more"
            onClick={routeToShopify}
          >
            Find out more
          </Button>
        </div>
        <div className="hero-illustration">
          <img src={HeroIllustration} alt="illustration" />
        </div>
      </div>
    </div>
  );
};

export default ShopifyMigrationPopUp;
