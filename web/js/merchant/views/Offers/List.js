import React from 'react';
import { connect } from 'react-redux';

import ListContainer from 'merchant/containers/ListContainer';
import EmptyList from 'merchant/components/EmptyList';
import DataTable from 'common/ui/Table/DataTable';

import { fetchOffers as fetchAll } from 'merchant/reducers/offers/offersList';
import {
  offerId,
  offerTitle,
  promotionType,
  paymentMethod,
  offerStatus,
  startOn,
  endsOn,
} from 'common/ui/item/pair';

@connect((state) => ({ ...state.offers }), {
  fetchAll,
})
export default class OffersList extends ListContainer {
  render() {
    return (
      <>
        <DataTable
          title="Offers"
          columns={[
            offerId,
            offerTitle,
            promotionType,
            paymentMethod,
            startOn,
            endsOn,
            offerStatus,
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          EmptyComponent={EmptyComponent}
        />
      </>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no offers yet!!</div>
        <div>Start creating new offers now.</div>
      </React.Fragment>
    }
  />
);
