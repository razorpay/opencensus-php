import React, { ReactNode, useEffect } from 'react';
import { Button } from '@razorpay/blade/components';
import { useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { useLocation, useNavigate } from 'react-router-dom';

import { User, YupObjectSchema } from 'common/typings';
import { encodeSensitiveFields, stringifyQueryParams } from 'common/utils/rzp-utils';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { PartnerDashboardExperiments } from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

import { ListFilterConfig } from './CommonFilters';
import { ListFiltersContext, ListFiltersContextType } from './context';
import { FilterContainer, ButtonContainer } from './styles';
import { getDecodedParams } from './utils';

export type ListFiltersType = Record<string, string | number | undefined>;

type GetFiltersArgs = {
  user: User;
  org: Org;
  experiments: PartnerDashboardExperiments;
};
export type GetFiltersType<PickT extends keyof GetFiltersArgs> = (
  args: Pick<GetFiltersArgs, PickT>,
) => Array<ListFilterConfig>;
export type GetFiltersTypeNoArgs = () => Array<ListFilterConfig>;

interface ListFilterWrapperProps {
  children: ReactNode;
  initState: ListFiltersType;
  onReset: () => void;
  onSearch: (formData: Record<string, string>) => void;
  validationSchema: YupObjectSchema;
}

// Note: This component aims to remove the dependency on
// 'merchant/components/ListFilter.js' component
const FiltersSectionWrapper = ({
  children,
  initState,
  onReset,
  onSearch,
  validationSchema,
}: ListFilterWrapperProps): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();

  const handleFormSubmit = (formData) => {
    const searchParams = stringifyQueryParams(encodeSensitiveFields(formData));
    navigate({
      pathname: location.pathname,
      hash: location.hash,
      search: searchParams,
    });
    onSearch(formData);
  };
  const formik = useFormik({
    initialValues: initState,
    validationSchema,
    validateOnChange: true,
    onSubmit: handleFormSubmit,
  });

  useEffect(() => {
    const decodedParams = getDecodedParams(location.search);
    const nextVal = { ...initState, ...decodedParams };
    formik.setValues(nextVal);
    if (JSON.stringify(decodedParams) !== '{}') {
      formik.submitForm();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleChange: ListFiltersContextType['handleChange'] = ({ name, value }) => {
    if (name) {
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
  };

  const handleReset = () => {
    navigate({
      search: stringifyQueryParams({}),
      hash: location.hash,
    });
    formik.resetForm();
    onReset();
  };

  return (
    <FilterContainer>
      <ListFiltersContext.Provider value={{ handleChange, formik }}>
        {children}
      </ListFiltersContext.Provider>
      {/* TODO v2: use DEFAULT_MAX_FILTER_COUNT_MOBILE to hide filters in more section */}
      <ButtonContainer>
        <Button
          variant="secondary"
          isDisabled={!isEmpty(formik.errors)}
          onClick={() => formik.handleSubmit()}
        >
          Search
        </Button>
        <Button variant="tertiary" marginLeft="spacing.4" onClick={handleReset}>
          Clear
        </Button>
      </ButtonContainer>
    </FilterContainer>
  );
};
export default FiltersSectionWrapper;
