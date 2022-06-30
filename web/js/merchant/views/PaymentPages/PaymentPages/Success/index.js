import React from 'react';
import { connect } from 'react-redux';
import { Link, withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import CustomClipboard from 'common/ui/Clipboard/Custom';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Tooltip from 'common/ui/Tooltip';
import Loader from 'common/ui/Loader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import PageSettingsModal from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Settings';
import PaymentReceiptModal from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import Header from '../Success/Header';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchPaymentPage, updateReceiptDetails, updateData } from 'merchant/reducers/wysiwyg';

import RoundTickImage from '../../../../../../icons/merchant/tick-round.svg';
import ShiprocketImage from '../../../../../../css/assets/payment_pages/shiprocket.svg';

import { autoPrefixUrls, getErrorMessageFromResponse } from 'common/utils/rzp-utils';
import { sendLink, editPaymentPage, setReceiptDetails } from '../model';
import track from '../Wysiwyg/track';

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobileResolution: state.app.isMobileResolution,
    ...state.wysiwyg,
  }),
  {
    showNotification,
    openModal,
    closeModal,
    fetchPaymentPage,
    updateReceiptDetails,
    updateData,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesContainer'))
class Success extends React.Component {
  state = {
    isPaymentReceiptsModalOpen: false,
    isPageSettingsModalOpen: false,
    isLoaded: false,
    isPageLoadError: '',
  };

  componentDidMount() {
    this.fetchEntity(this.props.id);
  }

  fetchEntity = (id) => {
    const promise = this.props.fetchPaymentPage(id, false); // Auto reinitialise store if id doesn't exist.

    if (promise instanceof Promise) {
      promise
        .then(() => {
          this.setState({
            isLoaded: true,
          });
        })
        .catch(({ errors }) => {
          const err = getErrorMessageFromResponse(errors);

          this.props.showNotification({
            type: 'error',
            message: err,
          });
          this.setState({
            isPageLoadError: true,
            isLoaded: true,
          });
        });
    }
  };

  togglePaymentReceiptsModal = () => {
    // analyticsTrack({
    //     objectName: 'payment receipts',
    //     actionName: 'clicked',
    //     screen: 'create payment page',
    //     properties: {
    //         ...getCommonAnalyticsProperties(window.rzp_user),
    //     },
    // });
    // !this.state.isPageReceiptModalOpened && trackClickOnOpenPaymentReceipts();

    this.setState((prevState) => {
      return {
        isPaymentReceiptsModalOpen: !prevState.isPaymentReceiptsModalOpen,
      };
    });
  };

  togglePageSettingsModal = (isCustomiseURL) => {
    // analyticsTrack({
    //     objectName: 'settings',
    //     actionName: 'clicked',
    //     screen: 'create payment page',
    //     properties: {
    //         ...getCommonAnalyticsProperties(window.rzp_user),
    //     },
    // });

    this.setState(
      (prevState) => {
        return {
          isPageSettingsModalOpen: !prevState.isPageSettingsModalOpen,
        };
      },
      () => {
        // if customize url option, then focus on customize url section
        if (isCustomiseURL) {
          const customURLElement = document.querySelector('.settings-section.custom-url');
          if (customURLElement) {
            customURLElement.classList.add('fade-out');
            setTimeout(() => {
              customURLElement.classList.remove('fade-out');
            }, 500);
          }
        }
      },
    );
  };

  openShareView = () => {
    const { paymentPageEntity } = this.props;
    // trackDetailViewEdits('Click Share');
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleAction={sendLink.bind(null, paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          trackerFn={() => {}}
          // trackerFn={trackShareActions}
          // openEmbedButton={this.openEmbedButtonView}
          url={paymentPageEntity.short_url}
        />
      ),
    });
  };

  handleViewPage = () => {
    window.open(this.props.paymentPageEntity.short_url, '_blank');
  };

  handleSavePaymentReceipt = (receipt) => {
    const { paymentPageEntity } = this.props;

    const requestAPIPromiseForReceipt = setReceiptDetails(paymentPageEntity.id, receipt);
    return requestAPIPromiseForReceipt
      .then((res) => {
        // if api call is successful, update store
        this.props.updateReceiptDetails(receipt);
        if (!res || !res.success) {
          throw new Error(res.errors);
        }

        return res;
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Receipt settings could not be saved. Please try again.',
        });
      });
  };

  // Update settings in store
  handleSaveSettings = (formData) => {
    const { paymentPageEntity, customDomain } = this.props;

    const reqPayload = {};

    reqPayload.expire_by = formData.expire_by || null;

    /* 
      - While creation, if a slug has not been entered, the slug key is not sent in the payload in the normal flow 
        (pages.razorpay.com). Backend automatically generates a slug in that case. 
      - In the custom domain flow, the user can have an empty string as slug to use the root domain, hence 
        explicitly sending an empty string in the slug in that case.
    */
    if (formData.slug || formData.domainType === 'custom') {
      reqPayload.slug = (formData.slug || '').trim();
    }

    reqPayload.settings = {};

    if (typeof formData.theme !== 'undefined') {
      if (formData.theme === '0') {
        reqPayload.settings.theme = 'dark';
      } else {
        reqPayload.settings.theme = 'light';
      }
    }

    reqPayload.settings.payment_success_message = formData.payment_success_message || '';

    reqPayload.settings.payment_success_redirect_url = formData.payment_success_redirect_url
      ? autoPrefixUrls(formData.payment_success_redirect_url)
      : '';

    reqPayload.settings.custom_domain = formData.domainType === 'custom' ? customDomain.value : '';

    editPaymentPage(paymentPageEntity.id, reqPayload)
      .then(() => {
        // on success, update the store
        this.props.updateData(reqPayload);
        this.setState({
          isPageSettingsModalOpen: false,
        });
      })
      .catch(({ errors }) => {
        const err = getErrorMessageFromResponse(errors);

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  render() {
    const { paymentPageEntity, FORM_ITEMS } = this.props;
    const { isLoaded, isPageLoadError } = this.state;

    let content;

    if (!isLoaded) {
      content = <Loader />;
    } else if (isPageLoadError) {
      content = <div className="error-message">Something went wrong</div>;
    } else {
      content = (
        <>
          {this.state.isPageSettingsModalOpen && (
            <PageSettingsModal
              handleClose={this.togglePageSettingsModal.bind(null, false)}
              openModal={this.props.openModal}
              closeModal={this.props.closeModal}
              paymentPageEntity={paymentPageEntity}
              handleAction={this.handleSaveSettings}
              isNew={this.props.id}
              isTestMode={this.props.mode.toLowerCase() === 'test'}
              customDomain={this.props.customDomain}
            />
          )}

          {this.state.isPaymentReceiptsModalOpen && (
            <PaymentReceiptModal
              paymentPageEntity={paymentPageEntity}
              formItems={FORM_ITEMS}
              handleClose={this.togglePaymentReceiptsModal}
              handleSave={this.handleSavePaymentReceipt}
              trackingDetails={{
                isPaymentPage: true,
                via: 'success',
              }}
            />
          )}

          <div className="content">
            <Link class="btn edit-page-btn" to={`/paymentpages/${paymentPageEntity.id}/edit`}>
              <i class="i i-chevron-left" />
              <span>EDIT PAGE</span>
            </Link>

            <div id="hero-box">
              <div id="hero-box--left">
                <div id="hero-box--title">{paymentPageEntity.title}</div>
                <div id="hero-box--page-live">
                  <i className="i i-tick" />
                  Your page is now live!
                </div>
                <div className="divider" />
                <div id="hero-box--action">
                  <div>Page URL</div>
                  <div id="hero-box--action-buttons">
                    <CustomClipboard
                      value={paymentPageEntity.short_url}
                      // value={url}
                      onCopy={() => {
                        // analyticsTrack({
                        //     objectName: 'copy hyperlink',
                        //     actionName: 'button',
                        //     screen: 'create payment page',
                        //     properties: {
                        //         ...getCommonAnalyticsProperties(window.rzp_user),
                        //     },
                        // });
                        const ele = document.getElementsByName('short_url');
                        ele?.[0].focus();
                        // this.props.trackerFn('Click Copy URL');
                        track.success.clickCopyUrl();
                      }}
                    >
                      <Input
                        name="short_url"
                        value={paymentPageEntity.short_url}
                        readOnly={true}
                        className="short-url"
                      />
                      <Button.Primary class="Button--small">Copy</Button.Primary>
                    </CustomClipboard>
                    <Button.Primary onClick={this.openShareView} class="Button--small">
                      <i className="i i-share-outline mr-5" />
                      Share
                    </Button.Primary>
                    {this.props.mode === 'test' ? (
                      <span>
                        <Button
                          class="Button--small Button--customise-url"
                          onClick={this.togglePageSettingsModal.bind(null, true)}
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
                        class="Button--small Button--customise-url"
                        onClick={this.togglePageSettingsModal.bind(null, true)}
                      >
                        Customise URL
                      </Button>
                    )}
                  </div>
                  {/* CTAs container for mobile view */}
                  <div class="mobile-cta-container">
                    <Button.Transparent class="button--highlight" onClick={this.handleViewPage}>
                      Go To Page <i class="i i-external-link" />
                    </Button.Transparent>
                    <Button.Primary onClick={this.openShareView}>
                      <i className="i i-share-outline mr-5" />
                      Share
                    </Button.Primary>
                  </div>
                </div>
              </div>
              <div id="hero-box--right">
                <iframe
                  src={paymentPageEntity.short_url}
                  width="1000"
                  height="471"
                  scrolling="no"
                />
                <a
                  className="preview-icon"
                  href={paymentPageEntity.short_url}
                  target="_blank"
                  rel="noreferrer noopener"
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
                  {paymentPageEntity.receipt.enable_custom_serial_number === '0' ? (
                    <div className="box--line">
                      <img src={RoundTickImage} alt="tick" width="15" height="15" />
                      <b>Receipts will be sent automatically</b> to customers after each payment{' '}
                      <br />
                    </div>
                  ) : (
                    <div className="box--line">
                      <i className="i i-receipt" />
                      <b>Receipts will not be sent automatically</b> to customers after each payment{' '}
                      <br />
                    </div>
                  )}
                  {paymentPageEntity.receipt.enable_80g_details === '0' ? (
                    <div className="box--line box--space">
                      You can customise your receipt by adding{' '}
                      <b>
                        customer’s information & 80G details
                        <span className="rzp-tooltip-80g">
                          <i className="i i-info-outline" />
                          <Popover align="top" theme="dark">
                            <PopoverBody>
                              <div className="rzp-tooltip-title">For Donations</div>
                              80G-registered organisations can add their details on receipts to help
                              donors avail tax benefits
                            </PopoverBody>
                          </Popover>
                        </span>
                      </b>
                    </div>
                  ) : null}
                </div>
                <div className="box--right">
                  <Button.Transparent
                    className="button--highlight"
                    onClick={this.togglePaymentReceiptsModal}
                  >
                    <i className="i i-receipt mr-5" />
                    Receipt Settings
                  </Button.Transparent>
                </div>
              </div>
              <div className="box">
                <div className="box--left">
                  <div className="box--line">
                    <i className="i i-redirect" />
                    <b>Redirect</b> customers to your website after payment <br />
                  </div>
                  <div className="box--line">
                    <img src={ShiprocketImage} alt="shiprocket-logo" className="shiprocket-image" />
                    <b>Create orders on Shiprocket</b> after customer pays on this page <br />
                  </div>
                  <div className="box--line">
                    <i className="i i-signal" />
                    Track page usage with <b>Facebook Pixel & Google Analytics</b>
                  </div>
                  <div className="box--line box--and-more">...and more!</div>
                </div>
                <div className="box--right">
                  <Button.Transparent
                    className="button--highlight"
                    onClick={this.togglePageSettingsModal.bind(null, false)}
                  >
                    <i className="i i-settings-outline mr-5" />
                    Page Settings
                  </Button.Transparent>
                </div>
              </div>
              <div id="next-steps--note">
                <i className="i i-info-outline mr-5" />
                You can also configure receipts & page settings later from dashboard
              </div>
            </div>
          </div>
        </>
      );
    }
    return (
      <div class="pp-success-container">
        <Header />
        {content}
      </div>
    );
  }
}

export default withRouter(Success);
