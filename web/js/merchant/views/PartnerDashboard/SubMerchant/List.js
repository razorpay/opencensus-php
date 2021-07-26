import { Fragment } from 'react';
import { connect } from 'react-redux';
import { NavLink, Route, Switch } from 'react-router-dom';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import RTracking from 'react-tracking';

import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';

import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import AddMerchant from './AddMerchant';
import ReferralBox from './ReferralBox';
import Announcement from 'merchant/components/Announcements/Instant';
import { XSubMerchantList, PrimarySubMerchantList } from './AccountsList';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobileResolution: state.app.isMobileResolution,
    ...state.submerchants,
  }),
  {
    openModal,
    closeModal,
    showNotification,
  },
)
@RTracking(() => window.rzpQ.component('SubMerchantsList'))
export default class SubMerchantsList extends React.Component {
  state = {};

  handleAddMerchant = () => {
    const { user } = this.props;
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.add', {
        partnerID: user.id,
      }),
    );
    this.props.openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={this.props.closeModal} />,
    });
  };

  handleShareReferralLink = () => {
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.referral', {
        partnerID: this.props.user.id,
      }),
    );

    this.props.openModal({
      size: 'med-large',
      component: (
        <ReferralBox
          closeModal={this.props.closeModal}
          referralData={this.state.referralData}
          tracking={this.props.tracking}
          partnerID={this.props.user.id}
          partnershipForXEnabled={this.props.user.isPartnershipForXEnabled}
        />
      ),
    });
  };

  constructor(props) {
    merchantFetch({
      url: 'merchant/referral',
      mode: 'live',
      method: 'post',
      data: {},
    })
      .then(({ data }) => {
        this.setState({
          referralData: data.referrals,
        });
      })
      .catch(() => {});
    super(props);
  }

  render() {
    const { user } = this.props;

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
          <header className='partner-dashboard-header'>
            <NavLink exact to="/partners/submerchants">
              Affiliate Razorpay Accounts
            </NavLink>
            <ShowWhen
              additionalCondition={(user) => user.isPartnershipForXEnabled}
            >
              <NavLink exact to="/partners/submerchants/x">
                Affiliate RazorpayX Accounts
              </NavLink>
            </ShowWhen>
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
                        <span> Share Referral Link</span>
                      </button>
                    </ShowWhen>
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
              <Switch>
                {
                  user.isPartnershipForXEnabled ?
                  <Route
                  path={`${this.props.match.path}/x`}
                  render={(props) => (
                    <XSubMerchantList
                      {...props}
                      product={PRODUCT_TYPE.X}
                      referralData={this.state.referralData}
                    />
                  )}
                  exact
                /> : ''
                }
                <Route
                  path={`${this.props.match.path}/`}
                  render={(props) => (
                    <PrimarySubMerchantList
                      {...props}
                      product={PRODUCT_TYPE.PG}
                      referralData={this.state.referralData}
                    />
                  )}
                />
              </Switch>
            </div>
          </content>
        </tabbed-container>
      </Fragment>
    );
  }
}
