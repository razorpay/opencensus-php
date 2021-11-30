import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { RZPFeatures } from 'merchant/helpers/data';

import { paymentId, amount, email, contact, createdAt, status } from 'common/ui/item/pair';
import Alert from 'common/ui/Forms/Alert';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';
import { fetchQRCodesPayments as fetchAll } from 'merchant/reducers/collection';
import track from './track';

@connect((state) => state.qrCodePayments, { fetchAll })
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
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          paymentColumns={[this.paymentIdCol, amount, email, contact, createdAt, status]}
        />
      </div>
    );
  }
}
