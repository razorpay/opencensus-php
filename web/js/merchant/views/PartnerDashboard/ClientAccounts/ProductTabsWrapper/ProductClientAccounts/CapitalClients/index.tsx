import React, { useEffect, useState } from 'react';
import { Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType, User } from 'common/typings';
import { fetchProducts } from 'merchant/reducers/capital';
import DataTableWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/styles';
import useWelcomeScreenData from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/WelcomeScreenContainer/hooks/useWelcomeScreenData';
import { OpenModalT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { fetchBureauLink } from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { CreateBureauLink } from 'merchant/views/PartnerDashboard/SubMerchant/components/CreateBureauLink';
import {
  CREATE_BUREAU_COUNTDOWN_TIME,
  PRODUCT_TYPE,
} from 'merchant/views/PartnerDashboard/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import FiltersSection from './FiltersSection';
import {
  FetchSubmerchantsParams,
  fetchSubmerchants,
  CapitalAcceptedInviteItem,
  FetchInviteResponse,
  getActivationBulkData,
  LoanApplicationDetailsProducts,
} from './api';
import { customColumnsGetter } from './columns';

type CapitalClientsProps = {
  user: User;
  openModal: OpenModalT;
  closeModal: () => void;
  showNotification: ShowNotificationType;
  products: LoanApplicationDetailsProducts;
  fetchProducts: () => void;
};
const CapitalClients = ({
  user,
  openModal,
  closeModal,
  showNotification,
  products,
  fetchProducts,
}: CapitalClientsProps): JSX.Element => {
  const [isCreateBureauButtonDisabled, setIsCreateBureauButtonDisabled] = useState({});
  const { isFilterSearchUsed, setIsAcceptedInvitesEmpty } = useWelcomeScreenData();
  useEffect(() => {
    fetchProducts();
  }, []);
  if (products?.loading) {
    return (
      <SpinnerContainer>
        <Spinner testID="capital-products-spinner" accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );
  }
  const partnerId = user?.id as string;
  const handleCreateBureauLinkClick = (item) => {
    fetchBureauLink(partnerId, item.id.replace('acc_', ''))
      .then((response) => {
        const { data } = response;
        const bureauLinkData = {
          bureauLink: data?.bureau_link || '',
          partnerId: partnerId || '',
          merchantId: item?.id?.replace('acc_', '') || '',
          smsCount: data?.sms_count || 0,
        };
        openModal({
          size: 'med-large',
          component: (
            <CreateBureauLink
              closeModal={closeModal}
              bureauLinkData={bureauLinkData}
              showNotification={showNotification}
            />
          ),
        });
        setIsCreateBureauButtonDisabled((state) => ({ ...state, [item.id]: true }));
        setTimeout(() => {
          setIsCreateBureauButtonDisabled((state) => ({ ...state, [item.id]: false }));
        }, CREATE_BUREAU_COUNTDOWN_TIME);
      })
      .catch((_err) => {
        showNotification?.({
          type: 'error',
          message: _err.errors,
        });
      });
  };

  const paginationQueryFn = async (paginationState, decodedParams) => {
    const { data } = await fetchSubmerchants({
      product: PRODUCT_TYPE.CAPITAL,
      ...paginationState,
      ...decodedParams,
    } as FetchSubmerchantsParams);
    const items = data?.items || [];

    const productsLength = products?.data?.length;
    if (productsLength && productsLength > 0 && items.length > 0) {
      try {
        const capitalItems = await getActivationBulkData({ items, products });
        return capitalItems;
      } catch (e) {
        showNotification?.({
          type: 'error',
          message: 'There was an error while fetching Status',
        });
        return items;
      }
    }
    return items;
  };

  const parseDataOnSuccess = (items) => {
    const parsedData = items || [];
    if (!isFilterSearchUsed) setIsAcceptedInvitesEmpty(parsedData.length === 0);
    return parsedData;
  };
  const getColumns = customColumnsGetter({
    handleCreateBureauLinkClick,
    isCreateBureauButtonDisabled,
  });

  return (
    <DataTableWrapper<CapitalAcceptedInviteItem, FetchInviteResponse>
      getColumns={getColumns}
      paginationQueryFn={paginationQueryFn}
      queryKey="filter-capital-accepted-invites"
      parseDataOnSuccess={parseDataOnSuccess}
      renderFiltersSection={({ paginationState, setPagination, refetch }) => (
        <FiltersSection
          paginationState={paginationState}
          refetch={refetch}
          setPagination={setPagination}
          user={user}
        />
      )}
      spinnerTestId="capital-accepted-invites-spinner"
    />
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
    products: state.loanApplicationDetails.products,
  }),
  (dispatch) =>
    bindActionCreators(
      {
        openModal,
        closeModal,
        showNotification,
        fetchProducts,
      },
      dispatch,
    ),
)(CapitalClients);
