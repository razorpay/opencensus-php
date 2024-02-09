import React from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';

import posRefer from 'assets/partner-dashboard/posRefer.svg';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import {
  AddMerchantSource,
  ProductListItemT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

import ProductListItem from './ProductListItem';
import ProductShimmer from './shimmer';

interface ReferralGuideT {
  partnerName: string;
  isFirstReferralDone: boolean;
  isFetching: boolean;
  handleReferClient: (source: AddMerchantSource, type?: string) => void;
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
  org,
}) => {
  const title = isFirstReferralDone
    ? `Good Job ${partnerName}!! Keep Referring`
    : 'Start Referring';
  const orgName = org?.business_name || 'Razorpay';
  const orgCode = org?.custom_code || 'rzp';
  const { isPartnershipsForPosEnabled } = usePartnerDashboardExperiments();

  const PRODUCT_LIST: ProductListItemT[] = [
    {
      icon: pgIcon,
      title: 'For Payment Product',
      subTitle: `Refer your clients to leading ${orgName}'s payment products`,
      ctaText: '+ Add New Client',
      onClickCTA: () => handleReferClient('referral-guide-pg', PRODUCT_TYPE.PG),
      disabled: PAYMENT_PRODUCT_DISABLED_STATUS[orgCode],
    },
  ];
  const bankingProduct = {
    icon: bankingIcon,
    title: 'For Banking Products',
    subTitle: (
      <>
        Refer your clients to next generation <span className="no-break">neo-banking</span> enabling
        faster payouts
      </>
    ),
    ctaText: '+ Add New Client',
    onClickCTA: () => handleReferClient('referral-guide-x', PRODUCT_TYPE.X),
    disabled: false,
  };
  const posProduct = {
    icon: posRefer,
    title: 'For POS Product',
    subTitle: `Refer your clients to leading ${orgName}'s POS payments products`,
    ctaText: '+ Add New Client',
    onClickCTA: () => handleReferClient('referral-guide-pos', PRODUCT_TYPE.POS),
    disabled: false,
  };
  if (isPartnershipsForPosEnabled) {
    PRODUCT_LIST.push(posProduct);
  } else {
    PRODUCT_LIST.push(bankingProduct);
  }
  const orgPrdList = {
    rzp: [...PRODUCT_LIST],
    curlec: [PRODUCT_LIST[0]],
  };

  const productList = orgPrdList[orgCode];

  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      {isFetching ? (
        <ProductShimmer />
      ) : (
        <div className="referral-guide-card">
          <div className="referral-guide">
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
          <div className="product-list">
            <img className="product-icon" src={subIcon} alt="" />
            <div className="product-container">
              {productList.map((product) => (
                <ProductListItem {...product} key={product.title} />
              ))}
            </div>
          </div>
        </div>
      )}
    </ErrorBoundary>
  );
};

export default ReferralGuide;
