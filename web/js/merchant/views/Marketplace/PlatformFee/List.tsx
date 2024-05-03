import React, { useState, useEffect } from 'react';
import { Amount } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { History, Location } from 'history';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { compose, ActionCreator, bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { User } from 'common/typings';
import { Notification } from 'common/typings/Store/notifications';
import ProductWrapper from 'common/ui/ProductWrapper';
import DataTable from 'common/ui/Table/DataTable';
import { recipient, createdAt } from 'common/ui/item/pair';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import { convertToMajorUnitInUserCurrency } from 'merchant/utils/currency';
import { platformFeeOpenedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import { PlatformFeeListFilter } from 'merchant/views/Marketplace/PlatformFee/components/PlatformFeeListFilter';
import { ContentBox } from 'merchant/views/Marketplace/PlatformFee/components/styles';
import TransferSource from 'merchant/views/Marketplace/Transfers/components/TransferSource';
import { showNotification } from 'merchant_common/reducers/notifications';

import { fetchTransfers } from './api';

const source = {
  title: 'Source Id',
  value: (item) => <TransferSource source={item.source} initiatePoint="transfers-table" />,
};

const transferStatus = {
  title: 'Status',
  value: (item) => <RouteTransfersStatusLabel status={item.status} />,
};

const platformFeeId = {
  title: 'Platform Fee Id',
  value: (item) => <Link to={`/route/platformfee/${item.id}`}>{item.id}</Link>,
};

const partnerFeeId = {
  title: 'Partner Fee Id',
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
  const platformAmount = {
    title: 'Platform Fee Amount',
    value: (item) => (
      <Amount value={convertToMajorUnitInUserCurrency(item.amount + item.fees)} size="small" />
    ),
  };
  const partnerAmount = {
    title: 'Partner Fee Amount',
    value: (item) => (
      <Amount value={convertToMajorUnitInUserCurrency(item.amount + item.fees)} size="small" />
    ),
  };

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
      tabsData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
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
            isPartnerPlatformFeeEnabled={isPartnerPlatformFeeEnabled}
          />
          <DataTable
            title={isPartnerPlatformFeeEnabled ? 'Platform Fee' : 'Partner Fee'}
            columns={[
              isPartnerPlatformFeeEnabled ? platformFeeId : partnerFeeId,
              source,
              recipient,
              recipientName,
              isPartnerPlatformFeeEnabled ? platformAmount : partnerAmount,
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
