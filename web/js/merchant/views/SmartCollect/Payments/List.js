import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';

import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';

import { fetchSmartCollectPayments as fetchAll } from 'merchant/reducers/collection';

@connect(state => state.scPayments, { fetchAll })
export default class VirtualAccountsListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.VA} />

            <DocsLink url="https://razorpay.com/docs/smart-collect/" />
          </div>
        </HeaderAction>

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
