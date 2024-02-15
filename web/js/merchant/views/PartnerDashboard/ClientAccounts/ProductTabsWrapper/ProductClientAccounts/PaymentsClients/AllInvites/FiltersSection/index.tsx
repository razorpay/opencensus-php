import React from 'react';
import * as Yup from 'yup';

import FiltersSectionWrapper, {
  ListFiltersType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import CommonFilters, {
  commonValidations,
  RenderFiltersSectionProps,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

import { getFiltersList } from './filters';

export interface AllInvitesFiltersType extends ListFiltersType {
  name: string;
  email: string;
  contact_no: string;
  count: number;
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
