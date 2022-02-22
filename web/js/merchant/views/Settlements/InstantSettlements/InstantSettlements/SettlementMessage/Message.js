import React from 'react';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { trackEnableNow } from '../../../trackEvents';

const Message = ({ heading, description, image, showEnableNowButton, openModal }) => {
  function handleEnableNowClick() {
    trackIS.clickCTAEnableNow();
    trackEnableNow('banner');
    openModal({
      component: <ScheduledModal />,
      size: 'small',
      disableClose: true,
    });
  }
  return (
    <>
      <div className="flex">
        <div>
          <img src={`/dist/css/assets/capital/${image}.svg`} />
        </div>
        <div className="payout-message-date--content">
          <div className="payout-message-date--heading">{heading}</div>
          <div className="payout-message-date--description">{description}</div>
        </div>
      </div>
      {showEnableNowButton && (
        <div className="payout-message-date--enable-btn">
          <Button.Secondary onClick={handleEnableNowClick}>
            <strong>
              Enable Now <i className="i i-chevron-right" />
            </strong>
          </Button.Secondary>
        </div>
      )}
    </>
  );
};

Message.propTypes = {
  heading: PropTypes.string,
  description: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
  image: PropTypes.string,
  openModal: PropTypes.func,
  showEnableNowButton: PropTypes.bool,
};

export default Message;
