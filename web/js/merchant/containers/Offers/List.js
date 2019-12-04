import React from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Pager from 'common/ui/Pager';

import * as OffersListActions from 'merchant/reducers/offers/offersList';
import * as ModalActions from 'merchant_common/reducers/modals';
import Time from 'common/ui/Time';
import { NavLink } from 'react-router-dom';
import { OfferStatusLabel } from 'merchant/components/StatusLabel';

const OfferListItem = ({ offer }) => {
  return (
    <EntityItemRow id={offer.id}>
      <td>
        <NavLink to={`/offers/${offer.id}`}>
          <code>{offer.id}</code>
        </NavLink>
      </td>
      <td>{offer.name}</td>

      <td>{offer.payment_method}</td>
      <td>
        {' '}
        <OfferStatusLabel status={offer.active ? 'Enabled' : 'Disabled'} />
      </td>
      <td>
        <Time value={offer.starts_at} format="DD MMM YYYY, hh:mm a" />
      </td>
      <td>
        <Time value={offer.ends_at} format="DD MMM YYYY, hh:mm a" />
      </td>
    </EntityItemRow>
  );
};

const OfferListTable = ({ offers, isLoading, onDelete }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Offer Id</th>
            <th>Offer Title</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Start On</th>
            <th>Ends On</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={offers}
          emptyTableMsg="No Offers found!"
        >
          {offers.map(offer => (
            <OfferListItem
              key={offer.id}
              offer={offer}
              onDelete={() => onDelete(offer)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

@connect(state => ({ ...state.offers, ...state.session }), {
  ...OffersListActions,
  ...ModalActions,
})
export default class List extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchOffers(params);
  }

  // highlightRowAndClose = customer => {
  //   this.props.luminateRow(customer.id);
  //   this.props.closeModal();
  // };

  render() {
    let { loading, items, mode } = this.props;
    let status = this.state.status;
    return (
      <>
        <OfferListTable
          onDelete={() => {}}
          isLoading={loading}
          offers={items}
          mode={mode}
        />
        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </>
    );
  }
}
