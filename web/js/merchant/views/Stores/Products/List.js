import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';

import DataTable from 'common/ui/Table/DataTable';
import Pager from 'common/ui/Pager';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from './Filter';
import { StoreProductsStatusLabel } from 'merchant/components/StatusLabel';

import { fetchProducts as fetchAll } from 'merchant/reducers/storefront';
import { storeProductId } from 'common/ui/item/pair';

import { paiseToRupees } from 'common/utils/rzp-utils';

const productImage = {
  title: 'Image',
  value: (item) =>
    item.images[0] ? (
      <img src={item.images[0]} alt={`${item.id} image`} width="40x" height="40px" />
    ) : (
      '-'
    ),
};

const amount = {
  title: 'Amount',
  value: (item) => paiseToRupees(item.selling_price),
};

const stock = {
  title: 'Stock',
  value: (item) => item.stock,
};

const unitsSold = {
  title: 'Units Sold',
  value: (item) => item.stock_sold,
};

const status = {
  title: 'Status',
  value: (item) => <StoreProductsStatusLabel status={item.status} />,
};

const name = {
  title: 'Product Name',
  value: (item) => item.name,
};

@connect((state) => ({ ...state.storefront.products, ...state.session }), {
  fetchAll,
})
class ProductsListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper StoreProducts--List">
        <ListFilter
          form="StoreProductsListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Products"
          columns={[storeProductId, name, productImage, amount, stock, unitsSold, status]}
          {...this.props}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={this.props.items.length}
          onClick={(params) => {
            this.paginate(params);
          }}
        />
      </div>
    );
  }
}

export default withRouter(ProductsListContainer);
