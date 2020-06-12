import React from 'react';
import Banner from './components/Banner';
import Button from 'common/new-ui/Button';
import { APPLICATION_STATES } from './constants';

const getStateBanner = (loanApplicationDetails, ref, changeActiveState) => {
  const activeState = loanApplicationDetails.context.activeState;

  switch (activeState) {
    case 'CREDIT_PULL_PENDING':
      if (loanApplicationDetails.bureau_report_details.data.bureau_report) {
        if (
          loanApplicationDetails.bureau_report_details.data.bureau_report
            .score > 450
        ) {
          return (
            <Banner
              title="Congratulations"
              description="Based on your credit history, you are eligible for a loan."
              type="success"
            />
          );
        } else {
          return (
            <Banner
              title="Sorry!"
              description="Based on your credit history, you are not eligible for loan. Please apply later."
              type="error"
            />
          );
        }
      } else {
        return null;
      }
      break;
    case 'PREVERIFICATION_UPLOAD_PENDING':
      return (
        <Banner
          title="Congratulations"
          description={
            <span>
              Based on your credit history, you are eligible for a loan.
              <a
                class="link"
                onClick={() =>
                  changeActiveState(APPLICATION_STATES.CREDIT_PULL_PENDING)
                }
              >
                View Credit Report
              </a>
            </span>
          }
          type="success"
          ref={ref}
        />
      );
    case 'PREVERIFICATION_FAILED':
      return (
        <Banner
          title="Sorry, Document has been rejected"
          description="Please upload a valid document."
          type="error"
        />
      );
    case 'CREDIT_OFFER_GENERATED':
      if (
        !(
          loanApplicationDetails.accepted_offer_details.data &&
          loanApplicationDetails.accepted_offer_details.data.credit_offer_id
        )
      ) {
        return (
          <Banner
            title="Congratulations"
            description="Your loan application has been successfully approved. Kindly accept the loan offer so that we can quickly disburse the funds to your account."
            type="success"
          />
        );
      }
      return null;
    case 'CONTRACT_PENDING':
      const { agreement_details } = loanApplicationDetails;
      if (!(agreement_details.data && agreement_details.data.signers)) {
        return (
          <Banner
            title="Loan Offer Accepted!"
            description="Thank you for accepting the loan offer, Only couple of more steps to get the funds disbursed to your account."
            type="success"
          />
        );
      }
      return null;
    case 'SLOT_SELECTION_PENDING':
      const { schedule_details } = loanApplicationDetails;
      if (schedule_details.data && schedule_details.data.slot_timing) {
        console.log(schedule_details.data.slot_date);
        return (
          <Banner
            title="Slot Selection Confirmed!"
            description="Thank you for choosing a slot for document collection. You will receive a mail regarding the details"
            type="success"
          />
        );
      } else {
        return (
          <Banner
            title="Loan application is completed!"
            description="Your loan application has been completed successfully. You are now few steps away from getting your loan amount disbursed."
            type="success"
          />
        );
      }
    case 'DOCUMENT_COLLECTION_INITIATED':
      return (
        <Banner
          title="Slot selection confirmation!"
          description="Thank you for choosing the slot for doorstep collection. A confirmation mail has been sent to your registered email."
          type="success"
        />
      );
    default:
      return null;
  }
};

const LoanStatusBanner = props => {
  return getStateBanner(
    props.loanApplicationDetails,
    props.ref,
    props.changeActiveState
  );
};

export default LoanStatusBanner;
