import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import { getCustomURL } from 'merchant/components/DocsLink';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import PaymentSchedule from './PaymentSchedule';
import EntitySchedule from './EntitySchedule';

const paymentTypes = ['domestic', 'international'];

const SettlementScheduleV2 = (props) => {
  const { closeModal, openModal, holidayList, config: settlementConfig } = props;
  const [showExample, setShowExample] = useState(false);

  const schedules = settlementConfig?.data?.config?.schedules;

  const toggleExample = () => {
    setShowExample(!showExample);
  };

  const viewHolidayList = () => {
    openModal({
      size: 'small',
      component: <HolidayModal data={holidayList} />,
    });
  };

  return (
    <div>
      <ModalHeader title="Settlement Cycle" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className="settlement-cycle-overflow-box">
          <ul>
            {schedules?.payment && (
              <li>
                Payments default settlement cycle
                {paymentTypes.map((paymentType) => {
                  return (
                    <PaymentSchedule
                      paymentType={paymentType}
                      key={paymentType}
                      schedules={schedules.payment}
                    />
                  );
                })}
                <div className="schedule-info">T is the date of payment capture</div>
              </li>
            )}
            {(schedules?.refund?.default ||
              schedules?.reversal?.default ||
              schedules?.transfer?.default) && (
              <li>
                Other Settlement cycle
                {schedules?.refund?.default && (
                  <EntitySchedule entityType="refunds" schedule={schedules.refund.default} />
                )}
                {schedules?.reversal?.default && (
                  <EntitySchedule entityType="reversals" schedule={schedules.reversal.default} />
                )}
                {schedules?.transfer?.default && (
                  <EntitySchedule entityType="transfers" schedule={schedules.transfer.default} />
                )}
                <div className="schedule-info">T is the date of initiation</div>
              </li>
            )}
          </ul>
          <div className="settlement-example">
            <p>
              <strong>Note:</strong> <span className="yellow">Bank holidays</span> aren’t counted as
              working days.
              <br />
              <span onClick={toggleExample} className="btn-link">
                {showExample ? 'Hide' : 'View'} Example
                <i className={`i i-arrow-${showExample ? 'up' : 'down'}`} />
              </span>
            </p>
            {showExample && (
              <>
                <h6 className="grey">Assume settlement schedule for a payment is T+3</h6>
                <img
                  src="https://cdn.razorpay.com/static/assets/settlements/settlement-example-new.svg"
                  alt="settlement holiday example"
                  className="settlement-example-image"
                />
              </>
            )}
          </div>
        </div>
        <div className="settlement-cycle-btn-container">
          <div className="button-wrapper mr-8">
            <button onClick={viewHolidayList} className="btn btn-default full-width no-margin">
              List of Bank Holidays
            </button>
          </div>
          <div className="button-wrapper ml-8">
            <a
              href={getCustomURL('https://razorpay.com/settlement')}
              target="_blank"
              rel="noopener noreferrer"
            >
              <button type="button" className="btn btn-primary full-width no-margin">
                Settlement Guide
              </button>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => state.settlement;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal: fnCloseModal, openModal: fnOpenModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementScheduleV2);
