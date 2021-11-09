import React from 'react';

import { titleCase, classList } from 'common/utils/rzp-utils';

import Time from 'common/ui/Time';
import Popover, { PopoverBody } from 'common/ui/Popover';

const statusMap = {
  activated: {
    labelClass: 'label-success',
    ctaText: 'Show Activation Form',
    showCtaAsButton: false,
    description: '',
    tooltipCta: '',
    tooltipMessage: 'Activated on ',
  },
  not_activated: {
    labelClass: 'label-muted',
    ctaText: 'Complete Activation Form',
    showCtaAsButton: true,
    description: '',
    tooltipCta: 'Open Activation Form',
    tooltipMessage: 'Please complete and submit the activation form to activate this account.',
  },
  verification_pending: {
    labelClass: 'label-pending',
    ctaText: 'View Activation Form',
    showCtaAsButton: false,
    description: 'Bank account verification can take up to 30 mins to complete.',
    tooltipCta: '',
    tooltipMessage:
      'We are still in the process to verify the bank account details.  It can take up to 30 mins to verify the details.',
  },
  verification_failed: {
    labelClass: 'label-danger',
    ctaText: 'Update Details',
    showCtaAsButton: true,
    description: '', // from backend
    tooltipCta: 'Update Details',
    tooltipMessage: '', // from backend
  },
};

const AccountStatusDetailsView = React.memo(
  ({ showActivationForm, activationStatus, errorDetails }) => {
    const status = activationStatus || 'not_activated';
    const { labelClass, ctaText, showCtaAsButton, description } = statusMap[status];

    return (
      <>
        <span class={`${labelClass} status-label label m-r`}>{titleCase(status)}</span>{' '}
        {(description || errorDetails) && (
          <div class="help-text" style={{ marginTop: '5px' }}>
            {description || errorDetails}
          </div>
        )}
        {showCtaAsButton ? (
          <div>
            <a class="m-t btn btn-primary btn-sm" onClick={showActivationForm}>
              {ctaText}
            </a>
          </div>
        ) : (
          <a onClick={showActivationForm}>{ctaText}</a>
        )}
      </>
    );
  },
);

const AccountStatusListView = React.memo(
  ({ showActivationForm, activationStatus, timeStamp, errorDetails }) => {
    const status = activationStatus || 'not_activated';
    const { labelClass, tooltipCta, tooltipMessage } = statusMap[status];

    return (
      <small class="help-content">
        <span>
          <span class={classList('ModeIndicator', labelClass)} />
          {titleCase(status)}
        </span>
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              {tooltipMessage || errorDetails}
              {status === 'activated' && <Time value={timeStamp} format="DD MMM YYYY, hh:mm:A" />}
              <br />
              {tooltipCta && (
                <button class="btn-link tooltip-cta" onClick={showActivationForm}>
                  {tooltipCta}
                </button>
              )}
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  },
);

export { AccountStatusDetailsView, AccountStatusListView };
