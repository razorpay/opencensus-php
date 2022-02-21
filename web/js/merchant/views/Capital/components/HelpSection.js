import React from 'react';
import { CAPITAL_LINKS } from '../Loans/constants';
import Button from 'common/new-ui/Button';
import getApplicationProgressPercentage from '../utils/ProgressPercentageCalculator';
import { isCashAdvanceProduct } from '../utils';
import { CreateTicketEmitter } from '../../TicketSupport/utils';

function HelpSection({
  applicationId,
  currentState,
  activeState,
  applicationConfiguration,
  product,
}) {
  const _getParentStepLabel = (step) => {
    return Object.values(applicationConfiguration.getSideNavigationStateGroups()).filter((meta) =>
      Object.values(meta.steps)
        .reduce((acc, curr) => [...acc, ...curr], [])
        .includes(step),
    )[0].description;
  };

  const trackEvent = (eventAction) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction,
      eventLabel: `${_getParentStepLabel(activeState)}:${
        applicationConfiguration.getApplicationStateDescriptions()[activeState].short_description
      } | ${getApplicationProgressPercentage(
        currentState,
        applicationConfiguration.getApplicationStateGroups(),
      )}%`,
    });
  };

  const raiseTicket = () => {
    trackEvent('Right Info | Write to us CTA');
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      if (
        rzpTicketSystem.setEnvironment &&
        rzpTicketSystem.setEnvironment.constructor === Function
      ) {
        rzpTicketSystem.setEnvironment('capital');
      }
      CreateTicketEmitter.emit('create-ticket', 'tickets');

      setTimeout(() => {
        document.getElementsByName('request-description')[0].value = `${
          applicationId === 'new' ? '' : `[${product} Application ID:${applicationId}]`
        }I have a loan application related query`;
      }, 1000);
    }
  };

  const instructions = [
    {
      description: 'Need Help or Have Questions!',
      _cta: <Button.Transparent onClick={raiseTicket}>Write to us!</Button.Transparent>,
      _isExternalLink: true,
    },
    {
      _cta: (
        <a
          className="btn-link"
          href={isCashAdvanceProduct(product) ? CAPITAL_LINKS.ca_faqs : CAPITAL_LINKS.faqs}
          target="_blank"
          rel="noreferrer noopener"
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
      {instructions.map((instruction) => (
        // eslint-disable-next-line react/jsx-key
        <div class="help-action-row">
          <div class="help-description-wrapper">
            {instruction.description && (
              <p class="instruction-description">{instruction.description}</p>
            )}
            {instruction._cta}
          </div>
          {instruction._isExternalLink && <i className="i i-chevron-right text-primary" />}
        </div>
      ))}
    </div>
  );
}

export default HelpSection;
