import React, { Component } from 'react';
import RTracking from 'react-tracking';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';

const closeButtonClass = 'close';

const appListBusinessBanking = [
  {
    icon: '/dist/css/assets/products/blue-theme/current-account.svg',
    name: 'Current Accounts',
    link: 'https://razorpay.com/x/current-accounts/?ref=app-switcher',
    desc: 'Business Banking built for disruptors.',
    showForUnregisteredBusiness: false,
  },
  {
    icon: '/dist/css/assets/products/blue-theme/payout-link.svg',
    name: 'Payout Links',
    link: 'https://razorpay.com/x/payout-links/?ref=app-switcher',
    desc: 'Easy and instant payouts',
    showForUnregisteredBusiness: false,
  },
  {
    icon: '/dist/css/assets/products/blue-theme/vendor-payments.svg',
    name: 'Vendor Payments',
    link: 'https://razorpay.com/x/vendor-payments/?ref=app-switcher',
    desc: 'Automated Tax payments.',
    showForUnregisteredBusiness: false,
  },
  {
    icon: '/dist/css/assets/products/blue-theme/payouts.svg',
    name: 'Payouts',
    link: 'https://razorpay.com/x/payouts/?ref=app-switcher',
    desc: '24x7, Instant & Automated Payouts',
    showForUnregisteredBusiness: false,
  },
  {
    icon: '/dist/css/assets/products/blue-theme/payroll.svg',
    name: 'Payroll',
    link: 'https://razorpay.com/payroll/?ref=app-switcher',
    desc: 'Automate and execute payroll',
    showForUnregisteredBusiness: true,
  },
];

// eslint-disable-next-line no-unused-vars
const appListRiskAndFraud = [
  {
    icon: '/dist/css/assets/products/thirdwatch.svg',
    name: 'Thirdwatch',
    link: 'https://razorpay.com/thirdwatch/?ref=app-switcher',
    desc: 'Fight fraud with Artificial Intelligence',
    showForUnregisteredBusiness: true,
  },
  {
    icon: '/dist/css/assets/products/prepay-cod.svg',
    name: 'Prepay COD',
    link: 'https://razorpay.com/thirdwatch/prepay-cod/?ref=app-switcher',
    desc: 'Convert risky CoD orders to prepaid.',
    showForUnregisteredBusiness: true,
    new: true,
  },
];

const appListLending = [
  {
    icon: '/dist/css/assets/products/blue-theme/working-capital-loans.svg',
    name: 'Working Capital Loans',
    link: 'https://razorpay.com/capital/working-capital-loans/?ref=app-switcher',
    desc: 'Avail collateral-free business loans',
    showForUnregisteredBusiness: false,
  },
  {
    icon: '/dist/css/assets/products/blue-theme/corporate-credit-cards.svg',
    name: 'Corporate Credit Cards',
    link: 'https://razorpay.com/x/corporate-cards/?ref=app-switcher',
    desc: 'Instantly approved corporate credit card',
    showForUnregisteredBusiness: false,
  },
];

@RTracking(() => window.rzpQ.component('AppSwitcher'))
class AppSwitcher extends Component {
  handleShow = () => {
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

    return (
      <a
        href={app.link}
        target="_blank"
        key={app.name}
        onClick={() => this.handleClick(app.name)}
        className="item"
        rel="noreferrer noopener"
      >
        <img className="icon" src={app.icon} alt={app.name} />
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
    const { user } = this.props;
    return (
      <Dropdown closeButtonClass={closeButtonClass} onShow={this.handleShow} closeOnClick={false}>
        <DropdownTrigger
          className={`dropdown-toggle${
            !user.isAnnouncementTextEnabled && !user.isWhatsNewTextEnabled
              ? ' dropdown-toggle--large-icon'
              : ''
          }`}
        >
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
