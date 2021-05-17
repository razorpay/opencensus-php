import React, { useState, useMemo } from 'react';
import PropTypes from 'prop-types';

import ToggleWithDescription from 'merchant/views/Capital/components/ToggleWithDescription';
import { NetbankingMeta, NetbankingHint } from './PreVerificationComponents';
import {
  PREVERIFICATION_VIEW_STATES,
  NOOP,
  PREVERIFICATION_NETBANKING_RETRY_LIMIT,
} from 'merchant/views/Capital/Loans/constants';

const NetBanking = ({ selected, disabled, errorCount, view, onClick, onRetry, showAltText }) => {
  const [loading, setLoading] = useState(false);

  const handleClick = () => {
    if (disabled) return;

    onClick();
  };

  const { title, description, className = '', icon = '', showMeta = false } = useMemo(() => {
    if (disabled) {
      return {
        title: 'Sorry, Bank statement upload failed',
        description: 'Please upload the bank statement using one of the other methods.',
        className: 'error',
        icon: 'i i-info-circle',
      };
    } else if (view === PREVERIFICATION_VIEW_STATES.PROCESSED) {
      return {
        title: 'Use Netbanking',
        description:
          'We are able to successfully fetched your 6 months bank statement. Please wait while we redirecting you to next step.',
        className: 'success',
        icon: 'i i-check-circle',
      };
    } else if (errorCount && errorCount < PREVERIFICATION_NETBANKING_RETRY_LIMIT) {
      return {
        title: 'Use Netbanking',
        description:
          'Kindly retry connecting to your netbanking account to fetch your 6 months bank statement. We DO NOT store your netbanking id or password.',
        className: 'warn',
        icon: 'i i-warning',
        showMeta: true,
      };
    } else if (showAltText) {
      return {
        title: 'Use Netbanking',
        description: 'Avoid mistakes, delays and downloading PDFs',
        className: '',
      };
    }

    return {
      title: 'Use Netbanking',
      description:
        'For the quickest loan processing, connect to your netbanking account to fetch your 6 months bank statement.',
      className: '',
      icon: '',
    };
  }, [selected, showAltText, errorCount, view, disabled]);

  return (
    <ToggleWithDescription
      title={title}
      description={description}
      meta={showMeta ? <NetbankingMeta text={description} onClick={onRetry} /> : null}
      expanded={showMeta}
      hint={disabled ? null : <NetbankingHint />}
      selected={selected}
      disabled={disabled}
      loading={loading}
      onClick={handleClick}
      style={{ marginBottom: 12 }}
      showRadioInput={true}
      radioPosition="left"
      className={className}
      icon={icon}
    />
  );
};

NetBanking.propTypes = {
  selected: PropTypes.bool,
  disabled: PropTypes.bool,
  showAltText: PropTypes.bool,
  errorCount: PropTypes.number,
  view: PropTypes.string,
  onClick: PropTypes.func,
  onRetry: PropTypes.func,
};

NetBanking.defaultProps = {
  selected: false,
  disabled: false,
  showAltText: false,
  errorCount: 0,
  view: PREVERIFICATION_VIEW_STATES.ACTIVE,
  onClick: NOOP,
  onRetry: NOOP,
};

export default NetBanking;
