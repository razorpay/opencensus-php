import React from 'react';
import { CAPITAL_LINKS } from '../constants';
import Button from 'common/new-ui/Button';

function HelpSection({ applicationId }) {
  const raiseTicket = () => {
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
      setTimeout(() => {
        document.getElementsByName('request-description')[0].value = `${
          applicationId === 'new'
            ? ''
            : `[Loan Application ID:${applicationId}]`
        }I have a loan application related query`;
      }, 1000);
    }
  };

  const instructions = [
    {
      description: 'Need Help or Have Questions!',
      _cta: (
        <Button.Transparent onClick={raiseTicket}>
          Write to us!
        </Button.Transparent>
      ),
      _isExternalLink: true,
    },
    {
      _cta: (
        <a className="btn-link" href={CAPITAL_LINKS['faqs']} target="_blank">
          View FAQs
          <i className="i i-question-circle-o" />
        </a>
      ),
      _isExternalLink: true,
    },
  ];
  return (
    <div class="help-section">
      {instructions.map(instruction => (
        <div class="help-action-row">
          <div class="help-description-wrapper">
            {instruction.description && (
              <p class="instruction-description">{instruction.description}</p>
            )}
            {instruction._cta}
          </div>
          {instruction._isExternalLink && (
            <i className="i i-chevron-right text-primary" />
          )}
        </div>
      ))}
    </div>
  );
}

export default HelpSection;
