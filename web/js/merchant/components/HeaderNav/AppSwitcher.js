import React, { Component } from 'react';
import LazyLoad, { forceCheck } from 'react-lazyload';
import RTracking from 'react-tracking';

import CorporateCreditCards from 'assets/products/blue-theme/corporate-credit-cards.svg';
import CurrentAccount from 'assets/products/blue-theme/current-account.svg';
import PayoutLink from 'assets/products/blue-theme/payout-link.svg';
import Payouts from 'assets/products/blue-theme/payouts.svg';
import Payroll from 'assets/products/blue-theme/payroll.svg';
import RupeeSolidRounded from 'assets/products/blue-theme/rupee-solid-rounded.svg';
import VendorPayments from 'assets/products/blue-theme/vendor-payments.svg';
import PrepayCod from 'assets/products/prepay-cod.svg';
import Thirdwatch from 'assets/products/thirdwatch.svg';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { utmCampaignMap, utmMediumMap, utmSourceMap } from 'merchant/helpers/x/updateUtmCookie';

const closeButtonClass = 'close';

const appListBusinessBanking = [
  {
    icon: CurrentAccount,
    name: 'Current Accounts',
    link: 'https://razorpay.com/x/current-accounts/',
    desc: 'Business Banking built for disruptors.',
    showForUnregisteredBusiness: false,
  },
  {
    icon: PayoutLink,
    name: 'Payout Links',
    link: 'https://razorpay.com/x/payout-links/',
    desc: 'Easy and instant payouts',
    showForUnregisteredBusiness: false,
  },
  {
    icon: VendorPayments,
    name: 'Vendor Payments',
    link: 'https://razorpay.com/x/vendor-payments/',
    desc: 'Automated Tax payments.',
    showForUnregisteredBusiness: false,
  },
  {
    icon: Payouts,
    name: 'Payouts',
    link: 'https://razorpay.com/x/payouts/',
    desc: '24x7, Instant & Automated Payouts',
    showForUnregisteredBusiness: false,
  },
  {
    icon: Payroll,
    name: 'Payroll',
    link: 'https://razorpay.com/payroll/',
    desc: 'Automate and execute payroll',
    showForUnregisteredBusiness: true,
  },
];

// eslint-disable-next-line no-unused-vars
const appListRiskAndFraud = [
  {
    icon: Thirdwatch,
    name: 'Thirdwatch',
    link: 'https://razorpay.com/thirdwatch/',
    desc: 'Fight fraud with Artificial Intelligence',
    showForUnregisteredBusiness: true,
  },
  {
    icon: PrepayCod,
    name: 'Prepay COD',
    link: 'https://razorpay.com/thirdwatch/prepay-cod/',
    desc: 'Convert risky CoD orders to prepaid.',
    showForUnregisteredBusiness: true,
    new: true,
  },
];

const appListLending = [
  {
    icon: RupeeSolidRounded,
    name: 'Line of Credit',
    link: 'https://razorpay.com/x/line-of-credit/',
    desc: 'Better short-term - Use, Repay, Repeat',
    showForUnregisteredBusiness: false,
  },
  {
    icon: CorporateCreditCards,
    name: 'Corporate Credit Cards',
    link: 'https://razorpay.com/x/corporate-cards/',
    desc: 'Instantly approved corporate credit card',
    showForUnregisteredBusiness: false,
  },
];

@RTracking(() => window.rzpQ.component('AppSwitcher'))
class AppSwitcher extends Component {
  handleShow = () => {
    forceCheck();
    const { tracking, user } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().clicked('dashboard.appswitcher', {
        menu_title: 'App Switcher',
        session_id: window.session_id,
      }),
    );

    [...appListBusinessBanking, ...appListLending].forEach(
      ({ name, showForUnregisteredBusiness }) => {
        if (user.isUnregisteredBusiness && !showForUnregisteredBusiness) return;

        tracking.trackEvent(
          window.rzpQ.onbr().success('dashboard.appswitcher.app_shown', {
            app_name: name,
            session_id: window.session_id,
          }),
        );
      },
    );
  };

  handleClick = (appName) => {
    const { tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('dashboard.appnavigation', {
        app_name: appName,
        session_id: window.session_id,
      }),
    );
  };

  getItem = (app) => {
    if (this.props.user.isUnregisteredBusiness && !app.showForUnregisteredBusiness) {
      return null;
    }

    const appUrl = new URL(app.link);
    appUrl.searchParams.append('ref', 'app-switcher');
    appUrl.searchParams.append('utm_campaign', utmCampaignMap.APP_SWITCHER);
    appUrl.searchParams.append('utm_source', utmSourceMap.PG);
    appUrl.searchParams.append('utm_medium', utmMediumMap.DASHBOARD);

    return (
      <a
        href={appUrl.href}
        target="_blank"
        key={app.name}
        onClick={() => this.handleClick(app.name)}
        className="item"
        rel="noreferrer noopener"
      >
        <LazyLoad height={25} once>
          <img className="icon" src={app.icon} alt={app.name} />{' '}
        </LazyLoad>
        <div className="info">
          <span className={`title ${app.new ? 'new' : null}`}>
            {app.name}
            <span className="arrow" />
          </span>
          <span className="desc">{app.desc}</span>
        </div>
      </a>
    );
  };

  render() {
    return (
      <Dropdown closeButtonClass={closeButtonClass} onShow={this.handleShow} closeOnClick={false}>
        <DropdownTrigger className="dropdown-toggle dropdown-toggle--large-icon">
          <i className="i i-app-switcher" />
        </DropdownTrigger>
        <DropdownContent>
          <div className="dropdown-menu app-switcher-dropdown">
            <div className="close-block">
              <span>Razorpay Apps</span>
              <i className={`i i-close ${closeButtonClass}`} />
            </div>
            <div className="column">
              <div className="block">
                <div className="heading">Business Banking</div>
                {appListBusinessBanking.map((app) => this.getItem(app))}
              </div>
            </div>
            <div className="column">
              <div className="block">
                <div className="heading">Lending</div>
                {appListLending.map((app) => this.getItem(app))}
              </div>
            </div>
          </div>
        </DropdownContent>
      </Dropdown>
    );
  }
}

export default AppSwitcher;
