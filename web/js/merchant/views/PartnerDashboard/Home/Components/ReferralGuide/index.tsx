import React from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';
import ProductShimmer from './shimmer';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { OpenModalT, ProductListItemT } from '../../TypesDeclare/home';
import ProductListItem from './ProductListItem';

interface ReferralGuideT {
  partnerName: string;
  isFirstReferralDone: boolean;
  isFetching: boolean;
  openModal: OpenModalT;
  closeModal: () => void;
}

const assetBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards`;
const referralGuideIcon = `${assetBase}/referral-guide-icon.svg`;
const pgIcon = `${assetBase}/pg-icon.svg`;
const bankingIcon = `${assetBase}/banking-icon.svg`;
const subIcon = `${assetBase}/referral-guide-sub-icon.svg`;

export const ReferralGuide: React.FC<ReferralGuideT> = ({
  partnerName,
  isFirstReferralDone,
  isFetching,
  openModal,
  closeModal,
}) => {
  const title = isFirstReferralDone
    ? `Good Job ${partnerName}!! Keep Referring`
    : 'Start Referring';

  const handleReferClient = (type?: string) => {
    openModal({
      size: 'med-large',
      component: <AddMerchant closeModal={closeModal} addType={type} />,
    });
  };

  const productList: ProductListItemT[] = [
    {
      icon: pgIcon,
      title: 'For Payment Product',
      subTitle: "Refer your clients to leading Razorpay's payment products",
      ctaText: '+ Add new client',
      onClickCTA: () => handleReferClient(PRODUCT_TYPE.PG),
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
      ctaText: '+ Add new client',
      onClickCTA: () => handleReferClient(PRODUCT_TYPE.X),
    },
  ];
  return (
    <div>
      {isFetching ? (
        <ProductShimmer />
      ) : (
        <div className="referral-guide-card">
          <div className="referral-guide">
            <div className="referral-guide__title">{title}</div>
            <div className="referral-guide__sub-title">
              You can use multiple ways to refer merchants for any of the Razorpay products
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
                  onClick={() => handleReferClient()}
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
    </div>
  );
};

const getDispatchToProps = () => ({
  openModal,
  closeModal,
});

export default connect(null, getDispatchToProps())(ReferralGuide);
