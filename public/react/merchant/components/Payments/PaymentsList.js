import React, { Component } from 'react';
import PaymentsTable from 'merchant/components/Payments/PaymentsTable';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter
  from 'merchant/components/Payments/PaymentsListFilter';

export default class PaymentsListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
