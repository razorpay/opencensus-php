import React from 'react';
import Button from 'common/new-ui/Button';
import RTracking from 'react-tracking';
import { analyticsStatusMap } from './Cards/data';

const CaApplyAcknowledge = (props) => {
  const handleClose = () => {
    props.tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'okay_got_it',
        status: 'application_submit',
      }),
    );

    props.tracking.trackEvent(
      window.rzpQ.merchantActions().viewed('dashboard.neopricing_tracker', {
        status: analyticsStatusMap['created'],
      }),
    );

    props.onClose();
  };

  return (
    <div className="ca-apply-acknowledge">
      <div className="title">Application Submitted Sucessfully</div>
      <div className="info">
        Our executives will reach out to you soon for documents for your kyc approval.
      </div>
      <Button.Primary onClick={handleClose} className="full-width">
        Okay, Got It
      </Button.Primary>
    </div>
  );
};

export default RTracking({ page: 'RXNeoCaApplyAcknowledge' })(CaApplyAcknowledge);
