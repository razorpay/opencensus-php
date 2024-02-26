import React from 'react';
import { Box } from '@razorpay/blade/components';
import * as Yup from 'yup';

import FiltersSectionWrapper, {
  ListFiltersType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import CommonFilters, {
  commonValidations,
  RenderFiltersSectionProps,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';
import ExportButton from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/ExportButton';
import { PRODUCT_TYPE, INVITE_VIEW_TYPE } from 'merchant/views/PartnerDashboard/constants';

import { getFiltersList } from './filters';

const productType = PRODUCT_TYPE.CAPITAL;
const inviteView = INVITE_VIEW_TYPE.ACCEPTED;

export interface AcceptedInvitesFiltersType extends ListFiltersType {
  count: number;
  email: string;
  id?: string;
  name: string;
}
const initState: AcceptedInvitesFiltersType = {
  email: '',
  id: '',
  name: '',
  count: 25,
};
const { idValidation, nameValidation, emailValidation } = commonValidations;

const AcceptedInvitesFiltersSection = ({
  refetch,
  paginationState,
  setPagination,
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
    <Box display="flex" flexDirection="row" gap="spacing.10" justifyContent="space-between">
      <Box>
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
      </Box>
      <Box
        minWidth="160px"
        marginTop="30px"
        marginBottom="spacing.2"
        marginRight="spacing.1"
        marginLeft="spacing.2"
      >
        <ExportButton productType={productType} />
      </Box>
    </Box>
  );
};

export default AcceptedInvitesFiltersSection;
