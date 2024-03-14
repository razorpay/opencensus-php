import { connect } from 'react-redux';

import { RZPFeatures } from 'merchant/helpers/data';
import { reversalId, transferId, amount, createdAt } from 'common/ui/item/pair';

import DataTable from 'common/ui/Table/DataTable';
import DocsLink from 'merchant/components/DocsLink';
import { fetchReversals as fetchAll } from 'merchant/reducers/collection';

import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ReversalsListFilter from 'merchant/views/Marketplace/Reversals/components/ReversalsListFilter';

import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ProductWrapper from 'common/ui/ProductWrapper';
import { navItems } from 'merchant/views/Marketplace/NavItems';
@connect(
  (state) => ({
    ...state.reversals,
    user: state.session.user,
  }),
  { fetchAll },
)
class ReversalsListContainer extends ListContainer {
  render() {
    const { isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled } = this.props;
    return (
      <ProductWrapper
        tabsData={navItems(isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        extra={
          <>
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="https://razorpay.com/docs/route/" />
          </>
        }
      >
        <content>
          <div className="content-wrapper">
            <TestModeBanner />
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
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(ReversalsListContainer);
