import PropTypes from 'prop-types';
import { AsyncBtn } from 'common/new-ui/Button';
import ButtonTrans from '@razorpay/blade-old/src/atoms/Button';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { fetchSubmerchants } from 'merchant/reducers/collection';
import moment from 'moment';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { withRouter } from 'react-router-dom';

const DetailsAction = ({
  activation_status = null,
  kyc_access = null,
  submerchant,
  history,
  ...props
}) => {
  const submerchantId = submerchant.id.replace('acc_', '');

  let state = kyc_access?.state;
  const token_expiry = moment.unix(kyc_access?.token_expiry);
  const rejection_count = kyc_access?.rejection_count;
  let isHidden = false;
  let btnText = 'Request for KYC access';
  let pendingState = 'Sending KYC access request...';

  let title = ' You can request to perform KYC access';
  const waitingApprovalImg = '/dist/css/assets/partner-dashboard/waiting-approval.png';
  const defaultImg = '/dist/css/assets/partner-dashboard/req-by-email-1.png';
  let image = defaultImg;

  let description = (
    <>
      You need approval from your merchant to get access <span>to their KYC form</span>
    </>
  );

  const openKYCForm = () => {
    const isMWeb = isMobileAndTablet();
    if (isMWeb) {
      history.push(`/partners/submerchants/onboarding/${submerchant.id}/steps`);
    } else {
      history.push(`/partners/submerchants/${submerchant.id}/activation`);
    }
  };

  const sendKYCRequest = async () => {
    try {
      props.trackUserEvent('partnerships.dashboard.affiliate_account.pannel', {
        cta: 'request_access',
      });
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
    image = waitingApprovalImg;
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
      description =
        'Merchant has rejected your KYC access request multiple times. Now, only the merchant can perform their KYC through their merchant dashboard';
    }
  }

  if (
    ['activated', 'activated_mcc_pending', 'under_review', 'rejected'].includes(activation_status)
  ) {
    return null;
  }
  return (
    <div className="details-action-container">
      <div className="submerchant-details-action request-access-kyc">
        <div className="icon-container">
          <div className="icon">
            <img src="/dist/css/assets/partner-dashboard/razorpay-circle.svg" />
          </div>
        </div>
        <div className="details-container">
          {<strong className="title">{title}</strong>}
          <br />
          <p className="description">{description}</p>
          <div className={`${image === waitingApprovalImg ? 'waiting-approval' : 'default-img'}`}>
            <img src={image} />
          </div>
          {!isHidden && (
            <AsyncBtn.Primary showLoader={true} pendingState={pendingState} onClick={onClickAction}>
              {btnText}
            </AsyncBtn.Primary>
          )}
        </div>
      </div>

      <div className="submerchant-details-action ">
        <div className="icon-container">
          <div className="icon">
            <img src="/dist/css/assets/partner-dashboard/document-circle.svg" />
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
            href="https://razorpay.com/docs/payments/kyc"
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
            <img src="/dist/css/assets/partner-dashboard/rupee-circle.svg" />
          </div>
        </div>
        <div className="details-container">
          <strong className="title">Benefits of Merchant KYC</strong>
          <br />
          <p className="description">
            Your affiliate will start receiving payment settlements in their bank account once their
            KYC has been approved
          </p>
        </div>
      </div>
    </div>
  );
};

DetailsAction.propTypes = {
  activation_status: PropTypes.string,
  kyc_access: PropTypes.shape({
    state: PropTypes.string.isRequired,
    rejection_count: PropTypes.number.isRequired,
    token_expiry: PropTypes.string.isRequired,
  }),
};

const mapStateToProps = (_state) => ({});

const getDispatchToProps = (productType = PRODUCT_TYPE.PG) => {
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

export default connect(mapStateToProps, getDispatchToProps())(withRouter(DetailsAction));
