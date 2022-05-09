import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { withRouter, NavLink } from 'react-router-dom';
import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import { RZPFeatures } from 'merchant/helpers/data';
import { QRCodeStatusLabel } from 'merchant/components/StatusLabel';
import { truncatedString } from 'common/utils/rzp-utils';
import { fetchQRCodes as fetchAll } from 'merchant/reducers/qrCodes/list';
import { qrCodeId, description, qrUsage, amountReceived, createdAt } from 'common/ui/item/pair';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import ListContainer from 'merchant/containers/ListContainer';
import EmptyList from 'merchant/components/EmptyList';

import ListFilter from './Filter';
import track from './track';

const QR_CODE_CREATE_HOTJAR = {
  trigger: 'QR_Creation',
  tags: ['QR_Creation'],
};

const QRCodeMobileListItem = (item) => {
  return (
    <EntityItemRow id={item.id}>
      <td>
        <NavLink to={`/qr_codes/${item.id}`}>
          <code>{item.id}</code>
        </NavLink>
        <tr className="mobile-text">{item.name || truncatedString(item.description)}</tr>
      </td>
      <td>
        <QRCodeStatusLabel status={item.status} />
      </td>
    </EntityItemRow>
  );
};

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

@withRouter
@connect(
  (state) => ({
    ...state.qr_codes,
    ...state.session,
    isMobileResolution: state.app.isMobileResolution,
  }),
  {
    fetchAll,
  },
)
@RTracking(() => window.rzpQ.component('QRCodesListContainer'))
export default class QRCodesListContainer extends ListContainer {
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

  onCreateQRCode = () => {
    triggerHotjarRecording(QR_CODE_CREATE_HOTJAR.trigger, QR_CODE_CREATE_HOTJAR.tags);
    track.create();
  };

  render() {
    return (
      <div class="QRCode--List content-wrapper">
        <HeaderAction responsive>
          <div class="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.QR_CODES}
              onSuccess={() => track.tourStatus(true)}
              onAbort={() => track.tourStatus(false)}
            />

            <DocsLink url="https://razorpay.com/docs/qr-codes/" onClick={track.docs} />
            <ShowWhen additionalCondition={(user) => user.isAllowedEdit('qr_codes')}>
              <span className="cta-container">
                <NavLink class="btn btn-primary" to="/qr_codes/new" onClick={this.onCreateQRCode}>
                  <i class="i i-plus" />
                  Create QR Codes
                </NavLink>
              </span>
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

        <Alert
          type={this.state.status.type}
          message={this.state.status.message}
          onCloseClick={this.onAlertCloseClick}
        />

        <DataTable
          {...this.props}
          title="QR Codes"
          EmptyComponent={EmptyComponent}
          mobileColumns={[qrCodeId, status]}
          customMobileRow={QRCodeMobileListItem}
          isMobileResolution={this.props.isMobileResolution}
          columns={[qrCodeId, description, qrUsage, amountReceived, createdAt, status]}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={this.props.items.length}
          onClick={(params, type) => {
            track.browse(type, {
              page: params.skip / params.count,
            });

            this.paginate(params);
          }}
        />
      </div>
    );
  }
}
