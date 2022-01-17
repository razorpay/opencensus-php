import React, { Fragment, Component } from 'react';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import SettlementOverview from 'merchant/views/Transactions/Payments/components/SettlementOverview';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';

class SettlementInfo extends Component {
  componentDidMount() {
    const { user, fetchSettlementConfig, fetchBankAccountChangeStatus } = this.props;
    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user.id);
  }

  onViewDetailsClick = () => {
    const { user, settlement_amount, data, openModal } = this.props;
    openModal({
      size: 'medium',
      component: (
        <SettlementDetail
          user={user}
          settlementAmount={settlement_amount?.data}
          transactionOnHold={data?.transaction?.on_hold}
        />
      ),
    });
  };

  render() {
    const { data, settlement_amount, settlementConfig } = this.props;

    const { no_settlement } = settlement_amount.data;

    const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

    const isOnHold =
      no_settlement?.on_hold || settlementConfig?.data?.config?.features?.disable?.status;
    const isSettlementOnHold = isOnHold || isOnTemporaryHold;

    let status, jsx;
    if (data.transaction && data.transaction.settlement) {
      status = data.transaction.settlement.status;
    }
    if (data.transaction.on_hold) {
      status = 'on_hold';
    }

    if (data.transaction.settlement) {
      jsx = (
        <div className="settlement-detail-toggle">
          <SettlementStatusLabel status={status} />
          {data.on_hold_until ? <a className="nav-link">Hold until {data.on_hold_until}</a> : null}

          {!data.transaction.on_hold ? (
            // false
            <Fragment>
              <br />
              <ContentToggler onToggleClick={this.props.viewSettlementOverview}>
                <span>
                  Settled on <Time value={data.transaction.settled_at} format="DD MMM YYYY" />
                </span>
                <SettlementOverview payment={data} />
              </ContentToggler>
            </Fragment>
          ) : (
            <a className="nav-link" onClick={this.onViewDetailsClick}>
              View Details
            </a>
          )}
        </div>
      );
    } else if (isSettlementOnHold) {
      jsx = (
        <div className="settlement-detail-toggle">
          <SettlementStatusLabel status={isOnHold ? 'on_hold' : 'on_temporary_hold'} />
          <a className="nav-link" onClick={this.onViewDetailsClick}>
            View Details
          </a>
        </div>
      );
    } else if (data.transaction.settled_at) {
      jsx = (
        <Fragment>
          {!(data.transaction && data.transaction.settlement) ? (
            <Fragment>
              <SettlementStatusLabel status="scheduled" /> <br />
            </Fragment>
          ) : null}
          <span className="link">
            To be settled on <Time value={data?.transaction?.settled_at} format="DD MMM YYYY" />
          </span>
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
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      fetchSettlementConfig: fnFetchSettlementConfig,
      fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementInfo);
