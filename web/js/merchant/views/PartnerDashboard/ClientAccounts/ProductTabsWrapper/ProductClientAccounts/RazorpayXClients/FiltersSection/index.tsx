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

export interface AcceptedInvitesFiltersType extends ListFiltersType {
  application_id: string;
  count: number;
  email: string;
  id?: string;
  name: string;
}
const initState: AcceptedInvitesFiltersType = {
  application_id: '',
  count: 25,
  email: '',
  id: '',
  name: '',
};
const { idValidation, nameValidation, emailValidation } = commonValidations;

const AcceptedInvitesFiltersSection = ({
  refetch,
  paginationState,
  setPagination,
  user,
}: RenderFiltersSectionProps): JSX.Element => {
  const validationSchema = Yup.object().shape({
    id: idValidation,
    name: nameValidation,
    email: emailValidation,
  });

  const onReset = () => {
    setPagination({
      skip: 0,
      count: initState.count,
    });
    // trackClearAnalytics
    refetch();
  };
  const onSearch = (formData) => {
    if (paginationState.count !== Number(formData.count)) {
      setPagination({
        skip: 0,
        count: Number(formData.count),
      });
    }
    // trackSearchAnalytics
    refetch();
  };
  return (
    <FiltersSectionWrapper
      initState={initState}
      validationSchema={validationSchema}
      onSearch={onSearch}
      onReset={onReset}
    >
      <CommonFilters filtersList={getFiltersList({ user })} />
    </FiltersSectionWrapper>
  );
};

export default AcceptedInvitesFiltersSection;
