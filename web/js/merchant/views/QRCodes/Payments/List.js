import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { RZPFeatures } from 'merchant/helpers/data';

import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import { NavLink } from 'react-router-dom';
import { paymentId, amount, email, contact, createdAt, status } from 'common/ui/item/pair';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { truncatedString } from 'common/utils/rzp-utils';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';
import { fetchQRCodesPayments as fetchAll } from 'merchant/reducers/collection';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import track from './track';

const paymentListRowItem = (item) => (
  <EntityItemRow id={item.id}>
    <td>
      <NavLink to={`/payments/${item.id}`}>
        <code>{item.id}</code>
      </NavLink>
      <tr class="mobile-text">{truncatedString(item.email)}</tr>
    </td>
    <td>
      <Amount value={item.amount} currency={item.currency} />
    </td>
    <td>
      <PaymentStatusLabel status={item.status} />
    </td>
  </EntityItemRow>
);

@connect(
  (state) => ({
    ...state.qrCodePayments,
    isMobileResolution: state.app.isMobileResolution,
  }),
  {
    fetchAll,
  },
)
@RTracking(() => window.rzpQ.component('QRPaymentsListContainer'))
export default class QRPaymentsListContainer extends ListContainer {
  get paymentIdCol() {
    return {
      ...paymentId,
      value: (...args) => <div>{paymentId.value(...args)}</div>,
    };
  }

  componentDidMount() {
    track.init({
      track: this.props.tracking.trackEvent,
    });

    track.load();
  }

  onAlertCloseClick = () => {
    track.fail(this.state.status.message[1]);
  };

  onSearchAnalytics = () => track.submit();

  onClearAnalytics = () => track.clear();

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction responsive>
          <div class="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.QR_CODES}
              onSuccess={() => track.tourStatus(true)}
              onAbort={() => track.tourStatus(false)}
            />

            <DocsLink url="https://razorpay.com/docs/qr-codes/" onClick={track.docs} />
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="qrPaymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <Alert
          type={this.state.status.type}
          message={this.state.status.message}
          onCloseClick={this.onAlertCloseClick}
        />

        <PaymentsTable
          {...this.props}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          customMobileRow={paymentListRowItem}
          isMobileResolution={this.props.isMobileResolution}
          mobileColumns={[this.paymentIdCol, amount, status]}
          paymentColumns={[this.paymentIdCol, amount, email, contact, createdAt, status]}
        />
      </div>
    );
  }
}
