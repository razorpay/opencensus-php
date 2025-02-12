import React from 'react';
import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';
import { FAQ_DATA_NEO } from './data';
import { FAQ_DATA_NITRO } from './data';
import { analyticsStatusMap } from './Cards/data';
import RTracking from 'react-tracking';

const Faq = (props) => {
  const { showNitroRXCAFlow } = props;
  const FAQ_DATA = showNitroRXCAFlow ? FAQ_DATA_NITRO : FAQ_DATA_NEO;

  const handleClick = () => {
    window.open('https://razorpay.com/links/neo-plan-terms-conditions', '_blank');
    props.tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'terms_and_conditions',
        status: props.caStatus ? analyticsStatusMap[props.caStatus] : 'application_pending',
      }),
    );
  };

  return (
    <div className="rx-faq">
      <div className="faq-title-container">
        <div className="faq-title">FAQ</div>
        {!showNitroRXCAFlow ? (
          <div className="t-n-c">
            <a onClick={handleClick}>
              Terms and conditions <i className="i i-external-link" />
            </a>
          </div>
        ) : null}
      </div>
      <hr />
      <Accordion>
        {FAQ_DATA.map((faq) => (
          <AccordionItem>
            <AccordionItemTitle>{faq.ques}</AccordionItemTitle>
            <AccordionItemContent>{faq.ans}</AccordionItemContent>
          </AccordionItem>
        ))}
      </Accordion>
    </div>
  );
};

export default RTracking({ page: 'RXNeoCaFaq' })(Faq);
