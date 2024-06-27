import React from 'react';
import {
  Box,
  TableBody,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
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
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchOffers as fetchAll } from 'merchant/reducers/offers/offersList';

import { isOfferIdClickable } from './utils';
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
    const { user, loading, items, hasMoreData = true } = this.props;
    const offerListColumns = [
      isOfferIdClickable(user) ? _offerId : OfferIdWithoutLink,
      offerTitle,
      promotionType,
      paymentMethod,
      startOn,
      endsOn,
      offerStatus,
    ];
    const tableData = {
      nodes: items ?? [],
    };

    return (
      <div className="content">
        {loading ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : Array.isArray(tableData.nodes) && tableData.nodes.length > 0 ? (
          <>
            <Table
              data={tableData}
              showStripedRows={true}
              gridTemplateColumns={`repeat(${offerListColumns.length}, minmax(auto, 1fr))`}
            >
              {(offerItems) => {
                return (
                  <>
                    <TableHeader>
                      <TableHeaderRow>
                        {offerListColumns.map(({ title }) => (
                          <TableHeaderCell key={title}>{title}</TableHeaderCell>
                        ))}
                      </TableHeaderRow>
                    </TableHeader>
                    <TableBody>
                      {offerItems.map((order, index) => (
                        <TableRow key={index} item={order}>
                          {offerListColumns.map(({ title, value }) => (
                            <TableCell key={title}>{value(order)}</TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </>
                );
              }}
            </Table>
            <Box>
              {this.paginate && (
                <Pager
                  count={this.state.count}
                  skip={this.state.skip}
                  length={items.length}
                  onClick={this.paginate}
                  hasMoreData={hasMoreData}
                />
              )}
            </Box>
          </>
        ) : (
          <Box display="flex" alignItems="center" justifyContent="center">
            <EmptyListWithTableRow
              colSpan={8}
              description={
                <React.Fragment>
                  <div>There are no offers yet!!</div>
                  <div>Start creating new offers now.</div>
                </React.Fragment>
              }
            />
          </Box>
        )}
      </div>
    );
  }
}

export default connect((state) => ({ ...state.offers }), { fetchAll })(withRouter(OffersList));
