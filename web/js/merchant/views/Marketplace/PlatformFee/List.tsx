import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose, ActionCreator, bindActionCreators } from 'redux';
import { useQuery } from 'react-query';
import { Link, withRouter } from 'react-router-dom';
import { Amount, Spinner } from '@razorpay/blade/components';
import { History, Location } from 'history';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { recipient, createdAt } from 'common/ui/item/pair';
import { RZPFeatures } from 'merchant/helpers/data';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TransferSource from 'merchant/views/Marketplace/Transfers/components/TransferSource';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import { PlatformFeeListFilter } from 'merchant/views/Marketplace/PlatformFee/components/PlatformFeeListFilter';
import { SpinnerContainer } from 'merchant/views/Marketplace/PlatformFee/components/styles';
import { Notification } from 'common/typings/Store/notifications';
import { fetchTransfers } from './api';

const source = {
  title: 'Source Id',
  value: (item) => <TransferSource source={item.source} initiatePoint="transfers-table" />,
};

const transferStatus = {
  title: 'Status',
  value: (item) => <RouteTransfersStatusLabel status={item.status} />,
};

const platformAmount = {
  title: 'Platform Fee Amount',
  value: (item) => <Amount value={paiseToRupees(item.amount)} size="body-small" />,
};

const platformFeeId = {
  title: 'Platform Fee Id',
  value: (item) => <Link to={`/route/platformfee/${item.id}`}>{item.id}</Link>,
};

const recipientName = {
  title: 'Recipient Name',
  value: (item) => <code>{item?.recipient_details?.name}</code>,
};

interface PlatformFeeProps {
  showNotification?: ActionCreator<Notification>;
  history: History;
  location: Location;
}

const PlatformFee = ({ showNotification, history, location }: PlatformFeeProps): JSX.Element => {
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });
  const [searchParams, setSearchParams] = useState('');
  const [items, setItems] = useState([]);

  const handleParams = (): string => {
    if (searchParams && searchParams !== '') {
      return `${searchParams.replace('?', '&')}&skip=${paginationState.skip}`;
    } else {
      return `&skip=${paginationState.skip}&count=${paginationState.count}`;
    }
  };
  const { isLoading, refetch } = useQuery(
    ['get-transfer-details', handleParams()],
    fetchTransfers,
    {
      refetchOnWindowFocus: false,
      onSuccess: (data) => {
        setItems(data.data.items);
      },
      onError: (err: { errors: Array<string> }) => {
        showNotification?.({
          type: 'error',
          message: err.errors,
        });
      },
    },
  );

  const search = (params: string) => {
    setSearchParams(params);
    refetch();
  };

  return (
    <div className="content-wrapper">
      <HeaderAction>
        <div className="btn-toolbar pull-right">
          <TakeATourButton feature={RZPFeatures.ROUTE} />

          <DocsLink url="https://razorpay.com/docs/route/" />
        </div>
      </HeaderAction>

      <PlatformFeeListFilter
        onSearch={(params) => search(params)}
        count={paginationState.count}
        location={location}
        history={history}
        setPagination={setPagination}
      />
      {isLoading ? (
        <SpinnerContainer>
          <Spinner testID="spinner" accessibilityLabel="spinner" size="xlarge" />
        </SpinnerContainer>
      ) : (
        <DataTable
          title="Platform Fee"
          columns={[
            platformFeeId,
            source,
            recipient,
            recipientName,
            platformAmount,
            createdAt,
            transferStatus,
          ]}
          count={paginationState.count}
          skip={paginationState.skip}
          paginate={setPagination}
          loading={isLoading}
          items={items}
        />
      )}
    </div>
  );
};

export default compose<any>(
  withRouter,
  connect(null, (dispatch) => bindActionCreators({ showNotification }, dispatch)),
)(PlatformFee);
