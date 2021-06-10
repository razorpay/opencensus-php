import { Fragment } from 'react';
import { connect } from 'react-redux';
import { Link, NavLink } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchSubmerchants as fetchAll } from 'merchant/reducers/collection';
import { switchMerchant } from 'merchant/reducers/session';
import { downloadSubmerchants } from 'merchant/reducers/submerchant';
import { merchantFetch } from 'merchant/utils/ajax';
import RTracking from 'react-tracking';

import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import Popover, { PopoverBody } from 'common/ui/Popover';

import ShowWhen from 'merchant/components/ShowWhen';
import { getTime } from 'common/ui/item';
import { ActivationStatusLabel, SubmerchantSettlementLabel } from 'merchant/components/StatusLabel';
import {
  submerchant as submerchantColumn,
  submerchantId as id,
  email as emailColumn,
} from 'common/ui/item/pair';
import { Modal, ModalContent, Header } from 'common/new-ui/Modal';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ListFilter from './ListFilter';
import Announcement from 'merchant/components/Announcements/Instant';
import { trackListEvents, trackSearchAnalytics, trackClearAnalytics, trackReferral } from '../ga';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import { mediaWindowUrl } from './components/SocialShare';
import CustomClipboard from 'common/ui/Clipboard/Custom';

const name = (isPurePlatform) => ({
  ...submerchantColumn,
  ...(isPurePlatform && {
    value: (item) => (
      <Link to={`/partners/submerchants/${item.id}/${item.application.id}`}>{item.name}</Link>
    ),
  }),
});

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
        <Popover align="top" theme="dark">
          <PopoverBody>Current status of merchant's activation request</PopoverBody>
        </Popover>
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
            <Popover align="right" theme="dark">
              <PopoverBody>
                The merchant can accept live payments but settlements will be on hold until KYC
                completion.
              </PopoverBody>
            </Popover>
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
        <Popover align="top" theme="dark">
          <PopoverBody>
            Current status of whether the merchant can receive the settlement
          </PopoverBody>
        </Popover>
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

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobileResolution: state.app.isMobileResolution,
    ...state.submerchants,
  }),
  {
    fetchAll,
    openModal,
    closeModal,
    switchMerchant,
    showNotification,
  },
)
@RTracking(() => window.rzpQ.component('SubMerchantsList'))
export default class SubMerchantsList extends ListContainer {
  state = {};

  handleAddMerchant = () => {
    const { user } = this.props;
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.add', {
        partnerID: user.id,
      }),
    );
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };
  handleShareReferralLink = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <ReferalBox
          closeModal={this.props.closeModal}
          referralUrl={this.state.referralUrl}
          shareReferralOn={this.shareReferralOn.bind(this)}
        />
      ),
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
  shareReferralOn(platform) {
    mediaWindowUrl({
      type: platform,
      url: this.state.referralUrl,
      title: 'Sign up on Razorpay!',
      description:
        "Start using a wide range of Razorpay's payment solutions and unlock growth for your business with just a few clicks. Go live in less than 10 minutes.",
    });
  }
  constructor(props) {
    merchantFetch({
      url: 'merchant/referral',
      mode: 'live',
      method: 'post',
      data: {},
    })
      .then(({ data }) => {
        this.setState({
          referralUrl: data.url,
        });
      })
      .catch(() => {});
    super(props);
  }

  render() {
    const { user } = this.props;
    let appIdColumn = [],
      switchMerchantColumn = [];

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
        className: props.isMobileResolution ? 'partner-onboarding-popup mobile-app-popup': 'partner-onboarding-popup',
      });
    }
    return (
      <Fragment>
        <Announcement user={this.props.user} mode={this.props.mode} />
        <tabbed-container>
          <header>
            <NavLink exact to="/partners/submerchants">
              Affiliate Accounts
            </NavLink>
          </header>
          <content>
            <div class="sub-merchants-list">
              <div>
                <HeaderAction>
                  <>
                    <ShowWhen
                      additionalCondition={(user) => user.isPartner() && user.isPartner('reseller')}
                    >
                      <button class="btn btn-link" onClick={this.handleShareReferralLink}>
                        <i className="i i-share" />
                        <span> Share Referral Link</span>
                      </button>
                    </ShowWhen>
                    <button
                      class="btn btn-default"
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
                    <ShowWhen
                      myRole="owner manager admin"
                      additionalCondition={(user) =>
                        user.isPartner() && !user.isPartner('pure_platform')
                      }
                    >
                      <button
                        class="btn btn-primary pull-right m-l"
                        onClick={this.handleAddMerchant}
                      >
                        <i class="i i-plus" />
                        Add New Accounts
                      </button>
                    </ShowWhen>
                  </>
                </HeaderAction>
              </div>
              {Array.isArray(this.props.items) && this.props.items.length > 0 && (
                <div class="content-wrapper">
                  <ListFilter
                    form="SubmerchantListFilter"
                    type="link"
                    count={this.state.count}
                    onSearchAnalytics={trackSearchAnalytics}
                    onClearAnalytics={trackClearAnalytics}
                    showAppIdFilter={user.isPartner('pure_platform')}
                  />
                  <DataTable
                    title="Sub Merchants"
                    count={this.state.count}
                    skip={this.state.skip}
                    paginate={this.paginate}
                    columns={[
                      name(user.isPartner('pure_platform')),
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
                </div>
              )}
              {Array.isArray(this.props.items) && this.props.items.length == 0 && (
                <ShowWhen
                  additionalCondition={(user) =>
                    user.isPartner() && !user.isPartner('pure_platform')
                  }
                >
                  <div class="content-wrapper partner-welcome">
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
                          additionalCondition={(user) =>
                            user.isPartner() && user.isPartner('reseller')
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
                              <CustomClipboard value={this.state.referralUrl}>
                                <button
                                  class="btn btn-primary pull-right m-l"
                                  onClick={() => {
                                    fireAnalyticsEvents({
                                      fbData: 'partner_copy_link',
                                      liData: 1764844,
                                    });
                                    trackReferral();
                                  }}
                                >
                                  <i class="i i-link line-height-9" /> Copy Link
                                </button>
                              </CustomClipboard>
                              <img
                                src="/img/social-media/fb.png"
                                onClick={() => this.shareReferralOn('fb')}
                              />
                              <img
                                src="/img/social-media/twitter.png"
                                onClick={() => this.shareReferralOn('twitter')}
                              />
                              <img
                                src="/img/social-media/whatsapp.png"
                                onClick={() => this.shareReferralOn('whatsapp')}
                              />
                            </div>
                          </div>
                        </ShowWhen>
                      </div>
                    </div>
                  </div>
                </ShowWhen>
              )}
            </div>
          </content>
        </tabbed-container>
      </Fragment>
    );
  }
}

const ReferalBox = ({ closeModal, referralUrl, shareReferralOn }) => (
  <div>
    <div style={{ padding: '10px' }}>
      <img src="/dist/css/assets/onboarding/share-referral-link.png" height="50" />{' '}
      <strong>
        <strong>Share Referral Link</strong>
      </strong>
      <button type="button" class="close" onClick={closeModal} style={{ marginTop: '10px' }}>
        <i class="i i-close" />
      </button>
    </div>
    <div style={{ padding: '14px' }}>
      <p>
        You <strong>get 0.1% commission for every transaction</strong> done by your affiliate
        accounts who signs up with this link.
      </p>
      <div class="input-group" style={{ marginTop: '20px' }}>
        <CustomClipboard value={referralUrl}>
          <input class="form-control input" value={referralUrl} style={{ width: '200px' }} />
          <button
            class="btn btn-primary"
            style={{
              width: '100px',
              borderRadius: '0px 2px 2px 0px',
            }}
            onClick={() => {
              fireAnalyticsEvents({
                fbData: 'partner_copy_link',
                liData: 1764844,
              });
              trackReferral();
            }}
          >
            Copy
          </button>
        </CustomClipboard>
      </div>
      <div class="social-share-btn-grp">
        <div>
          <strong>
            <p>Or Share Via</p>
          </strong>
        </div>
        <img src="/img/social-media/fb.png" onClick={() => shareReferralOn('fb')} />
        <img src="/img/social-media/twitter.png" onClick={() => shareReferralOn('twitter')} />
        <img src="/img/social-media/whatsapp.png" onClick={() => shareReferralOn('whatsapp')} />
      </div>
    </div>
  </div>
);
