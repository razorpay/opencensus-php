import React, { useEffect } from 'react';
import { TextInput, SearchIcon, Button, Box, Card, CardBody } from '@razorpay/blade/components';
import { isEmpty } from 'lodash';
import { useLocation } from 'react-router-dom';
import styled from 'styled-components';

import { UseFormikReturnType } from 'common/typings';
import { isMobileResolution } from 'common/utils/rzp-utils';
import {
  trackPageSectionCtaClicked,
  trackSearchCtaClicked,
  trackSearchSectionClicked,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/analytics';
import { getDecodedParams } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';

// BLADE DEVIATION REF: https://razorpay.slack.com/archives/C05HP8TJ2TZ/p1698046878204469
const StyledSearchBar = styled.div(
  ({ theme }) => `
  div[data-testid="playbook-search-input"]{
    input {
      width: 380px;
      font-size: ${theme.typography.fonts.size[300]}px;
      background-color: ${theme.colors.surface.background.gray.moderate};
    }
    input::placeholder{
      font-size: ${theme.typography.fonts.size[300]}px;
    }
    div {
      border-width: 0px;
      box-shadow: none;
    }
    div[class^='BaseInputAnimatedBorder'], div[class*=' BaseInputAnimatedBorder']{
      height: 0px;
    }
    @media screen and (max-width: 768px) {
      input {
        width: 150px;
      }
    }
  }
`,
);
type SearchBarProps = {
  formik: UseFormikReturnType;
};
const SearchBar = ({ formik }: SearchBarProps): JSX.Element => {
  const location = useLocation();
  const isMobile = isMobileResolution();
  useEffect(() => {
    const decodedParams = getDecodedParams(location.search);
    const nextVal = { ...formik.values, ...decodedParams };
    formik.setValues(nextVal);
    if (JSON.stringify(decodedParams) !== '{}') {
      formik.submitForm();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.search]);

  const handleChange = ({ name, value }: { name?: string; value?: string }) => {
    if (name) {
      if (!formik.touched[name]) {
        trackSearchSectionClicked();
      }
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
    if (name === 'query' && value === '') formik.submitForm();
  };

  const onSearchClicked = () => {
    trackSearchCtaClicked({ searchMessage: formik.values.query });
    trackPageSectionCtaClicked({
      section: 'Introducing Partner Playbook',
      pageFold: 1,
      ctaClicked: 'Search',
      folderName: null,
      folderDescription: null,
      title: null,
      description: null,
    });
    formik.handleSubmit();
  };
  const inputRef = React.useRef<HTMLInputElement>(null);

  return (
    <StyledSearchBar onClick={() => inputRef?.current?.focus()}>
      <Card
        elevation="lowRaised"
        padding="spacing.5"
        backgroundColor="surface.background.gray.moderate"
      >
        <CardBody>
          <Box display="flex" flexDirection="column" gap="27px" justifyContent="center">
            <Box
              display="flex"
              flexDirection="row"
              gap="spacing.1"
              paddingLeft="spacing.1"
              paddingRight="spacing.3"
              alignItems="center"
            >
              <Box display="flex" flexDirection="row" gap="spacing.2" alignItems="center">
                <SearchIcon size="large" color="interactive.icon.primary.normal" />
                <form
                  onSubmit={(e) => {
                    e.preventDefault();
                    if (isEmpty(formik.errors)) onSearchClicked();
                  }}
                >
                  <TextInput
                    ref={inputRef}
                    testID="playbook-search-input"
                    name="query"
                    label=""
                    placeholder={
                      isMobile ? 'Type to search' : 'Type to search for different content pieces'
                    }
                    value={formik.values.query}
                    errorText={formik.errors.query as string}
                    validationState={formik.errors.query ? 'error' : 'none'}
                    onChange={handleChange}
                  />
                </form>
              </Box>
              <Box marginLeft="auto">
                <Button isDisabled={!isEmpty(formik.errors)} onClick={onSearchClicked}>
                  Search
                </Button>
              </Box>
            </Box>
          </Box>
        </CardBody>
      </Card>
    </StyledSearchBar>
  );
};
export default SearchBar;
