import React, { useEffect, useState } from 'react';
import CCCreditLimit from 'assets/capital/cc-credit-limit.png';
import CCDeposits from 'assets/capital/cc-deposits.png';
import CCGetStarted from 'assets/capital/cc-get-started.png';
import XLogo from 'assets/capital/x-logo.png';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';

import Image from 'common/ui/Image';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import LoanEntity from 'merchant/models/Capital/BaseOrigination';

const LOS_CARDS_LINK = 'https://x.razorpay.com/cards/apply';
const CARDS_DASHBOARD_LINK = 'https://x.razorpay.com/cards';

const CorporateCards = ({ user }) => {
  const [loading, setLoading] = useState(true);
  const [ctaText, setCTAText] = useState('Apply Now');
  const [link, setLink] = useState(LOS_CARDS_LINK);
  const currentCtaText = loading ? 'Loading...' : ctaText;

  useEffect(() => {
    async function init() {
      if (user.isCardsEnabled) {
        setCTAText('Go to Cards Dashboard');
        setLink(CARDS_DASHBOARD_LINK);
      } else {
        try {
          const entity = new LoanEntity();
          const {
            data: { products = [] },
          } = await entity.fetchProducts();
          const cardProduct = products.find((d) => d.name === 'CARDS');
          const {
            data: { applications = [] },
          } = await entity.getApplications({
            product_id: cardProduct.id,
            owner_type: 'MERCHANT',
            owner_id: user.current,
            limit: 1,
          });
          const { status } = applications ? applications[0] : {};
          let text = '';
          let link = LOS_CARDS_LINK;

          if (!status || status === 'RZP_REJECTED' || status === 'RZP_CLOSED') {
            text = 'Apply Now';
          } else if (status === 'RZP_APPROVED') {
            text = 'Go to Cards Dashboard';
            link = CARDS_DASHBOARD_LINK;
          } else {
            text = 'Continue Applying';
          }

          setCTAText(text);
          setLink(link);
        } catch (err) {
          console.log('Failed to send analytics:', err.message);
        }
      }

      setLoading(false);
    }

    init();
  }, []);

  const handleOnClick = (position) => {
    let objectName = 'Corporate Cards Button';
    if (currentCtaText === 'Continue Applying') {
      objectName = `Continue Applying ${position}`;
    }
    try {
      analyticsTrack({
        screen: 'PG Dashboard | Corporate Cards | Overview',
        objectName,
        actionName: 'Clicked',
        properties: {
          location: 'Left Navigation | Corporate Cards',
          utm_campaign: `cardstab${position === 'Left' ? 1 : 2}`,
          utm_source: 'growth',
          utm_medium: 'RZPDashboard',
          ...getCommonSegmentProperties(window.rzp_user),
        },
      });
    } catch (e) {
      // handle error
    }
  };
  const getCTALinkWithUTMParams = (position) => {
    return `${link}?utm_campaign=cardstab${
      position === 'Left' ? 1 : 2
    }&utm_source=growth&utm_medium=RZPDashboard`;
  };
  const handleLeftCTAClick = () => {
    handleOnClick('Left');
  };

  const handleRightCTAClick = () => {
    handleOnClick('Right');
  };
  if (!user.isCardsLOSEnabled) return <Navigate to="/" replace />;
  return (
    <div className="corporate-cards-wrapper">
      <div className="corporate-cards__left">
        <div className="left__header">
          <div>
            <h3>RazorpayX</h3>
            <h1>Corporate Cards</h1>
          </div>
          <h4 className="subtext">Simplify your business payments</h4>
        </div>
        <div className="left__body">
          <ul>
            <li>
              <div>
                <p>Get Started for FREE</p>
                <p>Joining fee of ₹1499 will be waived off for Razorpay users if they apply now</p>
              </div>
              <Image src={CCGetStarted} isWebP />
            </li>
            <li>
              <div>
                <p>2x Credit Limit</p>
                <p>Your credit limit will increase faster to stay twice your spends</p>
              </div>
              <Image src={CCCreditLimit} isWebP />
            </li>
            <li>
              <div>
                <p>0 security deposits</p>
                <p>We require no personal guarantees or security deposits to get you started</p>
              </div>
              <Image src={CCDeposits} isWebP />
            </li>
          </ul>
          <a
            onClick={handleLeftCTAClick}
            className="cta secondary"
            href={getCTALinkWithUTMParams('Left')}
          >
            {currentCtaText}
          </a>
        </div>
      </div>
      <div className="corporate-cards__right">
        <div
          className="right__content"
          style={{ backgroundImage: "url('/dist/css/assets/capital/cc-preview.png')" }}
        >
          <Image src={XLogo} isWebP />
          <h1>Corporate Cards</h1>
          <div>
            <p>A real credit card for your business to make all the digital spends including:</p>
            <ul>
              <li>Online marketing and ad spends</li>
              <li>Recurring charges for SaaS & cloud</li>
              <li>International & other digital expenses</li>
            </ul>
            <a
              onClick={handleRightCTAClick}
              className="cta primary"
              href={getCTALinkWithUTMParams('Right')}
            >
              {currentCtaText}
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state, ownProps) => {
  const {
    session: { user },
  } = state;

  return {
    user,
    ...ownProps,
  };
};

export default connect(mapStateToProps)(CorporateCards);
