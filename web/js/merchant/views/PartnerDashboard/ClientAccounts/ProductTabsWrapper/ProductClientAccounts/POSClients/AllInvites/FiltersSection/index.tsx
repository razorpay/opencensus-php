import React from 'react';
import * as Yup from 'yup';

import { PaginationParamsType } from 'common/typings';
import { POSAgents } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/api';
import FiltersSectionWrapper, {
  ListFiltersType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import CommonFilters, {
  commonValidations,
  RenderFiltersSectionProps,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';
import { PRODUCT_TYPE, INVITE_VIEW_TYPE } from 'merchant/views/PartnerDashboard/constants';

import { customFiltersGetter } from './filters';

const productType = PRODUCT_TYPE.POS;
const inviteView = INVITE_VIEW_TYPE.ALL;

export interface AllInvitesFiltersType extends ListFiltersType {
  name: string;
  email: string;
  contact_no: string;
  count: PaginationParamsType['count'];
  inviter_user_id: string;
}

const { nameValidation, emailValidation, mobileValidation } = commonValidations;
const validationSchema = Yup.object().shape({
  name: nameValidation,
  email: emailValidation,
  contact_no: mobileValidation,
});

const initState: AllInvitesFiltersType = {
  name: '',
  email: '',
  contact_no: '',
  count: 25,
  inviter_user_id: '',
};

type AllInvitesFilterProps = {
  posAgents: POSAgents;
} & RenderFiltersSectionProps;
const AllInvitesFiltersSection = ({
  refetch,
  posAgents,
  paginationState,
  setPagination,
}: AllInvitesFilterProps): JSX.Element => {
  const onReset = () => {
    setPagination({
      skip: 0,
      count: initState.count,
    });
    refetch();
  };
  const onSearch = (formData) => {
    if (paginationState.count !== Number(formData.count)) {
      setPagination({
        skip: 0,
        count: Number(formData.count),
      });
    }
    refetch();
  };

  const getFiltersList = customFiltersGetter({ posAgents });
  return (
    <FiltersSectionWrapper
      productType={productType}
      inviteView={inviteView}
      initState={initState}
      validationSchema={validationSchema}
      onSearch={onSearch}
      onReset={onReset}
    >
      <CommonFilters filtersList={getFiltersList()} />
    </FiltersSectionWrapper>
  );
};

export default AllInvitesFiltersSection;
