import React from 'react';
import Banner from '../components/Banner';
import Button from 'common/new-ui/Button';
import { APPLICATION_STATES } from './constants';

const getStateBanner = (loanApplicationDetails, ref, changeActiveState, isCashAdvanceProduct) => {
  const activeState = loanApplicationDetails.context.activeState;

  const pseudoState = loanApplicationDetails.context.pseudoState;

  // pseudoState takes precedence over loan application' state
  if (pseudoState) {
    switch (pseudoState) {
      case 'BANK_STATEMENT_PROCESSING_FAILED':
        return (
          <Banner
            title="Sorry, Bank statement upload failed"
            description="Please upload the bank statement using one of the other methods."
            type="error"
          />
        );
    }
  }

  switch (activeState) {
    case 'CREDIT_PULL_PENDING':
      if (
        loanApplicationDetails.bureau_report_details.data.bureau_report &&
        loanApplicationDetails.bureau_report_details.data.bureau_report.score
      ) {
        if (loanApplicationDetails.bureau_report_details.data.bureau_report.score > 450) {
          return (
            <Banner
              title="Congratulations"
              description={`Based on your credit history, you are eligible for ${
                isCashAdvanceProduct ? 'Cash Advance' : 'a loan'
              }.`}
              type="success"
            />
          );
        } else {
          return (
            <Banner
              title="Sorry!"
              description={`Based on your credit history, you are not eligible for ${
                isCashAdvanceProduct ? 'Cash Advance' : 'a loan'
              }. Please apply later.`}
              type="error"
            />
          );
        }
      } else {
        return null;
      }
      break;
    // case 'PREVERIFICATION_UPLOAD_PENDING':
    //   if (
    //     loanApplicationDetails.bureau_report_details.data.bureau_report &&
    //     loanApplicationDetails.bureau_report_details.data.bureau_report.ntc_score
    //   ) {
    //     return (
    //       <Banner
    //         title="Congratulations"
    //         description={
    //           <span>
    //             We couldn't find any credit records on your name. But, you may still be eligible for
    //             {isCashAdvanceProduct ? ' Cash Advance' : ' a loan'}. &nbsp;
    //             <Button.Transparent
    //               className="no-margin"
    //               onClick={() => changeActiveState(APPLICATION_STATES.CREDIT_PULL_PENDING)}
    //             >
    //               View Credit Report
    //             </Button.Transparent>
    //           </span>
    //         }
    //         type="success"
    //         ref={ref}
    //       />
    //     );
    //   }
    //   return (
    //     <Banner
    //       title="Congratulations"
    //       description={
    //         <span>
    //           Based on your credit history, you are eligible for{' '}
    //           {isCashAdvanceProduct ? 'Cash Advance' : 'a loan'}. &nbsp;
    //           <Button.Transparent
    //             className="no-margin"
    //             onClick={() => changeActiveState(APPLICATION_STATES.CREDIT_PULL_PENDING)}
    //           >
    //             View Credit Report
    //           </Button.Transparent>
    //         </span>
    //       }
    //       type="success"
    //       ref={ref}
    //     />
    //   );
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
            description={`Your ${
              isCashAdvanceProduct ? 'Cash Advance' : 'loan'
            } application has been successfully approved. Kindly accept the ${
              isCashAdvanceProduct ? 'Cash Advance' : 'loan'
            } offer so that we can quickly disburse the funds to your account.`}
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
            title={`${isCashAdvanceProduct ? 'Cash Advance' : 'Loan'} Offer Accepted!`}
            description={`Thank you for accepting the ${
              isCashAdvanceProduct ? 'Cash Advance' : 'loan'
            } offer, Only couple of more steps to get the funds disbursed to your account.`}
            type="success"
          />
        );
      }
      return null;
    case 'OFFLINE_DOCUMENT_COLLECTION_PENDING':
      return (
        <Banner
          title={`${isCashAdvanceProduct ? 'Cash Advance' : 'Loan'}  Offer Accepted!`}
          description={`Thank you for accepting the ${
            isCashAdvanceProduct ? 'Cash Advance' : 'loan'
          } offer, Only couple of more steps to get the funds disbursed to your account.`}
          type="success"
        />
      );

    // case 'SLOT_SELECTION_PENDING':
    //   const { schedule_details } = loanApplicationDetails;
    //   if (schedule_details.data && schedule_details.data.slot_timing) {
    //     return (
    //       <Banner
    //         title="Slot Selection Confirmed!"
    //         description="Thank you for choosing a slot for document collection. You will receive a mail regarding the details"
    //         type="success"
    //       />
    //     );
    //   } else {
    //     return (
    //       <Banner
    //         title={`${isCashAdvanceProduct ? 'Cash Advance' : 'Loan'} application is completed!`}
    //         description={`Your ${
    //           isCashAdvanceProduct ? 'Cash Advance' : 'loan'
    //         } application has been completed successfully. You are now few steps away from getting your ${
    //           isCashAdvanceProduct ? 'Cash Advance' : 'loan'
    //         } amount disbursed.`}
    //         type="success"
    //       />
    //     );
    //   }
    // case 'DOCUMENT_COLLECTION_INITIATED':
    //   return (
    //     <Banner
    //       title="Slot selection confirmation!"
    //       description="Thank you for choosing the slot for doorstep collection. A confirmation mail has been sent to your registered email."
    //       type="success"
    //     />
    //   );
    default:
      return null;
  }
};

const LoanStatusBanner = (props) => {
  return getStateBanner(
    props.loanApplicationDetails,
    props.ref,
    props.changeActiveState,
    props.isCashAdvanceProduct,
  );
};

export default LoanStatusBanner;
