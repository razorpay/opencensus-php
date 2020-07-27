import React from 'react';
import {
  APPLICATION_STATE_DESCRIPTIONS,
  CAPITAL_LINKS,
  SIDE_NAVIGATION_STATE_GROUPS,
} from '../Loans/constants';
import Button from 'common/new-ui/Button';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';

function HelpSection({ applicationId, currentState, activeState }) {
  const _getParentStepLabel = step => {
    return Object.values(SIDE_NAVIGATION_STATE_GROUPS).filter(meta =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step)
    )[0].description;
  };

  const trackEvent = eventAction => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction,
      eventLabel: `${_getParentStepLabel(activeState)}:${
        APPLICATION_STATE_DESCRIPTIONS[activeState].short_description
      } | ${getApplicationProgressPercentage(currentState)}%`,
    });
  };

  const raiseTicket = () => {
    trackEvent('Right Info | Write to us CTA');
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
        if (
          rzpTicketSystem.setEnvironment &&
          rzpTicketSystem.setEnvironment.constructor === Function
        ) {
          rzpTicketSystem.setEnvironment('capital');
        }
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
        <a
          className="btn-link"
          href={CAPITAL_LINKS['faqs']}
          target="_blank"
          onClick={() => {
            trackEvent("Right Info | View FAQ's");
          }}
        >
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
