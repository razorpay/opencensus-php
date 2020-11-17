import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import { fetchHolidayList } from 'merchant/reducers/settlements/details';
import { fetchInstantSettlements as fetchAll } from 'merchant/reducers/collection';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import Pager from 'common/ui/Pager';
import InstantSettlementsList from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/List';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Alert from 'common/ui/Forms/Alert';
import HeaderAction from 'common/ui/HeaderAction';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import ScheduledBanner from 'merchant/views/Settlements/Settlements/components/ScheduledBanner';
import EmptySettleNow from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/EmptySettleNow';
import CurrentBalance from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/CurrentBalance';
import SettlementMessage from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage';
import InstantSettlementListFilter from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/ListFilter';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    holidayList: state.settlement.holidayList,
    ...state.home,
    ...state.instantSettlements,
  }),
  {
    fetchAll,
    showNotification,
    ...ModalActions,
    fetchCurrentBalance,
    fetchHolidayList,
  },
)
class InstantSettlements extends ListContainer {
  state = {
    count: 25,
  };
  componentDidMount() {
    this.props.fetchCurrentBalance();
    this.props.fetchHolidayList();
  }

  onSearchAnalytics() {
    trackIS.clickCTAISSearch();
  }

  onClearAnalytics() {
    trackIS.clickCTAISClear();
  }

  showOndemandSettlementForm = (e) => {
    const balance = this.props.current_balance.data.balance || 0;
    this.props.openModal({
      component: (
        <OndemandModal
          currentBalance={balance}
          fromWhere={e.clickOrigin ? 'Announcement' : 'Settlements'}
          showOndemandSettlementForm={this.showOndemandSettlementForm}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  };

  viewSettlementCycle = () => {
    this.props.openModal({
      size: 'medium',
      component: <SettlementSchedule holidayList={this.props.holidayList} />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: 'Settlements',
    });
  };

  render() {
    const {
      user,
      current_balance,
      holidayList,
      openModal,
      loading,
      items,
      error,
      location: { search },
    } = this.props;
    const { count } = this.state;
    const balance = current_balance.data.balance || 0;
    const updatedAt = current_balance.data.updated_at;

    let renderSettlementView = (
      <EmptySettleNow showOndemandSettlementForm={this.showOndemandSettlementForm} />
    );

    let renderSettlementFilterView = null;

    if ((items && items.length > 0) || search) {
      renderSettlementFilterView = (
        <InstantSettlementListFilter
          form="instantSettlementListFilter"
          count={count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />
      );
      renderSettlementView = (
        <>
          {error && <Alert type="error" message={error} />}
          <InstantSettlementsList settlements={items} isLoading={loading} />

          <Pager
            count={count}
            length={items.length}
            skip={this.state.skip}
            onClick={this.paginate}
          />

          <SettlementGuideText />
        </>
      );
    }

    if (loading) {
      renderSettlementView = PlaceholderLoader;
    }

    return (
      <>
        <div className="payout-details">
          <CurrentBalance
            showOndemandSettlementForm={this.showOndemandSettlementForm}
            updatedAt={updatedAt}
            balance={balance}
            isBalanceLoading={current_balance.loading}
          />
          <SettlementMessage
            user={user}
            holidayList={holidayList}
            openModal={openModal}
            balance={balance}
          />
        </div>
        <content>
          <div className="content-wrapper">
            <HeaderAction>
              <div className="settlement-actions-wrapper">
                {
                  <div
                    className="btn btn-link settlement-doc-btn"
                    onClick={this.viewSettlementCycle}
                  >
                    <span
                      className="icon i-info-outline"
                      style={{
                        marginRight: '5px',
                        position: 'relative',
                        top: '2px',
                      }}
                    />
                    View Settlement Cycle
                  </div>
                }
                {user.isOndemandSettlementEnabled && user.isAllowedView('early_settlement') && (
                  <div className="box-left-pad10-inline">
                    <ScheduledBanner openAutoModal={false} fromWhere="Instant Settlements" />
                  </div>
                )}
              </div>
            </HeaderAction>
            {renderSettlementFilterView}
            {renderSettlementView}
          </div>
        </content>
      </>
    );
  }
}

export default InstantSettlements;
