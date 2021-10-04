import { useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function WriteToUsPopup({ businessName, supportFlags, closeModal, rzpTicketSystem, id, history }) {
  const data = {
    cta_list: supportFlags.cta_list,
    message_body: supportFlags.message_body,
  };
  const msg = data.message_body;
  const handleContinueWithTicketClick = () => {
    analyticsTrack({
      objectName: 'SLA Information pop up CTA',
      actionName: 'Clicked',
      screen: 'homepage',
      properties: {
        cta: 'Continue With Ticket',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    closeModal();
    rzpTicketSystem.openModal(`#${id}`);
  };
  const handleFaqs = () => {
    analyticsTrack({
      objectName: 'SLA Information pop up CTA',
      actionName: 'Clicked',
      screen: 'homepage',
      properties: {
        cta: 'Bworse Faqs',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    window.open('https://razorpay.com/knowledgebase/#merchant', '_blank');
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'SLA information pop up screen',
      actionName: 'viewed',
      screen: 'homepage',
      properties: {
        ctas: supportFlags.cta_list,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, [supportFlags.cta_list]);

  const MAP = {
    continue_with_ticket: (
      <button className="btn btn-secondary " type="button" onClick={handleContinueWithTicketClick}>
        Continue with Ticket
      </button>
    ),
    complete_activation_form: (
      <button
        className="btn btn-primary "
        type="button"
        onClick={() => {
          analyticsTrack({
            objectName: 'SLA Information pop up CTA',
            actionName: 'Clicked',
            screen: 'homepage',
            properties: {
              cta: 'Complete KYC',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          history.push('/onboarding/steps');
          closeModal();
        }}
      >
        Complete KYC
      </button>
    ),
    fill_activation_form: (
      <button
        className="btn btn-primary "
        type="button"
        onClick={() => {
          analyticsTrack({
            objectName: 'SLA Information pop up CTA',
            actionName: 'Clicked',
            screen: 'homepage',
            properties: {
              cta: 'Complete KYC',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          history.push('/onboarding/steps');
          closeModal();
        }}
      >
        Complete KYC
      </button>
    ),
    needs_clarification: (
      <button
        className="btn btn-primary "
        type="button"
        onClick={() => {
          analyticsTrack({
            objectName: 'SLA Information pop up CTA',
            actionName: 'Clicked',
            screen: 'homepage',
            properties: {
              cta: 'Complete KYC',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          history.push('activation');
          closeModal();
        }}
      >
        Complete KYC
      </button>
    ),
    faqs: (
      <button className="btn btn-primary" type="button" onClick={handleFaqs}>
        Browse FAQs
      </button>
    ),
  };
  data.cta_list = data.cta_list.map((cta) => MAP[cta]);
  return (
    <div className="write-to-us">
      <div className="write-to-us-heading-container">
        <h3 className="write-to-us-heading-container write-to-us-heading">
          Hey {businessName} <i onClick={closeModal} class="i i-close" />
        </h3>
      </div>
      <p className="write-to-us-content">{msg}</p>
      <div className="write-to-us-button-container">{data.cta_list}</div>
    </div>
  );
}

export default withRouter(WriteToUsPopup);
