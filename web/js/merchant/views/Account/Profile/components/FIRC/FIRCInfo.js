import React from 'react';

import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

const openSupport = (closeModal) => {
  closeModal();
  CreateTicketEmitter.emit('create-ticket', 'tickets');
};

const FIRCInfo = (props) => {
  const { isInvalidDate, closeModal } = props;

  return (
    <div className="info-container">
      {!isInvalidDate ? (
        <>
          <span>Not able to view your FIRC here? FIRCs are usually issued in the</span>
          <b> first week of every month.</b>
          <span> If you still need any help, please </span>
          <b>
            <a
              onClick={() => {
                openSupport(closeModal);
              }}
            >
              reach out
            </a>
          </b>
          <span> to our support team.</span>
        </>
      ) : (
        <>
          <span>To get FIRCs for transactions before June 2021, please </span>
          <b>
            <a
              onClick={() => {
                openSupport(closeModal);
              }}
            >
              reach out
            </a>
          </b>
          <span> to our support team and we will get back to you at the earliest.</span>
        </>
      )}
    </div>
  );
};

export default React.memo(FIRCInfo);
