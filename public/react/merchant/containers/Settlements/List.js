import React, { Component } from 'react';
import { connect } from 'react-redux';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchant/components/Settlements/List';
import SettlementDetails from 'merchant/containers/Settlements/Details';
import SettlementsListFilter from 'merchant/components/Settlements/ListFilter';
import SettlementBreakupModal from './BreakupModal';
import { fetchSettlements } from 'merchant/modules/settlements/list';
import * as ModalActions from 'rzp/modules/modals';
import { openSlider } from 'rzp/modules/slider';

@connect(state => state.settlements, {
  fetchSettlements,
  openSlider,
  ...ModalActions,
})
export default class SettlementsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchSettlements(params);
  }

  showSettlementDetails = settlement => {
    this.props.openSlider({
      component: <SettlementDetails id={settlement.id} />,
      onOpenURL: `/app/settlements/${settlement.id}`,
      onCloseURL: '/app/settlements',
    });
  };

  showBreakup = settlement => {
    this.props.openModal({
      component: <SettlementBreakupModal settlementId={settlement.id} />,
    });
  };

  render() {
    let { loading, settlements, error } = this.props;

    return (
      <div class="content-wrapper">
        <SettlementsListFilter
          form="settlementsListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <SettlementsList
          settlements={settlements}
          isLoading={loading}
          showBreakup={this.showBreakup}
          onSettlementClick={this.showSettlementDetails}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={settlements.length}
          onClick={this.paginate}
        />

        <div class="row">
          <div class="col-md-6 col-md-offset-3 col-sm-12 text-center">
            <p>
              A settlement is an aggregate of payments and refunds, and as such the fees in a settlement is not reflective of the pricing. We only charge fees on a captured payment.
            </p>
          </div>
        </div>
      </div>
    );
  }
}
