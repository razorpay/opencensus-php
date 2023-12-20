import SupportButton from 'merchant/components/Home/SupportButton';
import { Link } from 'react-router-dom';
import { activationDuration as predefinedActivationDuration } from 'merchant/helpers/data';

export const kycModalContent = (args = {}) => {
  const activationState = args.activationState;

  if (args.modalType === 'KYC_CLARIFICATION_SUBMIT_MODAL') {
    return {
      title: 'Partner KYC under review',
      subtitle: 'Clarifications successfully submitted',
      body: (
        <div>
          <p>Great, thank you for providing requested clarifications!</p>
          <p>
            We’ll review the form and get back to you in{' '}
            {args.activationDuration || predefinedActivationDuration}. Meanwhile, you can continue
            using partner dashboard.
          </p>
        </div>
      ),
      background: 'warning',
      button: (
        <button className="btn btn-primary" onClick={args.onGoToDashboard}>
          Go to Dashboard
        </button>
      ),
    };
  }

  if (args.modalType === 'PARTNER_KYC_BLOCKED_MODAL') {
    return {
      title: 'Complete your Merchant KYC First',
      body: (
        <div>
          <p>
            Kindly open the Home section on your dashboard and submit the required information for
            Merchant KYC before accessing the Partner KYC
          </p>
        </div>
      ),
      background: 'warning',
      button: (
        <button className="btn btn-primary" onClick={args.goToMerchantDashboard}>
          Go to Dashboard
        </button>
      ),
    };
  }

  if (args.modalType === 'MERCHANT_KYC_BLOCKED_MODAL') {
    return {
      title: 'Complete your Partner KYC First',
      body: (
        <div>
          <p>
            Kindly open the Partner section on your dashboard and submit the required information
            for Partner KYC before accessing the Merchant KYC
          </p>
        </div>
      ),
      background: 'warning',
      button: (
        <button className="btn btn-primary" onClick={args.onGoToDashboard}>
          Go to Partner Dashboard
        </button>
      ),
    };
  }

  switch (activationState) {
    case 'kyc_qualified_unactivated':
    case 'under_review': {
      return {
        title: 'Partner KYC Under Review',
        subtitle: 'Our team is reviewing your Partner KYC details',
        body: (
          <div>
            KYC review process usually takes 3-4 working days.
            <br />
            We will notify you if we require any clarifications on your KYC.
          </div>
        ),
        background: 'pending',
        button: (
          <button className="btn btn-primary" onClick={args.onGoToDashboard}>
            Back to Partner Dashboard
          </button>
        ),
      };
    }

    case 'needs_clarification': {
      return {
        title: 'Partner KYC Clarification',
        body: (
          <div>
            We need some clarification regarding your Partner KYC details. Please clarify at the
            earliest to get your KYC approved
          </div>
        ),
        background: 'pending',
        button: (
          <Link to="/partner/activation" onClick={() => args.onClose()} className="btn btn-primary">
            Update Details
          </Link>
        ),
      };
    }

    case 'rejected': {
      return {
        title: 'Partner Account Rejected',
        body: (
          <div>
            We can't support your business because it doesn't meet our compliance requirements.
            <br />
            Please raise a ticket to request for settlement of any payments accepted through your
            account
          </div>
        ),
        background: 'warning',
        button: (
          <SupportButton
            type="button"
            buttonLabel="Contact Support"
            category="merchant"
            openSection="account-activation"
          />
        ),
      };
    }

    default:
      return '';
  }
};
