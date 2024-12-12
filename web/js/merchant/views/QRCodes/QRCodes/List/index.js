import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import ProductWrapper from 'common/ui/ProductWrapper';
import TestModeBanner from 'merchant/components/TestModeBanner';
import DataTable from 'common/ui/Table/DataTable';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import { RZPFeatures } from 'merchant/helpers/data';
import { QRCodeStatusLabel } from 'merchant/components/StatusLabel';
import { getTableTemplateColumnsValue, truncatedString } from 'common/utils/rzp-utils';
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
import { Box, Button, PlusIcon } from '@razorpay/blade/components';
import { NEW_QR_URL } from 'merchant/constants/urls';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import CustomerFeeBearerPopover from 'merchant/components/CustomerFeeBearerPopover';

const tabsData = [
  { title: 'QR Codes', url: '/qr_codes' },
  { title: 'Payments', url: '/qr_codes/payments' },
];

const QR_CODE_CREATE_HOTJAR = {
  trigger: 'QR_Creation',
  tags: ['QR_Creation'],
};

export const mobileQrCodeId = {
  title: 'QR Code ID',
  value: (item) => (
    <Box>
      <NavLink to={`/qr_codes/${item.id}`}>
        <code>{item.id}</code>
      </NavLink>
      <div className="mobile-text">{item.name || truncatedString(item.description)}</div>
    </Box>
  ),
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

@connect(
  (state) => ({
    ...state.qr_codes,
    ...state.session,
    isMobileResolution: state.app.isMobileResolution,
    isTestMode: state.session.mode === 'test',
  }),
  {
    fetchAll,
  },
)
@RTracking(() => window.rzpQ.component('QRCodesListContainer'))
class QRCodesListContainer extends ListContainer {
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
    const history = this.props.history;
    history.push(NEW_QR_URL);
    triggerHotjarRecording(QR_CODE_CREATE_HOTJAR.trigger, QR_CODE_CREATE_HOTJAR.tags);
    track.create();
  };

  render() {
    const { isTestMode } = this.props;
    const feeBearer = this.props.user.merchant.fee_bearer;
    const isCreateQRDisabled = feeBearer === FEE_BEARER_TYPES.CUSTOMER;

    const mobileColumns = [
      { ...mobileQrCodeId, width: '3fr' },
      { ...status, width: '1fr' },
    ];
    const columns = this.props.isMobileResolution
      ? mobileColumns
      : [
          { ...qrCodeId, width: '1fr' },
          { ...description, width: '3fr' },
          { ...qrUsage, width: '1fr' },
          { ...amountReceived, width: '2fr' },
          { ...createdAt, width: '1fr' },
          { ...status, width: '1fr' },
        ];

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <Box display="flex" alignItems="center">
            <TakeATourButton
              feature={RZPFeatures.QR_CODES}
              onSuccess={() => track.tourStatus(true)}
              onAbort={() => track.tourStatus(false)}
            />

            <DocsLink url="CREATE_QR_CODE" onClick={track.docs} />
            <ShowWhen additionalCondition={(user) => user.isAllowedEdit('qr_codes')}>
              <Box display="inline-block">
                <Button
                  onClick={this.onCreateQRCode}
                  icon={PlusIcon}
                  iconPosition="left"
                  size="small"
                  type="button"
                  variant="primary"
                  isDisabled={isCreateQRDisabled}
                >
                  Create QR Codes
                </Button>
                {isCreateQRDisabled && <CustomerFeeBearerPopover feature="QR Code" />}
              </Box>
            </ShowWhen>
          </Box>
        }
      >
        <content>
          <div class="QRCode--List content-wrapper">
            {isTestMode && <TestModeBanner />}

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
              isMobileResolution={this.props.isMobileResolution}
              columns={columns}
              gridTemplateColumns={getTableTemplateColumnsValue(
                ...columns.map(({ width }) => width),
              )}
              progressLoader={true}
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
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(QRCodesListContainer);
