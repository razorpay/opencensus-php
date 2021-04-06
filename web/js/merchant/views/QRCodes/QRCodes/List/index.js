import { connect } from 'react-redux';
import { withRouter, NavLink, Link } from 'react-router-dom';

import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import { RZPFeatures } from 'merchant/helpers/data';
import { QRCodeStatusLabel } from 'merchant/components/StatusLabel';
import { fetchQRCodes as fetchAll } from 'merchant/reducers/qrCodes/list';
import { qrCodeId, description, qrUsage, amountReceived, createdAt } from 'common/ui/item/pair';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import List from 'merchant/views/Invoices/Invoices/components/List';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import ListContainer from 'merchant/containers/ListContainer';
import EmptyList from 'merchant/components/EmptyList';

import ListFilter from './Filter';

@withRouter
@connect((state) => ({ ...state.qr_codes, ...state.session }), {
  fetchAll,
})
export default class QRCodesListContainer extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.QR_CODES} />

            <DocsLink url="https://razorpay.com/docs/qr-codes/api/new/" />

            <ShowWhen additionalCondition={(user) => user.isAllowedEdit('qr_codes')}>
              <NavLink class="btn btn-primary" to="/qr_codes/new">
                <i class="i i-plus" />
                Create QR Codes
              </NavLink>
            </ShowWhen>
          </div>
        </HeaderAction>

        <ListFilter
          form="QRCodesListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="QR Codes"
          columns={[qrCodeId, description, qrUsage, amountReceived, createdAt, status]}
          {...this.props}
          EmptyComponent={EmptyComponent}
        />
      </div>
    );
  }
}

// TODO: Update colSpan if no of columns are changes
const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no QR codes yet!!</div>
        <div>Start creating new QR code now.</div>
      </React.Fragment>
    }
  />
);

export const status = {
  title: 'Status',
  value: (item) => <QRCodeStatusLabel status={item.status} />,
};
