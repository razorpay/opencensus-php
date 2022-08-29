import React from 'react';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { openModal } from 'merchant_common/reducers/modals';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';

const EnableScheduledBanner = ({ openModal }) => {
  const handleEnableNow = () => {
    openModal({
      component: <ScheduledModal />,
      size: 'small',
      disableClose: true,
    });
  };

  return (
    <div className="enable-scheduled-small-banner">
      <p className="enable-scheduled-small-banner__header">
        <i className="i i-early-settlement settle-icon mr-5" />
        <span>Scheduled Settlements</span>
      </p>
      <p className="enable-scheduled-small-banner__content">
        Get your settlement balance on the same day, automatically.{' '}
        <Button.Transparent onClick={handleEnableNow}>Enable Now</Button.Transparent>
      </p>
    </div>
  );
};

export default connect(null, { openModal })(EnableScheduledBanner);
