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
import { TIMELINE_EVENTS, getSettlementTimeFormat } from './utils';
import { trackEvents as trackEventsAction } from 'merchant/reducers/trackEvents';
import ShowWhen from 'merchant/components/ShowWhen';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import LoaderDots from 'common/ui/LoaderDots';
import { getInitiatePointAndPageAndScreenName } from 'merchant/views/Transactions/v1/utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { SelfServeActionPages } from 'common/constant/enums';

const SettlementTimeline = ({
  events,
  data,
  entityType,
  settlementDetails,
  holidayList,
  openModal,
  fetchHolidayList,
  page = '',
  trackEventsAction,
  customSettlementLoading,
  adminAsMerchant,
  showCustomSettlDetails,
  bankSettleStatus,
  openedFrom,
  settlement_id,
}) => {
  const { transaction } = data;
  const { settlement } = transaction;
  const {
    method: settlementDetailsMethod,
    holidays,
    schedule: settlementDetailsSchedule,
    is_settled,
    settled_at,
    eligible_at,
    started_at,
  } = settlementDetails;

  useEffect(() => {
    fetchHolidayList().then((data) => {
      holidayList = data;
    });
  }, []);

  const viewHolidayList = () => {
    openModal({
      size: 'small',
      component: <HolidayModal holidayList={holidayList} />,
    });
  };
  const { initiatePage, screen, page: _page } = getInitiatePointAndPageAndScreenName();

  let INIT_POINT = 'payment-details';

  if (page === 'Refund Detail') {
    INIT_POINT = 'refund-details';
  } else if (page === 'Reversal Detail') {
    INIT_POINT = 'reversal-details';
  }

  const trackEvent = () => {
    if (page && page !== '') {
      trackEventsAction({
        objectName: 'settlement id',
        actionName: 'click',
        properties: {
          settlement_id: settlement?.id,
        },
        screen: page,
        toLumberjack: true,
      });
      const selfServeInitiateData = {
        selfServeAction: 'Settlement Details Fetched',
        page: _page,
        screen,
        props: {
          initiatePoint: INIT_POINT,
        },
      };
      if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
      if (
        settlement?.id !== settlement_id &&
        openedFrom !== 'settlement-details' &&
        initiatePage !== SelfServeActionPages.SettlementsReversals
      ) {
        selfServeTrackInitiate(selfServeInitiateData);
      }
    }
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
              <Time value={parseInt(started_at, 10)} format="DD MMM YYYY, hh:mm a" />
            </div>
          </React.Fragment>
        );
      }
      case TIMELINE_EVENTS.SCHEDULE_INFO: {
        const method = settlementDetailsMethod;
        const holidaysLen = holidays?.length || 0;
        const schedule = parseInt(settlementDetailsSchedule || 0, 10) - holidaysLen;
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
                  <strong>{schedule} working day(s)</strong>
                </div>
              ) : (
                <div>
                  Takes <strong>{schedule} working day(s)</strong> to get adjusted from your
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
              {holidays?.length > 1 ? (
                <ul>
                  {holidays?.map((holiday) => (
                    <li key={holiday.date}>
                      <Time value={parseInt(holiday.date, 10)} format="DD MMM YYYY" />
                      <span> ({holiday.description})</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <>
                  <Time value={parseInt(holidays?.[0].date, 10)} format="DD MMM YYYY" />
                  <span> ({holidays?.[0].description})</span>
                </>
              )}
            </div>
          </React.Fragment>
        );
      }
      case TIMELINE_EVENTS.SETTLEMENT_INFO: {
        return is_settled ? (
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
                value={parseInt(settled_at, 10)}
                format={getSettlementTimeFormat('DD MMM YYYY, hh:mm a')}
              />
              <ShowWhen
                additionalCondition={() =>
                  settlement?.utr && (!showCustomSettlDetails || adminAsMerchant)
                }
              >
                <div className="mt-6">
                  <span>UTR: </span>
                  <span>{settlement?.utr}</span>
                </div>
              </ShowWhen>
              <div className="mt-2">
                <Link
                  to={`/settlements/${settlement?.id}?init_point=${INIT_POINT}&init_page=${initiatePage}`}
                  aria-label="settlement link"
                  onClick={trackEvent}
                >
                  <code>{settlement?.id}</code>
                </Link>
              </div>
              <ShowWhen additionalCondition={() => customSettlementLoading}>
                <LoaderDots />
              </ShowWhen>
              <ShowWhen additionalCondition={() => showCustomSettlDetails}>
                <div className="mt-6">
                  <span>Final Settlement Reference no.: </span>
                  <span>{settlement?.id}</span>
                </div>
                <div className="mt-6">
                  <span>Bank Settlement Status: </span>
                  <span>
                    <SettlementStatusLabel status={bankSettleStatus} />
                  </span>
                </div>
              </ShowWhen>
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
                value={parseInt(eligible_at, 10)}
                format={getSettlementTimeFormat('DD MMM YYYY, hh:mm a')}
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

const mapStateToProps = (state) => {
  const { settlement, session } = state;
  return {
    settlement: settlement.settlement,
    user: session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      closeModal: fnCloseModal,
      openModal: fnOpenModal,
      fetchHolidayList: fnFetchHolidayList,
      trackEventsAction,
      showNotification,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementTimeline);
