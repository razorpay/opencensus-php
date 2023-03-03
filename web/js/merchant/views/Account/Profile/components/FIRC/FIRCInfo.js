import { useCallback, memo } from 'react';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

const FIRCInfo = ({ isInvalidDate, isJanMonth, closeModal }) => {
  const openSupport = useCallback(() => {
    closeModal();
    CreateTicketEmitter.emit('create-ticket', 'tickets');
  }, [closeModal]);

  return (
    <div className="info-container">
      {isInvalidDate ? (
        <div>
          To get FIRSs for transactions before June 2021, please
          <b>
            <a onClick={openSupport}> reach out </a>
          </b>
          to our support team and we will get back to you at the earliest.
        </div>
      ) : isJanMonth ? (
        <div>
          We are facing 3 weeks delay in updating your Jan 2023 FIRS due to system upgrade at our
          banking partner&apos;s end. Apologies for inconvenience caused.
        </div>
      ) : (
        <div>
          FIRS will be available for only international transactions and not for domestic card
          transactions. Will be available by <b>first half of the next month. </b>
        </div>
      )}
    </div>
  );
};

export default memo(FIRCInfo);
