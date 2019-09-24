import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';
import { reversalId, transferId, amount, createdAt } from 'rzp/ui/item/pair';

import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';

import DocsLink from 'merchant/components/DocsLink';
import { fetchReversals as fetchAll } from 'merchant/modules/collection';

import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ReversalsListFilter from 'merchant/components/Marketplace/ReversalsListFilter';

import ListContainer from 'merchant/containers/ListContainer';

@connect(
  state => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll }
)
export default class ReversalsListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="https://razorpay.com/docs/route/" />
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
