import { connect } from 'react-redux';
import { withRouter, NavLink, Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
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
import track from './track';

@withRouter
@connect((state) => ({ ...state.qr_codes, ...state.session }), {
  fetchAll,
})
@RTracking(() => window.rzpQ.component('QRCodesListContainer'))
export default class QRCodesListContainer extends ListContainer {
  componentDidMount() {
    track.init({
      track: this.props.tracking.trackEvent,
    });
  }

  onAlertCloseClick = () => {
    track.fail(this.state.status.message[1]);
  };

  onSearchAnalytics = () => track.submit();

  onClearAnalytics = () => track.clear();

  render() {
    return (
      <div class="QRCode--List content-wrapper">
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
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <Alert type={status.type} message={status.message} onCloseClick={this.onAlertCloseClick} />

        <DataTable
          title="QR Codes"
          columns={[qrCodeId, description, qrUsage, amountReceived, createdAt, status]}
          {...this.props}
          EmptyComponent={EmptyComponent}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={this.props.items.length}
          onClick={(params, type) => {
            track.browse(type, {
              page: params.skip % params.count,
            })

            this.paginate(params);
          }}
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
