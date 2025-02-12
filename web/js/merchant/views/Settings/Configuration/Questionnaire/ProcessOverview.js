import React from 'react';
import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';

const ProcessOverview = () => {
  return (
    <div className="process-overview">
      <div className="main-title">Process Overview</div>
      <div className="">
        Please fill out with the following questionnaire to provide details and supporting documents
        for <strong>enabling international payment acceptance</strong> for your account.
      </div>

      <Accordion expandedKey={1}>
        <AccordionItem expanded={false}>
          <AccordionItemTitle>Why Another Questionnaire?</AccordionItemTitle>
          <AccordionItemContent>
            <ul>
              <li>
                International payments are associated with a higher risk of frauds and chargeback,
                hence it is governed by strict risk evaluations policies laid down by our banking
                partners
              </li>
              <li>
                This questionnaire allows us to collect important information regarding business
                details, current fraud protection systems, past international transactions
                experience to present a case to our banking partners
              </li>
            </ul>
          </AccordionItemContent>
        </AccordionItem>
      </Accordion>

      <Accordion>
        <AccordionItem>
          <AccordionItemTitle>Process Information</AccordionItemTitle>
          <AccordionItemContent>
            <ul>
              <li>The questionnaire is spread across 4 sections and 13 questions</li>
              <li>Estimated Time to Complete : 10 minutes</li>
              <li>Application Evaluation Time : 3-5 Business Days</li>
            </ul>
          </AccordionItemContent>
        </AccordionItem>
      </Accordion>

      <Accordion>
        <AccordionItem>
          <AccordionItemTitle>Tips for Favourable Evaluation</AccordionItemTitle>
          <AccordionItemContent>
            <ul>
              <li>
                Presence of domestic transactions of at least 90 days on our platform is viewed
                favorably by our banking partners
              </li>
              <li>
                Please provide as many available documents to present a strong case to the banking
                partners
              </li>
            </ul>
          </AccordionItemContent>
        </AccordionItem>
      </Accordion>
    </div>
  );
};

export default ProcessOverview;
