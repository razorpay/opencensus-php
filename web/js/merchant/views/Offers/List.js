import React from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import EmptyList from 'merchant/components/EmptyList';
import DataTable from 'common/ui/Table/DataTable';
import { fetchOffers as fetchAll } from 'merchant/reducers/offers/offersList';
import {
  offerId,
  OfferIdWithoutLink,
  offerTitle,
  promotionType,
  paymentMethod,
  offerStatus,
  startOn,
  endsOn,
} from 'common/ui/item/pair';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { isOfferIdClickable } from './utils';

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

const _offerId = {
  title: offerId.title,
  value: (item) => {
    const intermediateElement = offerId.value(item);
    return (
      <div
        onClick={() => {
          selfServeTrackInitiate({
            selfServeAction: 'Offer Details Fetched',
            page: 'Offers',
            screen: 'Offers',
          });
        }}
      >
        {intermediateElement}
      </div>
    );
  },
};

class OffersList extends ListContainer {
  render() {
    const { user } = this.props;
    return (
      <DataTable
        title="Offers"
        columns={[
          isOfferIdClickable(user) ? _offerId : OfferIdWithoutLink,
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
    );
  }
}

export default connect((state) => ({ ...state.offers }), { fetchAll })(withRouter(OffersList));
