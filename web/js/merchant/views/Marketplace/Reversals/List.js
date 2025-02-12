import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ProductWrapper from 'common/ui/ProductWrapper';
import DataTable from 'common/ui/Table/DataTable';
import { reversalId, transferId, amount, createdAt } from 'common/ui/item/pair';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ListContainer from 'merchant/containers/ListContainer';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchReversals as fetchAll } from 'merchant/reducers/collection';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import ReversalsListFilter from 'merchant/views/Marketplace/Reversals/components/ReversalsListFilter';

class ReversalsListContainer extends ListContainer {
  render() {
    const { isPlatformFeeTabEnabled, user, isPartnerPlatformFeeEnabled } = this.props;
    return (
      <ProductWrapper
        tabsData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        extra={
          <>
            <TakeATourButton feature={RZPFeatures.ROUTE} />

            <DocsLink url="ROUTE_REVERSAL_DOC_URL" />
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

export default compose(
  withRouter,
  connect(
    (state) => ({
      ...state.reversals,
      user: state.session.user,
    }),
    { fetchAll },
  ),
)(ReversalsListContainer);
