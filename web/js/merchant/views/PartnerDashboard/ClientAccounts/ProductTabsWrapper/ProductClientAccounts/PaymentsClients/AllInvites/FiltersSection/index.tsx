import React from 'react';
import * as Yup from 'yup';

import { PaginationParamsType } from 'common/typings';
import FiltersSectionWrapper, {
  ListFiltersType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import CommonFilters, {
  commonValidations,
  RenderFiltersSectionProps,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';
import { PRODUCT_TYPE, INVITE_VIEW_TYPE } from 'merchant/views/PartnerDashboard/constants';

import { getFiltersList } from './filters';

const productType = PRODUCT_TYPE.PG;
const inviteView = INVITE_VIEW_TYPE.ALL;
export interface AllInvitesFiltersType extends ListFiltersType {
  name: string;
  email: string;
  contact_no: string;
  count: PaginationParamsType['count'];
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
};
const AllInvitesFiltersSection = ({
  refetch,
  paginationState,
  setPagination,
}: RenderFiltersSectionProps): JSX.Element => {
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
