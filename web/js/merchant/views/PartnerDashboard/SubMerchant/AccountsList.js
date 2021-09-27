import { Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import QueryString from 'query-string';

import ListContainer from 'merchant/containers/ListContainer';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchSubmerchants as fetchAll } from 'merchant/reducers/collection';
import { switchMerchant } from 'merchant/reducers/session';
import { downloadSubmerchants } from 'merchant/reducers/submerchant';
import RTracking from 'react-tracking';

import DataTable from 'common/ui/Table/DataTable';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

import ShowWhen from 'merchant/components/ShowWhen';
import { getTime } from 'common/ui/item';
import {
  ActivationStatusLabel,
  SubmerchantSettlementLabel,
  XSubmerchantCAStatusLabel,
  XSubmerchantVAStatusLabel,
} from 'merchant/components/StatusLabel';
import {
  submerchant as submerchantColumn,
  submerchantId as id,
  email as emailColumn,
} from 'common/ui/item/pair';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ListFilter from './ListFilter';
import { trackSearchAnalytics, trackClearAnalytics, trackReferral } from '../ga';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { mediaWindowUrl } from './components/SocialShare';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import Loader from 'common/ui/Loader';

const email = {
  title: 'Registered Email',
  value: emailColumn.value,
};

const addedOn = {
  title: 'Added On',
  value: getTime('created_at', 'll'),
};

const activationStatus = {
  title: (
    <Fragment>
      Activation Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant's activation request</PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) =>
    submerchant.details && submerchant.details.activation_status ? (
      <>
        <ActivationStatusLabel status={submerchant.details.activation_status} />
        {submerchant.details.activation_status === 'instantly_activated' && (
          <>
            &nbsp;
            <i class="i i-info-circle" />
            <PopoverComponent align="right" theme="dark">
              <PopoverBody>
                The merchant can accept live payments but settlements will be on hold until KYC
                completion.
              </PopoverBody>
            </PopoverComponent>
          </>
        )}
      </>
    ) : (
      <span class="status-label label label-warning">Not Submitted</span>
    ),
};

const settlementStatus = {
  title: (
    <Fragment>
      Settlement Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>
            Current status of whether the merchant can receive the settlement
          </PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <SubmerchantSettlementLabel
      status={
        submerchant.details &&
        submerchant.details.activation_status === 'activated' &&
        submerchant.hold_funds === false
          ? 'active'
          : 'inactive'
      }
    />
  ),
};

const xVirtualAccountStatus = {
  title: (
    <Fragment>
      Virtual Account Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant's virtual account</PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <XSubmerchantVAStatusLabel
      status={
        submerchant.banking_account && submerchant.banking_account.va_status
          ? submerchant.banking_account.va_status.toLowerCase()
          : 'inactive'
      }
    />
  ),
};

const xCurrentAccountStatus = {
  title: (
    <Fragment>
      Current Account Status&nbsp;
      <span>
        <i class="i i-info-circle" />
        &nbsp;
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>Current status of merchant's current account</PopoverBody>
        </PopoverComponent>
      </span>
    </Fragment>
  ),
  value: (submerchant) => (
    <XSubmerchantCAStatusLabel
      status={
        submerchant.banking_account && submerchant.banking_account.ca_status
          ? submerchant.banking_account.ca_status.toLowerCase()
          : 'inactive'
      }
    />
  ),
};

const switchMerchantActionBtn = (handleSwitchMerchant) => ({
  title: 'Switch Account',
  value: (item) =>
    item.dashboard_access ? (
      <button
        class="btn btn-default btn-xs"
        onClick={handleSwitchMerchant(item.id.replace('acc_', ''))}
      >
        Switch
      </button>
    ) : (
      'No Access'
    ),
});

const appId = {
  title: 'App Id',
  value: (item) => (
    <Link to={`/partners/applications/${item.application.id}`}>{item.application.id}</Link>
  ),
};

@RTracking(() => window.rzpQ.component('ProductSubMerchantsList'))
class ProductSubMerchantsList extends ListContainer {
  state = {};

  searchAnalytics = () => {
    const searchQuery = QueryString.parse(this.props.location.search);
    const { count, email: emailId, id: accountId, name: accountName } = searchQuery;
    const result = this.props.items?.length;
    this.trackUserEvent('partnerships.dashboard.affiliate_account.search', {
      searchRequestParams: {
        count,
        emailId,
        accountId,
        accountName,
      },
      result,
    });
  };

  getCurrentProduct = () => {
    const { product } = this.props;
    if (product === PRODUCT_TYPE.PG) {
      return 'Payments';
    }
    if (product === PRODUCT_TYPE.X) {
      return 'X';
    }
    return '';
  };

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    const productGroup = this.getCurrentProduct();
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        ...properties,
      }),
    );
  };

  name = (isPurePlatform) => ({
    ...submerchantColumn,
    value: (item) => (
      <Link
        to={`/partners/submerchants/${item.id}`}
        onClick={() =>
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            submerchantId: item.id,
          })
        }
      >
        {item.name}
      </Link>
    ),
    ...(isPurePlatform && {
      value: (item) => (
        <Link
          to={`/partners/submerchants/${item.id}/${item.application.id}`}
          onClick={() =>
            this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
              submerchantId: item.id,
              applicationId: item.application.id,
            })
          }
        >
          {item.name}
        </Link>
      ),
    }),
  });

  xName = () => ({
    ...submerchantColumn,
    value: (item) => (
      <Link
        to={`/partners/submerchants/x/${item.id}`}
        onClick={() =>
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            submerchantId: item.id,
          })
        }
      >
        {item.name}
      </Link>
    ),
  });

  handleAddMerchant = () => {
    this.trackUserEvent('partnerships.submerchant.add', {
      source: 'welcome screen',
    });
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  handleSwitchMerchant = (merchantId) => () => {
    this.props
      .switchMerchant(merchantId)
      .then(() => {
        window.location.reload();
      })
      .catch((errors) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  onDownload = () => {
    this.trackUserEvent('partnerships.dashboard.affiliate_account.export');
    const { user } = this.props;
    this.props.showNotification({
      type: 'info',
      message: 'Your file will downloaded shortly',
      hidePrevious: true,
    });
    this.setState({ affiliatesDownloading: true });
    return downloadSubmerchants(user.isPartner('pure_platform'), user.id)
      .then((response) => {
        this.setState({ affiliatesDownloading: false });
        if (response.error) {
          this.props.showNotification({
            type: 'error',
            message: 'Oops!, Unable to export data of submerchants',
            hidePrevious: true,
          });
          return;
        }
        window.location = response.data.signed_url;
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Oops!, Unable to export data of submerchants',
          hidePrevious: true,
        });
      });
  };
  shareReferralOn(platform, referralUrl) {
    mediaWindowUrl({
      type: platform,
      url: referralUrl,
      title: 'Sign up on Razorpay!',
      description:
        "Start using a wide range of Razorpay's payment solutions and unlock growth for your business with just a few clicks. Go live in less than 10 minutes.",
    });
    const productGroup = this.getCurrentProduct();
    if (this.props.product === PRODUCT_TYPE.X) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.x.social', {
          partnerID: this.props.user.id,
        }),
      );

      // new event
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.social', {
          productGroup,
          partnerID: this.props.user.id,
          socialMedia: platform,
          source: 'welcome screen',
        }),
      );
    }
    if (this.props.product === PRODUCT_TYPE.PG) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.social', {
          partnerID: this.props.user.id,
        }),
      );
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.social', {
          productGroup,
          partnerID: this.props.user.id,
          socialMedia: platform,
          source: 'welcome screen',
        }),
      );
    }
  }

  handleCopyReferralLink = () => {
    const productGroup = this.getCurrentProduct();

    if (this.props.product === PRODUCT_TYPE.PG) {
      fireAnalyticsEvents({
        fbData: 'partner_copy_link',
        liData: 1764844,
      });
      trackReferral();
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.copy', {
          partnerID: this.props.user.id,
        }),
      );
      // new event
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.copy', {
          productGroup,
          partnerID: this.props.user.id,
          source: 'welcome screen',
        }),
      );
    }
    if (this.props.product === PRODUCT_TYPE.X) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.x.copy', {
          partnerID: this.props.user.id,
        }),
      );
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().clicked('partnerships.submerchant.referral.product_group.copy', {
          productGroup,
          partnerID: this.props.user.id,
          source: 'welcome screen',
        }),
      );
    }
  };

  render() {
    const { user, product, referralData, location } = this.props;
    let appIdColumn = [];
    let switchMerchantColumn = [];
    const referralUrl = referralData ? referralData[product].url : '';
    const isNonEmptyList = Array.isArray(this.props.items) && this.props.items.length > 0;
    const isFilterSearchUsed = location.search !== '';
    const shouldShowWelcomeScreen =
      !isNonEmptyList && !isFilterSearchUsed && !user.isPartner('pure_platform');

    if (user.isPartner('pure_platform')) {
      appIdColumn = [appId];
    } else if (user.isPartner('aggregator', 'fully_managed')) {
      switchMerchantColumn = [switchMerchantActionBtn(this.handleSwitchMerchant)];
    }

    if (user.isPartnerIntent()) {
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
      });
    }

    if (this.props.loading) {
      return (
        <tabbed-container>
          <content>
            <div class="sub-merchants-list">
              <Loader />
            </div>
          </content>
        </tabbed-container>
      );
    }
    return (
      <tabbed-container>
        <content>
          <div class="sub-merchants-list">
            <div className={`content-wrapper ${shouldShowWelcomeScreen ? 'partner-welcome' : ''}`}>
              {!shouldShowWelcomeScreen && (
                <div className="submerchant-filter-wrapper">
                  <ListFilter
                    form="SubmerchantListFilter"
                    type="link"
                    count={this.state.count}
                    onSubmit={this.search}
                    onSearchAnalytics={trackSearchAnalytics}
                    onClearAnalytics={trackClearAnalytics}
                    showAppIdFilter={user.isPartner('pure_platform')}
                  />
                  <button
                    class="btn btn-default export-all-btn"
                    onClick={this.onDownload}
                    disabled={this.state.affiliatesDownloading}
                  >
                    {!this.state.affiliatesDownloading ? (
                      <>
                        <i className="i i-download" />
                        <span>Export All (CSV)</span>
                      </>
                    ) : (
                      <>Exporting Affiliates...</>
                    )}
                  </button>
                </div>
              )}
              {!isNonEmptyList && isFilterSearchUsed ? (
                <div style={{ flex: 2, textAlign: 'center' }}>
                  <div>
                    <h3 class="sub-title">No Search results found</h3>
                  </div>
                </div>
              ) : (
                ''
              )}
              {isNonEmptyList && product === PRODUCT_TYPE.PG && (
                <DataTable
                  title="Sub Merchants"
                  count={this.state.count}
                  skip={this.state.skip}
                  paginate={this.paginate}
                  columns={[
                    this.name(user.isPartner('pure_platform')),
                    id,
                    email,
                    ...appIdColumn,
                    addedOn,
                    activationStatus,
                    settlementStatus,
                    ...switchMerchantColumn,
                  ]}
                  {...this.props}
                />
              )}
              {isNonEmptyList && product === PRODUCT_TYPE.X && (
                <DataTable
                  title="Sub Merchants"
                  count={this.state.count}
                  skip={this.state.skip}
                  paginate={this.paginate}
                  columns={[
                    this.xName(),
                    id,
                    email,
                    ...appIdColumn,
                    addedOn,
                    xVirtualAccountStatus,
                    xCurrentAccountStatus,
                  ]}
                  {...this.props}
                />
              )}

              {shouldShowWelcomeScreen && (
                <>
                  <div style={{ flex: 2, textAlign: 'center' }}>
                    <div>
                      <h1 class="main-title"> Welcome to Partner Dashboard</h1>
                      <h3 class="sub-title">Get started by adding merchants to Razorpay</h3>
                    </div>
                  </div>
                  <div style={{ flex: 3 }} class="action-area">
                    <div>
                      <div>
                        <div>
                          <img src="/dist/css/assets/onboarding/add-new-sub-merchants.png" />
                        </div>
                        <p>
                          <strong>Invite a merchant</strong> by adding their details
                        </p>
                        <div style={{ paddingTop: '20px' }}>
                          <button
                            class="btn btn-primary pull-right m-l"
                            onClick={this.handleAddMerchant}
                          >
                            <i class="i i-plus line-height-9" /> Add New Merchant
                          </button>
                        </div>
                      </div>
                      <ShowWhen
                        additionalCondition={(currentUser) =>
                          currentUser.isPartner() && currentUser.isPartner('reseller')
                        }
                      >
                        <div>
                          <div>
                            <img src="/dist/css/assets/onboarding/share-referral-link.png" />
                          </div>
                          <p>
                            Share the <strong>invite link</strong> on social media
                          </p>

                          <div class="social-share-btn-grp">
                            <CustomClipboard value={referralUrl}>
                              <button
                                class="btn btn-primary pull-right m-l"
                                onClick={this.handleCopyReferralLink}
                              >
                                <i class="i i-link line-height-9" /> Copy Link
                              </button>
                            </CustomClipboard>
                            <img
                              src="/img/social-media/fb.png"
                              onClick={() => this.shareReferralOn('fb', referralUrl)}
                            />
                            <img
                              src="/img/social-media/twitter.png"
                              onClick={() => this.shareReferralOn('twitter', referralUrl)}
                            />
                            <img
                              src="/img/social-media/whatsapp.png"
                              onClick={() => this.shareReferralOn('whatsapp', referralUrl)}
                            />
                          </div>
                        </div>
                      </ShowWhen>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        </content>
      </tabbed-container>
    );
  }
}

const getDispatchToProps = (productType = PRODUCT_TYPE.PG) => {
  return {
    fetchAll: (params) => {
      return fetchAll({
        ...params,
        product: productType,
      });
    },
    openModal,
    closeModal,
    switchMerchant,
    showNotification,
  };
};

export const PrimarySubMerchantList = connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    ...state.submerchants,
  }),
  getDispatchToProps(PRODUCT_TYPE.PG),
)(ProductSubMerchantsList);

export const XSubMerchantList = connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    ...state.submerchants,
  }),
  getDispatchToProps(PRODUCT_TYPE.X),
)(ProductSubMerchantsList);
