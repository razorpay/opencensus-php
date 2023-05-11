import React from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';
import ProductShimmer from './shimmer';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  AddMerchantSource,
  ProductListItemT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ProductListItem from './ProductListItem';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

interface ReferralGuideT {
  partnerName: string;
  isFirstReferralDone: boolean;
  isFetching: boolean;
  handleReferClient: (source: AddMerchantSource, type?: string) => void;
  handleAggregatorApplyNow: () => void;
  isUserOwner: boolean;
  user: any;
  org: TODO_PD;
}

const assetBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards`;
const referralGuideIcon = `${assetBase}/referral-guide-icon.svg`;
const pgIcon = `${assetBase}/pg-icon.svg`;
const bankingIcon = `${assetBase}/banking-icon.svg`;
const subIcon = `${assetBase}/referral-guide-sub-icon.svg`;
const PAYMENT_PRODUCT_DISABLED_STATUS = {
  rzp: false,
  curlec: false,
};

export const ReferralGuide: React.FC<ReferralGuideT> = ({
  partnerName,
  isFirstReferralDone,
  isFetching,
  handleReferClient,
  handleAggregatorApplyNow,
  isUserOwner,
  user,
  org,
}) => {
  const title = isFirstReferralDone
    ? `Good Job ${partnerName}!! Keep Referring`
    : 'Start Referring';
  const orgName = org?.business_name || 'Razorpay';
  const orgCode = org?.custom_code || 'rzp';

  const PRODUCT_LIST: ProductListItemT[] = [
    {
      icon: pgIcon,
      title: 'For Payment Product',
      subTitle: `Refer your clients to leading ${orgName}'s payment products`,
      ctaText: '+ Add New Client',
      onClickCTA: () => handleReferClient('referral-guide-pg', PRODUCT_TYPE.PG),
      disabled: PAYMENT_PRODUCT_DISABLED_STATUS[orgCode],
    },
    {
      icon: bankingIcon,
      title: 'For Banking Products',
      subTitle: (
        <>
          Refer your clients to next generation <span className="no-break">neo-banking</span>{' '}
          enabling faster payouts
        </>
      ),
      ctaText: '+ Add New Client',
      onClickCTA: () => handleReferClient('referral-guide-x', PRODUCT_TYPE.X),
      disabled: false,
    },
  ];
  const orgPrdList = {
    rzp: [...PRODUCT_LIST],
    curlec: [PRODUCT_LIST[0]],
  };

  const isShowAggregatorCard =
    !localStorage.getItem('aggregatorApplicationSubmit') &&
    isUserOwner &&
    user?.isOnboardAsResellers;

  const productList = orgPrdList[orgCode];

  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      {isFetching ? (
        <ProductShimmer />
      ) : (
        <div className="referral-guide-card">
          <div className={`referral-guide ${!isShowAggregatorCard && 'referal-guide-new'}`}>
            <div className="referral-guide__title">{title}</div>
            <div className="referral-guide__sub-title">
              You can use multiple ways to refer merchants for any of the {orgName} products
            </div>
            <div className="referral-action">
              <div className="referral-action__icon">
                <img src={referralGuideIcon} alt="referral guide icon" />
              </div>
              <div className="referral-content">
                <div className="content-title">
                  <span>Start Referring </span>
                </div>
                <div className="content-sub-title">
                  Start referring clients, while you submit KYC details to activate commissions
                </div>
                <div
                  role="button"
                  className="referral-content__cta"
                  onClick={() => handleReferClient('referral-guide')}
                >
                  <Button>+ Refer New Client</Button>
                </div>
              </div>
            </div>
          </div>
          {isShowAggregatorCard ? (
            <div className="aggregator-card">
              <div className="agg-content">
                <div className="agg-title">
                  Do you also manage your merchant’s account and transactions?
                </div>
                <div className="agg-sub-desc">
                  If you want to integrate Razorpay payments on your platform and manage your
                  sub-merchants transactions, you can become an aggregator partner, read about the
                  features and requisites &nbsp;
                  <a href="https://razorpay.com/docs/partners/aggregators/" target="__blank">
                    here
                  </a>
                  .
                </div>
                <div
                  role="button"
                  className="referral-content__cta"
                  onClick={handleAggregatorApplyNow}
                >
                  <Button>Apply Now</Button>
                </div>
                <div className="agg-sub-desc-note">
                  * Requires &nbsp;
                  <a
                    href="https://razorpay.com/docs/partners/aggregators/partner-auth/"
                    target="__blank"
                  >
                    ( Partner Auth )
                  </a>{' '}
                  Integration
                </div>
              </div>
              <div className="agg-img-wrap" />
            </div>
          ) : (
            <div className="product-list">
              <img className="product-icon" src={subIcon} alt="" />
              <div className="product-container">
                {productList.map((product) => (
                  <ProductListItem {...product} key={product.title} />
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </ErrorBoundary>
  );
};

export default ReferralGuide;
