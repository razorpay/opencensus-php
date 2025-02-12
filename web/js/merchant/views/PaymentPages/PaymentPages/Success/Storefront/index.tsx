import React, { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Header from 'merchant/views/PaymentPages/PaymentPages/Success/Header';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import Loader from 'common/ui/Loader';
import PageSettings from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Settings/StorefrontSettings';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchStorefront,
  editStorefrontDeepMerge,
  resetStorefront,
} from 'merchant/reducers/paymentPages/storefront';

import { dispatchWebViewEvent } from 'common/utils/reactNativeWebView';
import {
  sendLink,
  editStorefrontPage as editStorefrontPageApiCall,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import {
  OpenModalType,
  ShowNotificationType,
} from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/types';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track/index';
interface IStorefrontSuccessProps extends RouteComponentProps {
  storefrontData: any; // TODO: to be fixed after merging everyone's code together
  id: string;
  isWebView: boolean;
  fetchStorefront: (id: string) => Promise<void>;
  editStorefrontDeepMerge: (payload) => void;
  openModal: OpenModalType;
  closeModal: () => void;
  showNotification: ShowNotificationType;
  resetStorefront: () => void;
}

const StorefrontSuccess = (props: IStorefrontSuccessProps): React.ReactElement => {
  const { editStorefrontDeepMerge, storefrontData, showNotification, resetStorefront } = props;
  const { isLoading, entity: storeData, error } = storefrontData;
  const [isPageSettingsOpen, setIsPageSettingsOpen] = useState<boolean>(false);
  const location = useLocation();

  useEffect(() => {
    props.fetchStorefront(props.id);

    return () => {
      resetStorefront();
    };
  }, [props.id]);

  const handleGoToPage = (): void => {
    window.open(storeData.shortUrl, '_blank', 'noopener');
  };

  const handlePageSettings = (val: boolean) => {
    setIsPageSettingsOpen(val);

    const locationState = location.state;
    track.pageSettingClickedOnPublishedPage({
      storefrontId: props.id,
      isNewStorefront: Boolean(locationState?.isCreate),
      published_page_url: storeData.shortUrl,
    });
  };

  const trackClickboardCopy = () => {
    const locationState = location.state;
    track.successPageCopyUrl({
      storefrontId: props.id,
      isNewStorefront: Boolean(locationState?.isCreate),
      published_page_url: storeData.shortUrl,
    });
  };

  const handlePreviewPublishedPageClick = () => {
    const locationState = location.state;
    track.publishedPagePreview({
      storefrontId: props.id,
      isNewStorefront: Boolean(locationState?.isCreate),
      published_page_url: storeData.shortUrl,
    });
  };

  const handleShare = (): void => {
    // if opened in webview (mobile app), sending native event with required data
    if (props.isWebView) {
      dispatchWebViewEvent({
        eventType: 'SHARE',
        data: {
          url: storeData.shortUrl,
          title: storeData.title,
        },
      });
    } else {
      props.openModal({
        size: 'small',
        component: (
          <ShareView
            handleClose={props.closeModal}
            openModal={props.openModal}
            handleAction={sendLink.bind(null, storeData.id)}
            showNotification={props.showNotification}
            title={storeData.title}
            description={storeData.description}
            trackerFn={() => {}}
            url={storeData.shortUrl}
          />
        ),
      });
    }
  };

  const handleMyProducts = (): void => {
    props.history.push('/paymentpages/products');
  };

  const handlePluginsAndAddOnsSave = (formData): void => {
    const payload = {
      settings: formData,
    };

    editStorefrontDeepMerge(payload);
  };

  const handlePageSettingsSave = (formData: {
    expire_by?: number | null;
    payment_success_message?: string;
    payment_success_redirect_url?: string;
    slug?: string | null;
  }) => {
    const payload: {
      expire_by: number | null;
      settings: {
        payment_success_message: string;
        payment_success_redirect_url: string;
      };
      slug?: string | null;
    } = {
      expire_by: formData?.expire_by ? +formData.expire_by : null,
      settings: {
        ...storeData.settings, // destructuring the whole settings because backend api expects the whole settings object for patch
        payment_success_message: formData?.payment_success_message || '',
        payment_success_redirect_url: formData?.payment_success_redirect_url || '',
      },
      slug: formData.slug || null,
    };
    editStorefrontPageApiCall(props.id, payload, false)
      .then(() => {
        const newPayload = {
          ...payload,
          expire_by: payload.expire_by ? Math.round(payload.expire_by / 1000) : null,
        };
        editStorefrontDeepMerge(newPayload);

        showNotification({
          type: 'success',
          message: 'Page settings were saved successfully',
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Failed to save settings. Please try again later.',
        });
      });
  };

  let content: JSX.Element | string;

  if (isLoading) {
    content = <Loader />;
  } else if (error) {
    content = <div className="error-message">{error}</div>;
  } else {
    content = (
      <div className="content">
        <Link
          onClick={() => {
            const locationState = location.state;
            track.editStorefrontPage({
              storefrontId: props.id,
              isNewStorefront: Boolean(locationState?.isCreate),
              published_page_url: storeData.shortUrl,
            });
          }}
          className="btn edit-page-btn"
          to={`/paymentpages/storefront/${props.id}/edit`}
        >
          <i className="i i-chevron-left" />
          <span>EDIT PAGE</span>
        </Link>

        <div id="hero-box">
          <div id="hero-box--left">
            <div id="hero-box--title">{storeData.title}</div>
            <div id="hero-box--page-live">
              <i className="i i-tick" />
              Your page is now live!
            </div>
            <div className="divider" />
            <div id="hero-box--action">
              <div>Page URL</div>
              <div id="hero-box--action-buttons">
                <CustomClipboard value={storeData.shortUrl} onCopy={trackClickboardCopy}>
                  <Input
                    name="short_url"
                    value={storeData.shortUrl}
                    readOnly={true}
                    className="short-url"
                  />
                  <Button.Primary className="Button--small">Copy</Button.Primary>
                </CustomClipboard>
                {/* TODO: Add it back once backend API is done */}
                {/* <Button.Primary className="Button--small" onClick={handleShare}>
                  <i className="i i-share-outline mr-5" />
                  Share
                </Button.Primary> */}
                {/* TODO: Add it back once we add custom domain to storefront */}
                {/* {props.mode === 'test' ? (
            <span>
              <Button
                className="Button--small Button--customise-url"
                // onClick={this.togglePageSettingsModal.bind(null, true)}
                disabled={true}
              >
                Customise URL
              </Button>
              <Tooltip theme="dark" align="top">
                Only available in Live Mode
              </Tooltip>
            </span>
          ) : (
            <Button
              className="Button--small Button--customise-url"
              onClick={togglePageSettingsModal.bind(null, true)}
            >
              Customise URL
            </Button>
          )} */}
              </div>
              {/* CTAs container for mobile view */}
              <div className="mobile-cta-container">
                <Button.Transparent className="button--highlight" onClick={handleGoToPage}>
                  Go To Page <i className="i i-external-link" />
                </Button.Transparent>
                <Button.Primary onClick={handleShare}>
                  <i className="i i-share-outline mr-5" />
                  Share
                </Button.Primary>
              </div>
            </div>
          </div>
          <div id="hero-box--right">
            <iframe src={storeData.shortUrl} width="1000" height="471" scrolling="no" />
            <a
              className="preview-icon"
              href={storeData.shortUrl}
              target="_blank"
              rel="noreferrer noopener"
              onClick={handlePreviewPublishedPageClick}
            >
              <i className="i i-external-link" />
            </a>
            <div className="image-shadow" />
          </div>
        </div>

        <div id="next-steps">
          <div id="next-steps--title">
            Next Steps for Your Page
            <div className="divider" />
          </div>
          <div className="box">
            <div className="box--left">
              <div className="box--line">
                <i className="i i-products" />
                <b>Add more Products -</b> add multiple product images and detailed description
              </div>
            </div>
            <div className="box--right">
              <Button.Transparent className="button--highlight" onClick={handleMyProducts}>
                <i className="i i-products mr-5" />
                My Products
              </Button.Transparent>
            </div>
          </div>
          <div className="box">
            <div className="box--left">
              <div className="box--line">
                <i className="i i-redirect" />
                <b>Redirect</b> customers to your website after payment <br />
              </div>
              <div className="box--line box--and-more">...and more!</div>
            </div>
            <div className="box--right">
              <Button.Transparent
                className="button--highlight"
                onClick={handlePageSettings.bind(null, true)}
              >
                <i className="i i-settings-outline mr-5" />
                Page Settings
              </Button.Transparent>
            </div>
          </div>
          <div id="next-steps--note">
            <i className="i i-info-outline mr-5" />
            You can also add products and configure page settings later from dashboard
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="pp-success-container">
      <Header />
      {content}
      {isPageSettingsOpen && (
        <PageSettings
          storefrontEntity={storeData}
          onSave={handlePageSettingsSave}
          onPluginsAndAddOnsSave={handlePluginsAndAddOnsSave}
          onClose={handlePageSettings.bind(null, false)}
        />
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  storefrontData: state.paymentPageStorefront,
  isWebView: state.app.isWebView,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      openModal,
      showNotification,
      fetchStorefront,
      editStorefrontDeepMerge,
      resetStorefront,
    },
    dispatch,
  );

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(StorefrontSuccess));
