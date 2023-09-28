import WidgetSetup from './Setup';
import EnableWidgetButton from './EnableWidget';
import { withRouter } from 'common/deprecated/withRouter';
import { useState, useEffect, Fragment } from 'react';
import { platformTitleMapping, platformIdMapping } from './data';
import { compose } from 'redux';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import track from './track';
import ShopifyCommingSoon from './ShopifyCommingSoon';

const Step = ({
  stepProps,
  platform,
  openModal,
  closeModal,
  affordability,
  tracking,
  user,
  ...props
}) => {
  const { title, desc, stepNumber, lastStep = false, cta, redirectUrl } = stepProps;
  const { enabled, test_mode_live } = affordability;

  const [isEnabled, setIsEnabled] = useState(enabled);

  const updateWidgetEnabled = () => {
    setIsEnabled(true);
  };

  const isTestModeLive = platform === 'others' && cta === 'setup' && test_mode_live;

  const isDone = () => {
    if (isTestModeLive || (isEnabled && cta == 'enable')) {
      return true;
    }
    return false;
  };

  return (
    <div className="step">
      <div className="step-indicator">
        <span className={`step-indicator-icon ${isDone() && 'step-indicator-icon-done'}`}>
          {!isDone() && stepNumber}
        </span>

        {!lastStep && <div className="step-indicator-connector" />}
      </div>

      <div className="step-content">
        <b className="title">{title}</b>
        <p className="description">{desc}</p>
        {isTestModeLive ? (
          <button className="Button--primary btn-lg btn-feedback" disabled={true}>
            Test mode live
          </button>
        ) : cta === 'setup' || cta === 'switch' ? (
          <ViewSetupGuide
            redirectUrl={redirectUrl}
            cta={cta}
            platform={platform}
            tracking={tracking}
          />
        ) : cta === 'enable' ? (
          <EnableWidgetButton
            source={platform}
            openModal={openModal}
            closeModal={closeModal}
            enabled={isEnabled}
            affordability={affordability}
            onEnable={updateWidgetEnabled}
            history={props.history}
          />
        ) : null}
      </div>
    </div>
  );
};

const ViewSetupGuide = ({ platform, cta, redirectUrl }) => {
  const setupSource = `${platform === 'others' ? 'native' : platform}_setup_page`;

  const ctaLabelMapping = {
    shopify: 'Go to Shopify',
    woocommerce: 'View Setup Guide',
    others: 'View Setup Guide',
  };

  const handleViewSetupClick = () => {
    if (platform === 'shopify') {
      track.goToShopify('shopify_setup_page');
    } else if (platform !== 'shopify' && cta === 'setup') {
      track.setupGuide(setupSource);
    }
  };

  return (
    <a
      className={`${
        platform === 'shopify' ? 'Button--primary' : 'Button--secondary'
      } btn-lg btn-border`}
      href={redirectUrl}
      target="_blank"
      rel="noreferrer noopener"
      onClick={handleViewSetupClick}
    >
      {cta === 'switch' && platform === 'others' ? 'Switch to live' : ctaLabelMapping[platform]}
    </a>
  );
};

const platformSteps = {
  shopify: [
    {
      title: 'Install Affordability App',
      desc: 'You will be redirected to Shopify to download the app',
      cta: 'setup',
      redirectUrl: 'https://apps.shopify.com/affordability-widget',
      lastStep: true,
    },
  ],
  woocommerce: [
    {
      title: 'Activate on your website',
      desc: (
        <>
          Click on <b>Enable Widget</b> to complete the integration
        </>
      ),
      cta: 'enable',
      done: false,
    },
    {
      title: 'Go live!',
      desc: 'Preview the widget in admin mode and switch to live mode by viewing the setup guide',
      redirectUrl:
        'https://razorpay.com/docs/payments/payment-gateway/affordability/widget/woocommerce#step-2-preview-and-go-live',
      cta: 'setup',
      lastStep: true,
    },
  ],
  others: [
    {
      title: 'Try on your website',
      desc: 'Preview the widget in test mode and switch to live mode by viewing the setup guide',
      cta: 'setup',
      redirectUrl:
        'https://razorpay.com/docs/payments/payment-gateway/affordability/widget/native-web/#step-1-integrate-the-widget',
      done: false,
    },
    {
      title: 'Go live!',
      desc: (
        <Fragment>
          Once switched to live mode, click on <b>Enable Widget</b> and you’re good to go
        </Fragment>
      ),
      cta: 'enable',
      lastStep: true,
    },
  ],
};

export const SetupSteps = ({ platform, ...props }) => {
  const { user } = props;
  const showWaitlistCTA = (platform) => {
    if (
      (platform === platformIdMapping.shopify && user.isShowAffWidgetShopifyWaitlist) ||
      (platform === platformIdMapping.woocommerce && user.isShowAffWidgetWoocWaitlist)
    ) {
      return true;
    }
    return false;
  };

  return (
    <div className="steps">
      {showWaitlistCTA(platform) ? (
        <ShopifyCommingSoon platform={platform} />
      ) : (
        <>
          {platformSteps[platform].map((step, idx) => (
            <div key={idx}>
              <Step stepProps={{ ...step, stepNumber: idx + 1 }} {...props} platform={platform} />
            </div>
          ))}
        </>
      )}
    </div>
  );
};

export const PlatformSetup = (props) => {
  const { platform } = props.match.params;
  const { openModal, closeModal, affordabilityWidget } = props;
  const { affordability } = affordabilityWidget;

  useEffect(() => {
    track.setupPage(platform === 'others' ? 'native' : platform);
  }, []);
  console.log('---->');
  return (
    <WidgetSetup
      enabled={affordability.enabled}
      className={`setup setup-${platform}`}
      title={`Set up Affordability Widget ${
        ['woocommerce', 'shopify'].includes(platform) ? `for ${platformTitleMapping[platform]}` : ''
      }`}
      imageUrl="https://cdn.razorpay.com/static/assets/affordability-widget/widget_banner.svg"
    >
      <SetupSteps
        affordability={affordability}
        openModal={openModal}
        closeModal={closeModal}
        platform={platform}
        tracking={props.tracking}
        history={props.history}
        user={props.user}
      />
    </WidgetSetup>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('PlatformSetup')),
  withRouter,
  connect((state) => ({ user: state.session.user })),
)(PlatformSetup);
