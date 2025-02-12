import React from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import Time from 'common/ui/Time';
import { titleCase, classList } from 'common/utils/rzp-utils';
import SupportButton from 'merchant/components/Home/SupportButton';

const statusMap = {
  activated: {
    labelClass: 'label-success',
    showToolTip: true,
    ctaText: 'Show Activation Form',
    showCtaAsButton: false,
    description: '',
    tooltipCta: '',
    tooltipMessage: 'Activated on ',
    showContactSupport: false,
  },
  not_activated: {
    labelClass: 'label-muted',
    showToolTip: true,
    ctaText: 'Complete Activation Form',
    showCtaAsButton: true,
    description: '',
    tooltipCta: 'Open Activation Form',
    tooltipMessage: 'Please complete and submit the activation form to activate this account.',
    showContactSupport: false,
  },
  verification_pending: {
    labelClass: 'label-pending',
    showToolTip: true,
    ctaText: 'View Activation Form',
    showCtaAsButton: false,
    description: 'Bank account verification can take up to 30 mins to complete.',
    tooltipCta: '',
    tooltipMessage:
      'We are still in the process to verify the bank account details.  It can take up to 30 mins to verify the details.',
    showContactSupport: false,
  },
  verification_failed: {
    labelClass: 'label-danger',
    showToolTip: true,
    ctaText: 'Update Details',
    showCtaAsButton: true,
    description: '', // from backend
    tooltipCta: 'Update Details',
    tooltipMessage: '', // from backend
    showContactSupport: false,
  },
  suspended: {
    labelClass: 'label-danger',
    showToolTip: false,
    showCtaAsButton: false,
    showContactSupport: false,
  },
  needs_clarification: {
    labelClass: 'label-muted',
    showToolTip: false,
    showCtaAsButton: false,
    showContactSupport: true,
  },
  under_review: {
    labelClass: 'label-muted',
    showToolTip: false,
    showCtaAsButton: false,
    showContactSupport: true,
  },
  rejected: {
    labelClass: 'label-danger',
    showToolTip: false,
    showCtaAsButton: false,
    showContactSupport: false,
  },
};

const AccountStatusDetailsView = React.memo(
  ({ showActivationForm, activationStatus, errorDetails, isCreationDisabled }) => {
    const status = activationStatus || 'not_activated';
    // TODO: quick fix unexpected activation status, remove once new key added
    if (!statusMap[status]) {
      return '-';
    }

    const { labelClass, ctaText, showCtaAsButton, description } = statusMap[status];
    const isCtaDisabled = showCtaAsButton && isCreationDisabled;

    return (
      <>
        <span className={`${labelClass} status-label label m-r`}>{titleCase(status)}</span>{' '}
        {(description || errorDetails) && (
          <div className="help-text" style={{ marginTop: '5px' }}>
            {description || errorDetails}
          </div>
        )}
        {showCtaAsButton ? (
          <>
            <br />
            <span>
              {isCtaDisabled && (
                <Popover align="top" theme="dark">
                  <PopoverBody>This action is not allowed for your business type</PopoverBody>
                </Popover>
              )}
              <button
                className="m-t btn btn-primary btn-sm"
                onClick={showActivationForm}
                disabled={isCtaDisabled}
              >
                {ctaText}
              </button>
            </span>
          </>
        ) : (
          <a onClick={showActivationForm}>{ctaText}</a>
        )}
      </>
    );
  },
);

const AccountStatusListView = React.memo(
  ({ showActivationForm, activationStatus, timeStamp, errorDetails, isCreationDisabled }) => {
    const status = activationStatus || 'not_activated';
    // TODO: quick fix unexpected activation status, remove once new key added
    if (!statusMap[status]) {
      return '-';
    }

    const { labelClass, showToolTip, tooltipCta, tooltipMessage, showContactSupport } =
      statusMap[status];
    const isCtaDisabled = isCreationDisabled;

    return (
      <small className="help-content">
        {showContactSupport ? (
          <SupportButton
            type="anchor"
            buttonLabel="Contact Support"
            category="merchant"
            openSection="account-activation"
          />
        ) : (
          <span>
            <span className={classList('ModeIndicator', labelClass)} />
            {titleCase(status)}
          </span>
        )}
        {showToolTip ? (
          <Popover align="top" theme="dark">
            <PopoverBody>
              <div data-testid="popover-body">
                {tooltipMessage || errorDetails}
                {status === 'activated' && <Time value={timeStamp} format="DD MMM YYYY, hh:mm:A" />}
                <br />
                {tooltipCta && (
                  <button
                    className="btn-link tooltip-cta"
                    onClick={showActivationForm}
                    disabled={isCtaDisabled}
                  >
                    {tooltipCta}
                  </button>
                )}
              </div>
            </PopoverBody>
          </Popover>
        ) : null}
      </small>
    );
  },
);

export { AccountStatusDetailsView, AccountStatusListView };
