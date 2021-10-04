import { merchantFetch } from 'merchant/utils/ajax';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { partnerProducts } from './data/index';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import rTracking from 'react-tracking';

function installHandler(appid, setIsAppInstalled, showNotification) {
  merchantFetch({
    url: 'merchants/app_store/install',
    mode: 'live',
    method: 'POST',
    data: {
      app_name: appid,
    },
  }).then((res) => {
    if (res.success === true && res.data.success === true) {
      // Installation successful
      setIsAppInstalled(true);
      // TODO: add success message here
      showNotification({
        type: 'success',
        message: 'App Installed Successfully',
      });
    } else {
      // something went wrong
      // TODO: add error message here
      showNotification({
        type: 'error',
        message: 'App Installation Failed. Please try again!',
      });

      console.log(res);
    }
  });
}

function checkIfAppIsInstalled(merchantId, appid, _showNotification) {
  return merchantFetch({
    url: `merchants/${merchantId}/app_store/apps`,
    mode: 'live',
  }).then((res) => {
    if (res.success === true) {
      const isCurrentAppInTheInstalledList =
        res.data.findIndex((entry) => entry.app_name === appid) >= 0;
      if (isCurrentAppInTheInstalledList) {
        return true;
      } else {
        return false;
      }
    } else {
      // failed to fetch
      console.log('could not fetch app installation state');
      console.log(res);
      return false;
    }
  });
}

function trackBannerButtonClick(tracking, user, appName) {
  tracking.trackEvent(
    window.rzpQ.onbr().clicked('partnerships.appstore.getstarted', {
      merchantId: user.merchant.id,
      appName,
    }),
  );
}

// Sub Components START

function BannerButton({
  partnerDetails,
  isAppInstallable,
  isAppInstalled,
  setIsAppInstalled,
  showNotification,
  user,
  tracking,
  appName,
}) {
  if (!isAppInstallable) {
    return (
      <a
        onClick={() => trackBannerButtonClick(tracking, user, appName)}
        className="btn get-started-button"
        target="_blank"
        href={partnerDetails.url}
        rel="noreferrer"
      >
        {partnerDetails.cta ? partnerDetails.cta : 'Get Started'} &nbsp;{' '}
        <i className="fa fa-angle-right" />
      </a>
    );
  }

  if (isAppInstalled) {
    return (
      <button style={{ cursor: 'not-allowed' }} className="btn get-started-button install-button">
        Installed
      </button>
    );
  }

  if (!user.isActivated) {
    return (
      <button
        style={{ cursor: 'not-allowed' }}
        className="btn get-started-button complete-kyc-option"
      >
        Complete KYC to Install this App
      </button>
    );
  }

  return (
    <>
      <button
        disabled={user.role !== 'owner'}
        onClick={() => {
          trackBannerButtonClick(tracking, user, appName);
          installHandler(partnerDetails.appid, setIsAppInstalled, showNotification);
        }}
        className="btn get-started-button install-button"
      >
        Install
      </button>
      {user.role !== 'owner' ? (
        <span className="only-owners-message">Only Owners can Install this App</span>
      ) : null}
    </>
  );
}

function PartnerPage(props) {
  const partnerSlug = props.partner;
  const partnerDetails = partnerProducts[partnerSlug];
  const [partnerContent, setPartnerContent] = useState('');
  const [isAppInstalled, setIsAppInstalled] = useState(false);

  const isAppInstallable = partnerDetails.isAppInstallable && partnerDetails.appid; // appid is needed to make app installable

  const brandColor = partnerDetails.brandColor || '#1F6ED8';

  useEffect(() => {
    // Read the respective content from ./data/content/<app-name>.js file
    (async () => {
      const contentData = (await import(`./data/content/${partnerSlug}`)).default(brandColor);
      setPartnerContent(contentData);
    })();

    // If the App is installable, check if it is installed already.
    (async () => {
      if (isAppInstallable) {
        setIsAppInstalled(
          await checkIfAppIsInstalled(
            props.user.merchant.id,
            partnerDetails.appid,
            props.showNotification,
          ),
        );
      }
    })();
  }, []);

  const { logoPadding } = partnerDetails;

  return (
    <div className="PartnerPage appstore-shared-styles">
      <section className="appstore-card">
        <div className="partnerpage-colored-top" style={{ backgroundColor: brandColor }}>
          <header>
            <Link to="/app-store">
              <i className="i i-arrow-back" /> Back To Apps
            </Link>
          </header>

          <div className="top-heading">
            <div className="product-logo-container inline-block">
              <div
                className="product-image-background"
                style={{ ...(logoPadding !== undefined && { padding: `${logoPadding}px` }) }}
              >
                <img
                  alt={`Logo of ${partnerDetails.title}`}
                  src={`/dist/css/assets/app-store/partner-logos/${partnerDetails.logo}`}
                />
              </div>
            </div>

            <div className="heading-container inline-block">
              <h1>{partnerDetails.detailsPageTitle || partnerDetails.title}</h1>
              <p className="category">{partnerDetails.category}</p>
            </div>

            <div className="get-started-button-container inline-block">
              <BannerButton
                partnerDetails={partnerDetails}
                isAppInstallable={isAppInstallable}
                isAppInstalled={isAppInstalled}
                setIsAppInstalled={setIsAppInstalled}
                showNotification={props.showNotification}
                user={props.user}
                tracking={props.tracking}
                appName={partnerDetails.slug}
              />
            </div>
          </div>
        </div>

        <div className="content">{partnerContent}</div>
      </section>
    </div>
  );
}

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  { showNotification: fnShowNotification },
)(
  rTracking(() => {
    window.rzpQ.component('PartnerPage');
  })(PartnerPage),
);
