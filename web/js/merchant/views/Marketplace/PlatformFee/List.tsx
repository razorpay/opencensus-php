import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose, ActionCreator, bindActionCreators } from 'redux';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { Amount } from '@razorpay/blade/components';
import { History, Location } from 'history';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { recipient, createdAt } from 'common/ui/item/pair';
import { RZPFeatures } from 'merchant/helpers/data';
import DataTable from 'common/ui/Table/DataTable';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import TransferSource from 'merchant/views/Marketplace/Transfers/components/TransferSource';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import { PlatformFeeListFilter } from 'merchant/views/Marketplace/PlatformFee/components/PlatformFeeListFilter';
import { ContentBox } from 'merchant/views/Marketplace/PlatformFee/components/styles';
import { Notification } from 'common/typings/Store/notifications';
import { User } from 'common/typings';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ProductWrapper from 'common/ui/ProductWrapper';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import { fetchTransfers } from './api';
import { platformFeeOpenedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';

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
  isPlatformFeeTabEnabled: boolean;
  isPartnerPlatformFeeEnabled: boolean;
  user: User;
}

interface listItemsProps {
  id: string;
  recipient: string;
  amount: number;
  status: string;
  source: string;
  recipient_details: {
    email: string;
    name: string;
  };
  created_at: number;
}

const PlatformFee = ({
  showNotification,
  history,
  location,
  isPlatformFeeTabEnabled,
  user,
  isPartnerPlatformFeeEnabled,
}: PlatformFeeProps): JSX.Element => {
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });
  const [searchParams, setSearchParams] = useState('');
  const [items, setItems] = useState<listItemsProps[]>([]);
  const [id, setId] = useState('');

  const handleParams = (): string => {
    if (searchParams && searchParams !== '') {
      return `${searchParams}&skip=${paginationState.skip}`;
    } else {
      return `?transfer_type=platform&skip=${paginationState.skip}&count=${paginationState.count}`;
    }
  };

  const queryParams = handleParams();
  const queryKey = ['get-transfer-details', queryParams];
  const { isLoading, refetch } = useQuery({
    queryKey,
    queryFn: async () => {
      const response = await fetchTransfers(queryParams);
      return response;
    },
    refetchOnWindowFocus: false,
    onSuccess: (data) => {
      if (id) {
        setItems([data.data]);
      } else {
        setItems(data.data.items);
      }
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  const search = (id: string, params: string) => {
    setSearchParams(params);
    setId(id);
    refetch();
    setItems([]);
  };

  useEffect(() => {
    platformFeeOpenedAnalytics(user.id);
  }, []);

  return (
    <ProductWrapper
      tabsData={navItems(isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
      extra={
        <>
          <TakeATourButton feature={RZPFeatures.ROUTE} />

          <DocsLink url="https://razorpay.com/docs/route/" />
        </>
      }
    >
      <ContentBox>
        <div className="content-wrapper">
          <TestModeBanner />

          <PlatformFeeListFilter
            onSearch={(id, params) => search(id, params)}
            location={location}
            history={history}
            setPagination={setPagination}
          />
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
        </div>
      </ContentBox>
    </ProductWrapper>
  );
};

export default compose<any>(
  withRouter,
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(PlatformFee);
