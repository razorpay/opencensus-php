import { Component } from 'react';
import { connect } from 'react-redux';
import ReversalsListFilter from 'merchant/components/Marketplace/ReversalsListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import HeaderAction from 'rzp/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import { fetchReversals as fetchAll } from 'merchant/modules/collection';

import { reversalId, transferId, amount, createdAt } from 'rzp/ui/item/pair';

@connect(
  state => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll }
)
export default class ReversalsListContainer extends ListContainer {
  render() {
    let { loading, items, error, user } = this.props;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink url="https://razorpay.com/docs/route/"/>
          </div>
        </HeaderAction>        
        <ReversalsListFilter
          form="reversalsListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Reversals"
          columns={[reversalId, transferId, amount, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
