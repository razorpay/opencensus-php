import React, { Component } from 'react';
import Spinner from 'rzp/ui/Spinner';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import PaymentsTable from 'merchant/components/Payments/PaymentsTable';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter
  from 'merchant/components/Payments/PaymentsListFilter';

export default class PaymentsListContainer extends ListContainer {
  fetchEntityList = props => this.props.fetchAll(props);

  render() {
    let { loading, items, error } = this.props;
    return (
      <div class="content-wrapper">
        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />
        {error && <Alert type="error" message={error} />}

        {loading
          ? <Spinner />
          : items && items.length
              ? <PaymentsTable items={items} />
              : <h4 class="empty-table-message">No Payments Found!</h4>}

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
