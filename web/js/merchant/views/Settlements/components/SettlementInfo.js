import React, { Fragment, Component } from 'react';
import moment from 'moment';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import SettlementOverview from 'merchant/views/Transactions/Payments/components/SettlementOverview';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import SettlementTimeline from 'merchant/views/Settlements/components/SettlementTimeline';
import {
  fetchSettlementConfig as fnFetchSettlementConfig,
  fetchSettlementTimeline as fnFetchSettlementTimeline,
} from 'merchant/reducers/settlements/details';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import { TIMELINE_EVENTS } from './utils';

class SettlementInfo extends Component {
  componentDidMount() {
    const {
      user,
      data,
      fetchSettlementConfig,
      fetchBankAccountChangeStatus,
      fetchSettlementTimeline,
    } = this.props;
    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user.id);
    if (!data?.transaction?.on_hold) {
      const { id, created_at } = data?.transaction;
      const payload = {
        transaction_id: id?.split('_')[1],
        created_at,
      };
      fetchSettlementTimeline(payload);
    }
  }

  onViewDetailsClick = () => {
    const {
      user,
      settlement_amount,
      data,
      openModal,
      handleSettlementGuideClick,
      trackContactSupport,
      trackKnowMore,
      trackSameDaySettlement,
      trackSettlementClose,
    } = this.props;
    openModal({
      size: 'medium',
      component: (
        <SettlementDetail
          user={user}
          settlementAmount={settlement_amount?.data}
          transactionOnHold={data?.transaction?.on_hold}
          handleSettlementGuideClick={handleSettlementGuideClick}
          trackContactSupport={trackContactSupport}
          trackKnowMore={trackKnowMore}
          trackSameDaySettlement={trackSameDaySettlement}
          trackSettlementClose={trackSettlementClose}
        />
      ),
    });
  };

  render() {
    const {
      data,
      settlement_amount,
      settlementConfig,
      showTimeline = false,
      entityType,
      settlementTimelineDetails,
      user,
      terminalProviders,
      page,
    } = this.props;

    const { no_settlement } = settlement_amount.data;

    const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

    const isOnHold = no_settlement?.on_hold;
    const isSettlementOnHold = isOnHold || isOnTemporaryHold;
    const rescheduled = settlementTimelineDetails?.holidays?.length > 0;

    const timelineEvents = [
      TIMELINE_EVENTS.PAYMENT_CAPTURED,
      TIMELINE_EVENTS.SCHEDULE_INFO,
      TIMELINE_EVENTS.SETTLEMENT_INFO,
    ];

    if (entityType === 'refund') timelineEvents[0] = TIMELINE_EVENTS.REFUND_PROCESSED;
    if (rescheduled) timelineEvents.splice(2, 0, TIMELINE_EVENTS.HOLIDAY_INFO);

    let status, jsx;
    if (data?.transaction && data.transaction.settlement) {
      status = data.transaction.settlement.status;
    }
    if (data?.transaction?.on_hold) {
      status = 'on_hold';
      jsx = (
        <div className="settlement-detail-toggle">
          <SettlementStatusLabel status={status} />
          {data.on_hold_until ? <a className="nav-link">Hold until {data.on_hold_until}</a> : null}
          <a className="nav-link" onClick={this.onViewDetailsClick}>
            View Details
          </a>
        </div>
      );
    } else if (data?.transaction?.settlement) {
      jsx = (
        <div className="settlement-detail-toggle">
          <SettlementStatusLabel status={status} />
          <br />
          {showTimeline && settlementTimelineDetails && settlementTimelineDetails.eligible_at ? (
            <ContentToggler>
              <span>
                <span> Settled on </span>
                <Time
                  value={parseInt(settlementTimelineDetails.settled_at, 10)}
                  format="DD MMM YYYY"
                />
              </span>

              <SettlementTimeline
                data={data}
                events={timelineEvents}
                entityType={entityType}
                settlementDetails={settlementTimelineDetails}
                page={page}
              />
            </ContentToggler>
          ) : (
            <ContentToggler onToggleClick={this.props.viewSettlementOverview}>
              <span>
                Settled on{' '}
                <Time
                  value={
                    user.isSingleReconEnabled && user.isOptimizerEnabled
                      ? data.transaction.settlement.created_at
                      : data.transaction.settled_at
                  }
                  format="DD MMM YYYY"
                />
              </span>
              <SettlementOverview
                payment={data}
                terminalProviders={terminalProviders}
                user={user}
                page={page}
              />
            </ContentToggler>
          )}
        </div>
      );
    } else if (isSettlementOnHold) {
      jsx = (
        <div className="settlement-detail-toggle">
          <SettlementStatusLabel status={isOnHold ? 'under_review' : 'on_temporary_hold'} />
          <a className="nav-link" onClick={this.onViewDetailsClick}>
            View Details
          </a>
        </div>
      );
    } else if (
      data.transaction.settled_at &&
      (!user.isSingleReconEnabled ||
        !user.isOptimizerEnabled ||
        data.optimizer_provider === 'Razorpay')
    ) {
      jsx = (
        <Fragment>
          {!(data?.transaction && data?.transaction?.settlement) ? (
            <Fragment>
              <SettlementStatusLabel status="scheduled" /> <br />
            </Fragment>
          ) : null}
          {showTimeline && settlementTimelineDetails && settlementTimelineDetails.eligible_at ? (
            <>
              <span className="link">
                <span>To be settled on </span>
                <Time
                  value={parseInt(settlementTimelineDetails.eligible_at, 10)}
                  format="DD MMM YYYY"
                />
              </span>
              <ContentToggler>
                {rescheduled ? 'Rescheduled due to bank holidays' : 'View settlement timeline'}
                <SettlementTimeline
                  data={data}
                  events={timelineEvents}
                  entityType={entityType}
                  settlementDetails={settlementTimelineDetails}
                  page={page}
                />
              </ContentToggler>
            </>
          ) : (
            <span className="link">
              To be settled on <Time value={data.transaction.settled_at} format="DD MMM YYYY" />
            </span>
          )}
        </Fragment>
      );
    } else if (
      user.isSingleReconEnabled &&
      user.isOptimizerEnabled &&
      data.optimizer_provider !== 'Razorpay'
    ) {
      const info = this.props.integratedGateways?.includes(data.settled_by) ? (
        <>
          Settlement details last fetched at{' '}
          <Time value={moment().subtract(1, 'day').unix()} format="DD MMM YYYY" /> 9pm and no data{' '}
          was found. We will next fetch at{' '}
          {moment(`${moment().format('YYYY-MM-DD')}T21:00:00`).isAfter(moment()) ? (
            <Time value={moment().unix()} format="DD MMM YYYY" />
          ) : (
            <Time value={moment().add(1, 'day').unix()} format="DD MMM YYYY" />
          )}{' '}
          9pm
        </>
      ) : (
        <>Not integrated with {data.settled_by} to fetch settlement details</>
      );
      jsx = (
        <Fragment>
          <div>--</div>
          <div className="optimizer-settlement-info">
            <p className="icon-para">
              <i className="i i-info-outline" />
            </p>
            <p className="info-para">{info}</p>
          </div>
        </Fragment>
      );
    } else {
      jsx = '--';
    }

    return jsx;
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    settlement_amount: state.home.settlement_amount,
    settlementConfig: state.settlement.config,
    settlementTimelineDetails: state.settlement.timeline.data,
    terminalProviders: state.navigator.terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      fetchSettlementConfig: fnFetchSettlementConfig,
      fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
      fetchSettlementTimeline: fnFetchSettlementTimeline,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementInfo);
