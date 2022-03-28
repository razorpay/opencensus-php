import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link } from 'react-router-dom';
import Time from 'common/ui/Time';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import { fetchHolidayList as fnFetchHolidayList } from 'merchant/reducers/settlements/details';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import { TIMELINE_EVENTS } from './utils';

const SettlementTimeline = ({
  events,
  data,
  entityType,
  settlementDetails,
  holidayList,
  openModal,
  fetchHolidayList,
}) => {
  const { transaction } = data;

  useEffect(() => {
    fetchHolidayList();
  }, []);
  const viewHolidayList = () => {
    openModal({
      size: 'small',
      component: <HolidayModal data={holidayList} />,
    });
  };
  const getEventBody = (event) => {
    switch (event) {
      case TIMELINE_EVENTS.PAYMENT_CAPTURED:
      case TIMELINE_EVENTS.REFUND_PROCESSED: {
        return (
          <React.Fragment key={event}>
            <div className="timeline-row success-row">
              <div>
                <i className="i i-done circle-check" />
              </div>
              <div className="capitalize">{event?.split('_').join(' ').toLowerCase()}</div>
            </div>
            <div className="initial-event-details timeline-sub-text">
              <Time value={data?.transaction?.created_at} format="DD MMM YYYY, hh:mm a" />
            </div>
          </React.Fragment>
        );
      }
      case TIMELINE_EVENTS.SCHEDULE_INFO: {
        const method = settlementDetails?.method;
        const holidaysLen = settlementDetails?.holidays?.length || 0;
        const schedule = parseInt(settlementDetails?.schedule || 0, 10) - holidaysLen;
        return (
          <React.Fragment key={event}>
            <div className="timeline-row">
              <div className="settlement-schedule-circle" />
              <div>Settlement schedule</div>
            </div>
            <div className="settlement-schedule-details timeline-sub-text">
              {entityType === 'payment' ? (
                <div>
                  <span className="capitalize">{method} </span>
                  <span>payment takes </span>
                  <strong>{schedule} working days</strong>
                </div>
              ) : (
                <div>
                  Takes <strong>{schedule} working days</strong> to get adjusted from your
                  settlement
                </div>
              )}
            </div>
          </React.Fragment>
        );
      }
      case TIMELINE_EVENTS.HOLIDAY_INFO: {
        return (
          <React.Fragment key={event}>
            <div className="timeline-row bank-holidays-container">
              <div className="bank-holdiays-bump">
                <div className="bank-holidays-semi-circle" />
                <div className="bank-holidays-circle" />
              </div>
              <div>
                <span> Bank Holidays </span>
                <span className="text-primary pointer" onClick={viewHolidayList}>
                  View Holidays
                </span>
              </div>
            </div>
            <div className="holiday-details timeline-sub-text">
              {settlementDetails?.holidays?.length > 1 ? (
                <ul>
                  {settlementDetails.holidays?.map((holiday) => (
                    <li key={holiday.date}>
                      <Time value={parseInt(holiday.date, 10)} format="DD MMM YYYY" />
                      <span> ({holiday.description})</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <>
                  <Time
                    value={parseInt(settlementDetails?.holidays?.[0].date, 10)}
                    format="DD MMM YYYY"
                  />
                  <span> ({settlementDetails.holidays?.[0].description})</span>
                </>
              )}
            </div>
          </React.Fragment>
        );
      }
      case TIMELINE_EVENTS.SETTLEMENT_INFO: {
        return settlementDetails.is_settled ? (
          <React.Fragment key={event}>
            <div className="timeline-row success-row">
              <div>
                <i className="i i-done circle-check" />
              </div>
              <div>
                <span className="capitalize">{entityType?.toLowerCase()}</span> Settled
              </div>
            </div>
            <div className="settled-at-details timeline-sub-text">
              <Time
                value={parseInt(settlementDetails?.settled_at, 10)}
                format="DD MMM YYYY, hh:mm a"
              />
              {transaction?.settlement?.utr ? (
                <div className="mt-6">
                  <span>UTR: </span>
                  <span>{transaction?.settlement?.utr}</span>
                </div>
              ) : null}
              <div className="mt-2">
                <Link to={`/settlements/${transaction?.settlement?.id}`}>
                  <code>{transaction?.settlement?.id}</code>
                </Link>
              </div>
            </div>
          </React.Fragment>
        ) : (
          <React.Fragment key={event}>
            <div className="timeline-row eligible-at-row">
              <div className="eligible-at-circle" />
              <div>Settlement Date</div>
            </div>
            <div className="eligible-at-details timeline-sub-text">
              <Time
                value={parseInt(settlementDetails?.eligible_at, 10)}
                format="DD MMM YYYY, hh:mm a"
              />
            </div>
          </React.Fragment>
        );
      }
      default: {
        return null;
      }
    }
  };

  return (
    <div className="settlement-timeline-container">
      {events?.map((event) => getEventBody(event))}
    </div>
  );
};

const mapStateToProps = (state) => state.settlement;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { closeModal: fnCloseModal, openModal: fnOpenModal, fetchHolidayList: fnFetchHolidayList },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementTimeline);
