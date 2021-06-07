import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { RZPFeatures } from 'merchant/helpers/data';

import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { paymentId, amount, email, contact, createdAt, status } from 'common/ui/item/pair';

import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';
import { fetchQRCodesPayments as fetchAll } from 'merchant/reducers/collection';

@connect((state) => state.qrCodePayments, { fetchAll })
export default class QRPaymentsListContainer extends ListContainer {
  get paymentIdCol() {
    return {
      ...paymentId,
      value: (...args) => <div>{paymentId.value(...args)}</div>,
    };
  }

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.QR_CODES} />

            <DocsLink url="https://razorpay.com/docs/qr_codes/" />
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="qrPaymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
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
