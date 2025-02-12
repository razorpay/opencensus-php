import React, { useEffect } from 'react';
import RazorpayXLogoWhite from 'assets/razorpay-x-logo-white.svg';
import RXPayrollLogoWhite from 'assets/rx-payroll-logo-white.svg';
import RXCABullet from 'assets/rxca-bullet.svg';
import RXCADashboardBG from 'assets/rxca-dashboard-bg.svg';

const RXPayrollMoonshineModal = ({ hideModal, tracking }) => {
  const content = [
    'Direct salary transfers to employees',
    'Automated compliance payments and filings',
    'Reimbursements and attendance modules',
    'Easy to use employee self-serve dashboard',
    'Access to best-in-class Group Health Insurance for your team',
  ];

  const trackCTAClick = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('merchant_dashboard.exclusive_offers.payroll_modal'),
    );
  };

  useEffect(() => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().viewed('merchant_dashboard.exclusive_offers.payroll_modal'),
    );
  }, []);

  return (
    <div id="hubspot-ca-form-modal">
      <button type="button" className="close" onClick={hideModal}>
        <i className="i i-close" />
      </button>
      <div className="razorpayx-announcement-details">
        <div className="section">
          <div className="left-section">
            <div className="logo-header">
              <img className="rx-logo" src={RazorpayXLogoWhite} alt="Razorpay X" />
              <img className="rx-payroll-logo" src={RXPayrollLogoWhite} alt="Payroll" />
            </div>
            <h3 className="heading">
              Get <span>10 lakhs worth of credits</span> when you use RazorpayX Payroll for 3 months
            </h3>
            <ul className="list">
              {content.map((data) => (
                <li key={data}>
                  <img src={RXCABullet} />
                  <span>{data}</span>
                </li>
              ))}
            </ul>
            <div className="btn-wrapper">
              <a
                href="http://payroll.razorpay.com/sso?utm_source=moonshine&utm_medium=pgdashboard"
                target="_blank"
                rel="noreferrer noopener"
                className="btn btn-primary Button--primary Button"
                onClick={trackCTAClick}
              >
                Sign up for free
              </a>
            </div>
            <div className="terms-and-conditions">
              * Applicable on XPayroll Pro Plan. Rs. 10 lakhs worth of payment credits apply after 3
              months of payroll execution
            </div>
          </div>
          <div className="right-section">
            <img src={RXCADashboardBG} alt="razorpayx-payroll" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default RXPayrollMoonshineModal;
