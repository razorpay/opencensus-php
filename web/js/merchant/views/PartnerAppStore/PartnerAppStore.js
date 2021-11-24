import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import { track as trackPartnerOnbr } from 'merchant/views/PartnerDashboard/Onboarding/ga';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { partnerProducts } from './data/index';

// Helpers START
function showPartnerIntent(props) {
  if (window && typeof window.hj === 'function') {
    window.hj('trigger', 'partner_onboarding_started');
    window.hj('tagRecording', ['partner_onboarding_started']);
  }

  const businessTypeName = props.user.isUnregisteredBusiness ? 'Unregistered' : 'Registered';
  trackPartnerOnbr({
    eventLabel: `Partner Onboarding | Start | Become a Partner | ${businessTypeName}`,
  });

  analyticsTrack({
    objectName: 'Become a Partner',
    actionName: 'clicked',
    screen: 'app store',
    properties: {
      location: 'app store footer',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toCleverTap: true,
  });

  props.tracking.trackEvent(
    window.rzpQ.onbr().clicked('partnerships.appstore.partner.cta', {
      merchantId: props.user.merchant.id,
    }),
  );

  props.openModal({
    size: 'xlarge',
    disableClose: false,
    component: <PartnerOnbr closeModal={props.closeModal} disableClose={false} />,
    className: props.isMobileResolution
      ? 'partner-onboarding-popup mobile-app-popup'
      : 'partner-onboarding-popup',
  });
}

function trackMerchant(event, props) {
  props.tracking.trackEvent(
    window.rzpQ.onbr().clicked(event, {
      merchantId: props.user.merchant.id,
    }),
  );
}

function appTileClickHandler(props, appSlug) {
  props.tracking.trackEvent(
    window.rzpQ.onbr().clicked('partnerships.appstore.app', {
      merchantId: props.user.merchant.id,
      appName: appSlug,
    }),
  );
}
// Helpers END

// new app banner
function NewAppBanner(props) {
  const { isMobileResolution } = props;
  return (
    <div className="new-app-banner">
      <div className="new-app-icon">
        <img src="/dist/css/assets/app-store/new-app-icon.svg" />
      </div>
      <div className="new-app-message">
        <p>Don’t miss out on our</p>
        <p>
          <b>newly added apps</b>
          <br /> to the marketplace.
        </p>
      </div>
      <div className="arrow-head-container">
        {isMobileResolution ? (
          <>
            <img className="arrow-head-top" src="/dist/css/assets/app-store/mob-down-head-1.svg" />
            <img className="arrow-head-mid" src="/dist/css/assets/app-store/mob-down-head-2.svg" />
            <img
              className="arrow-head-bottom"
              src="/dist/css/assets/app-store/mob-down-head-3.svg"
            />
          </>
        ) : (
          <>
            <img className="arrow-head-top" src="/dist/css/assets/app-store/down-head-1.svg" />
            <img className="arrow-head-bottom" src="/dist/css/assets/app-store/down-head-2.svg" />
          </>
        )}
      </div>
    </div>
  );
}

// Sub-components START
function BecomePartner(props) {
  return (
    <div className="become-partner-container">
      <div className="become-partner-content">
        <h2>Publish your App on the App Store</h2>
        <p>
          Join our developer community and start building and publishing your App on the Razorpay
          App Store{' '}
        </p>

        <div className="partner-links">
          <a
            onClick={() => trackMerchant('partnerships.appstore.developer', props)}
            className="btn btn-primary"
            href="https://razorpay.com/app-store/developer-guide"
          >
            View Developer Guide <i className="i i-arrow-forward" />
          </a>
        </div>
      </div>
      <div className="become-partner-content">
        <h2>Join the Razorpay Partner Program</h2>
        <p>Extend the finest Payment Experience to your customers and grow your business</p>

        <div className="partner-links">
          <button className="btn btn-primary" onClick={() => showPartnerIntent(props)}>
            Become a Partner <i className="i i-arrow-forward" />
          </button>
        </div>
      </div>

      <img
        className="footer-pc-illustration"
        src="/dist/css/assets/app-store/footer-pc-illustration.svg"
        alt="PC illustration"
      />
    </div>
  );
}

function PartnerAppCard(props) {
  const product = props.product;
  const [cardHoverBgColor, setCardHoverBgColor] = useState(``);
  return (
    <div className="product-col">
      {product.isNew ? <div className="new-offer-ribbon">NEW!</div> : null}
      <Link
        onClick={() => appTileClickHandler(props, product.slug)}
        className="product-wrapper"
        to={`/app-store/${product.slug}`}
        onMouseEnter={() => setCardHoverBgColor(product.brandColor)}
        onMouseLeave={() => setCardHoverBgColor(``)}
      >
        <div className="image-x-title-flex" style={{ background: cardHoverBgColor }}>
          <div className="image-col">
            <div className="product-image-background">
              <img
                alt={`Logo of ${product.title}`}
                src={`/dist/css/assets/app-store/partner-logos/${product.logo}`}
              />
            </div>
          </div>
          <div className="title-category-col">
            <h2 className="product-title">{product.title}</h2>
            <span className="product-category">{product.category}</span>
          </div>
          <span className="left-strip" style={{ background: cardHoverBgColor }} />
        </div>

        <div className="product-description">
          <p>{product.description}</p>
        </div>

        <div className="more-details-container">
          <Link
            className="mode-details-arrow-anchor"
            to={`/app-store/${product.slug}`}
            style={{ background: cardHoverBgColor }}
          >
            <i className="i i-arrow-forward" />
          </Link>
          <svg
            className="hover-vector"
            width="85"
            height="66"
            viewBox="0 0 85 66"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M32.5 65L19.5 18L84.5 1" stroke="#1F5BB4" strokeOpacity="0.13" />
            <path d="M11.5 62.5L1 43.5L83 36" stroke="#1F5BB4" strokeOpacity="0.13" />
          </svg>
        </div>
      </Link>
    </div>
  );
}
// Sub-components END

// Main Component
function PartnerAppStore(props) {
  const isPartner = props.user.isPartner();

  return (
    <div className="PartnerAppStore appstore-shared-styles">
      <section className="appstore-card">
        {/* Top banner START */}
        <div className="appstore-header text-white">
          <div className="back-to-dashboard-button-container">
            <Link to="/dashboard">
              <i className="i i-arrow-back" /> Back To Dashboard
            </Link>
          </div>

          <div className="appstore-info">
            <h1 className="appstore-heading">
              Find the <span className="text-teal">right apps</span> for your business needs
            </h1>
            <p>Explore powerful applications and tools to get the most out of Razorpay</p>
          </div>
          {props.isMobileResolution ? (
            <img
              className="top-shape-green top-shape-green-mobile"
              src="/dist/css/assets/app-store/mob-banner-shape-green.svg"
              alt=""
            />
          ) : (
            <img
              className="top-shape-green"
              src="/dist/css/assets/app-store/top-shape-green.svg"
              alt=""
            />
          )}
          <img
            className="razorpay-partner-image"
            src="/dist/css/assets/app-store/razorpay-partner.svg"
            alt=""
          />
        </div>
        {/* Top banner ENDS */}

        {/* Background dots design */}
        {props.isMobileResolution ? null : (
          <div className="dots-container">
            <img className="dots-1" src="/dist/css/assets/app-store/dots.svg" />
            <img className="dots-2" src="/dist/css/assets/app-store/dots.svg" />
            <img className="dots-3" src="/dist/css/assets/app-store/dots.svg" />
            <img className="dots-4" src="/dist/css/assets/app-store/dots.svg" />
          </div>
        )}
        {/* New banner */}
        <NewAppBanner {...props} />

        {/* Main part that holds partner cards */}
        <div className="partner-products-container">
          {Object.values(partnerProducts).map((product, index) => {
            if (product.slug === 'whatsapp-bot-payment-link' && !props.user.isAppStoreEnabled) {
              return null;
            }

            return <PartnerAppCard key={`app-${index}`} product={product} {...props} />;
          })}
        </div>
      </section>
      <section className="appstore-card become-partner">
        {isPartner ? null : <BecomePartner {...props} />}
      </section>
    </div>
  );
}
// Main Component END

export default connect(
  (state) => ({
    user: state.session.user,
    isMobileResolution: state.app.isMobileResolution,
  }),
  { openModal, closeModal },
)(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => {
    window.rzpQ.component('PartnerAppStore');
  })(PartnerAppStore),
);
