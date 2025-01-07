import { useState } from 'react';
import ButtonTrans from '@razorpay/blade-old/src/atoms/Button';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import DefaultImg from 'assets/partner-dashboard/req-by-email-1.png';
import WaitingApprovalImg from 'assets/partner-dashboard/waiting-approval.png';
import { AsyncBtn } from 'common/new-ui/Button';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Image from 'common/ui/Image';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { fetchSubmerchants } from 'merchant/reducers/collection';
import { merchantFetch } from 'merchant/utils/ajax';
import { openKYCFormUtil } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import {
  PARTNERSHIPS_WEBSITE_LINKS,
  PRODUCT_TYPE,
} from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackAccountLevelAcceptedInvitesCta } from './utils/analytics';

// Note: this component is PG specific only.
const productType = PRODUCT_TYPE.PG;

const DetailsAction = ({
  activation_status = null,
  kyc_access = null,
  submerchant,
  isSubMerchantKYCAccess,
  user,
  ...props
}) => {
  const navigate = useNavigate();
  const { isPartnershipsInviteFlowEnabled } = usePartnerDashboardExperiments();
  const submerchantId = submerchant.id.replace('acc_', '');
  const [isActionLoading, setIsActionLoading] = useState(false);

  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry);
  const rejection_count = kyc_access?.rejection_count;
  let isHidden = false;
  let btnText = 'Request for KYC access';
  let pendingState = 'Sending KYC access request...';

  let title = 'You can send request for KYC access';
  let image = DefaultImg;

  let description = (
    <>
      You need approval from your merchant to get access <span>to their KYC form</span>
    </>
  );

  const openKYCForm = () => {
    if (!isActionLoading) {
      const isMWeb = isMobileAndTablet();
      setIsActionLoading(true);

      // Note: product type is PG as parent conditionally renders it only for PG.
      if (isPartnershipsInviteFlowEnabled) {
        trackAccountLevelAcceptedInvitesCta(submerchant, { productType, action: btnText });
      }
      openKYCFormUtil(isMWeb, navigate, submerchant, props.showNotification).then(() => {
        setIsActionLoading(false);
      });
    }
  };

  const sendKYCRequest = async () => {
    try {
      props.trackUserEvent('partnerships.dashboard.affiliate_account.pannel', {
        cta: 'request_access',
      });

      if (isPartnershipsInviteFlowEnabled) {
        trackAccountLevelAcceptedInvitesCta(submerchant, { productType, action: btnText });
      }

      await merchantFetch({
        url: 'partner/kyc_access_request',
        // mode: 'live',
        method: 'post',
        data: { entity_id: submerchantId },
      });

      props.getPannelData(); // refresh data of Pannel
      props.fetchSubmerchants(); // refresh table contents
      props.showNotification({
        type: 'success',
        message:
          'We have sent a mail to the merchant to approve your request. You will receive an email once the request has been approved',
      });
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors && errors[0],
      });
    }
  };

  let onClickAction = sendKYCRequest;
  const currentTime = moment();
  const isApprovalRequestExpired = currentTime.isAfter(token_expiry);
  if (state === 'pending_approval' && isApprovalRequestExpired) {
    state = 'expired';
  }

  if (state === 'pending_approval') {
    title = "Your merchant's approval for KYC access is pending";
    description = 'Your merchant would have received an email with approval link.';
    image = WaitingApprovalImg;
    isHidden = true;
  }
  if (state === 'expired') {
    description = 'Resend KYC request';
  }
  if (state === 'approved') {
    title = 'Merchant has approved your KYC access request';
    description = '';
    btnText = 'Perform KYC';
    pendingState = 'Opening KYC form ...';
    onClickAction = openKYCForm;
  }
  if (state === 'rejected') {
    const rejection_texts = {
      1: 'Merchant has rejected your KYC access request',
      2: "Merchant has rejected your KYC access request 2 times. You can request for access only one more time, if the merchant still doesn't approve, you wont be able to send the request again",
      3: 'Merchant has rejected your KYC access request multiple times. Now, only the merchant can perform their KYC through their merchant dashboard',
    };
    description = `${rejection_texts[rejection_count] || ''}`;
    btnText = 'Resend KYC request';
    if (rejection_count >= 3) {
      isHidden = true;
      title = 'You can not send request for KYC access';
      description =
        'Merchant has rejected your KYC access request multiple times. Now, only the merchant can perform their KYC through their merchant dashboard';
    }
  }

  if (
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
    ].includes(activation_status)
  ) {
    return null;
  }

  if (isSubMerchantKYCAccess) {
    title = 'You can perform Merchant KYC';
    description = '';
    btnText = 'Perform KYC';
    pendingState = 'Opening KYC form ...';
    onClickAction = openKYCForm;
  }
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      <div className="details-action-container">
        <div className="submerchant-details-action request-access-kyc">
          <div className="icon-container">
            <div className="icon">
              <img src={require("assets/partner-dashboard/razorpay-circle.svg")} />
            </div>
          </div>
          <div className="details-container">
            {<strong className="title">{title}</strong>}
            <br />
            <p className="description">{description}</p>
            <div className={`${image === WaitingApprovalImg ? 'waiting-approval' : 'default-img'}`}>
              <Image src={image} isWebP />
            </div>
            {!isHidden && (
              <AsyncBtn.Primary
                showLoader={true}
                pendingState={pendingState}
                onClick={onClickAction}
              >
                {btnText}
              </AsyncBtn.Primary>
            )}
          </div>
        </div>

        <div className="submerchant-details-action ">
          <div className="icon-container">
            <div className="icon">
              <img src={require("assets/partner-dashboard/document-circle.svg")} />
            </div>
          </div>
          <div className="details-container">
            <strong className="title">Documents for KYC</strong>
            <br />
            <p className="description">
              Various business and personal details of the merchant need to be provided in
              merchant&apos;s KYC
            </p>
            <a
              href={PARTNERSHIPS_WEBSITE_LINKS.PG_KYC_DOCS_LINK}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() =>
                props.trackUserEvent('partnerships.dashboard.affiliate_account.pannel', {
                  cta: 'know_more',
                })
              }
            >
              <ButtonTrans variant="secondary" size="small">
                Know More
              </ButtonTrans>
            </a>
          </div>
        </div>

        <div className="submerchant-details-action ">
          <div className="icon-container">
            <div className="icon">
              <img src={require("assets/partner-dashboard/rupee-circle.svg")} />
            </div>
          </div>
          <div className="details-container">
            <strong className="title">Benefits of Merchant KYC</strong>
            <br />
            <p className="description">
              Your affiliate will start receiving payment settlements in their bank account once
              their KYC has been approved
            </p>
          </div>
        </div>
      </div>
    </ErrorBoundary>
  );
};

DetailsAction.propTypes = {
  activation_status: PropTypes.string,
  kyc_access: PropTypes.shape({
    state: PropTypes.string.isRequired,
    rejection_count: PropTypes.number.isRequired,
    token_expiry: PropTypes.number.isRequired,
  }),
};

const mapStateToProps = (state) => ({ user: state.session.user });

const getDispatchToProps = () => {
  return {
    fetchSubmerchants: (params) => {
      return fetchSubmerchants({
        ...params,
        product: productType,
      });
    },
    showNotification,
  };
};

export default connect(mapStateToProps, getDispatchToProps())(DetailsAction);
