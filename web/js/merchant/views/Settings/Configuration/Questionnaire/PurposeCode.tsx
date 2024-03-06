import React, { useState, useEffect, useMemo } from 'react';
import Input from 'common/new-ui/Input';
import { Alert, Link } from '@razorpay/blade/components';
import { useFormikContext } from 'formik';

import {
  computePurposeCodeSearch,
  getPurposeGroups,
  computePurposeCodeOptions,
  flattenPurposeCodesList,
} from './utils';
import { StyledPurposeCodeWrapper } from './styles';
import { PurposeCodeList, PurposeCodeProps } from './types';
import { PURPOSE_CODE_DOC_LINK } from './constants';

const PurposeCode = ({
  isRevampFlow,
  isAnyIntlProductEnabled,
  purposeCodeList,
  fetchPurposeCodes,
}: PurposeCodeProps) => {
  const formikProps = useFormikContext<{ purpose_code: string }>();
  const { setFieldValue, values, errors, touched, status } = formikProps;

  const [search, setSearch] = useState<{ text: string; results: PurposeCodeList }>({
    text: '',
    results: [],
  });

  const isPurposeCodeAdded = isAnyIntlProductEnabled && values.purpose_code;

  const radioOptions = useMemo(
    () => computePurposeCodeOptions(search.results, values.purpose_code),
    [search.results, values.purpose_code],
  );
  const purposeGroups = useMemo(() => getPurposeGroups(purposeCodeList), [purposeCodeList]);

  const handleChange = (value, name) => {
    setFieldValue(name, value);
  };

  const handleSearch = (e) => {
    const { value } = e.target;
    const results = computePurposeCodeSearch(purposeCodeList, value);
    setSearch((prevState) => ({ ...prevState, text: value, results }));
    handleChange('', 'purpose_code');
  };

  const handleDropdownChange = (e) => {
    const { value } = e.target;
    const purposeGroup = purposeCodeList.find((group) => group.purposeGroup === value);
    setSearch({ text: '', results: purposeGroup?.codes ?? [] });
    handleChange('', 'purpose_code');
  };

  const getError = (name) =>
    (touched[name] ? errors[name] : '') || (isRevampFlow ? '' : status ? status[name] : '');

  useEffect(() => {
    if (purposeCodeList.length) {
      setSearch({ text: '', results: flattenPurposeCodesList(purposeCodeList) });
    } else {
      fetchPurposeCodes();
    }
  }, [purposeCodeList]);

  return (
    <StyledPurposeCodeWrapper>
      <div>
        <div className="main-title">PURPOSE CODE</div>
        <div className="sub-title">
          Select a purpose code from the list. This helps us determine the purpose of your business
          for reporting and compliance mandated by the Reserve Bank of India.
          <Link marginLeft="spacing.1" href={PURPOSE_CODE_DOC_LINK} target="_blank">
            Learn more about purpose codes
          </Link>
        </div>

        {isPurposeCodeAdded ? (
          <Alert
            marginLeft={{ base: 'spacing.0', m: '85px' }}
            marginBottom="spacing.8"
            marginTop="spacing.5"
            color="notice"
            description="The purpose code below is associated with your account for processing international payments and we recommend not updating it at this step."
            isDismissible={false}
            title=""
            actions={{
              primary: {
                text: 'Know more',
                onClick: () => window.open(PURPOSE_CODE_DOC_LINK, '_blank'),
              },
            }}
          />
        ) : null}

        <Input
          placeholder="Search purpose codes"
          label="Search code"
          onChange={handleSearch}
          data-testid="purpose-code-input"
        />

        <Input.Select
          label="Purpose group"
          options={purposeGroups}
          onChange={handleDropdownChange}
          data-testid="purpose-group-dropdown"
        />

        <Input.Radio
          required
          name="purpose_code"
          label="Purpose code"
          className="Input--vTop"
          options={radioOptions}
          autoRender
          onChange={(e) => handleChange(e.target.value, 'purpose_code')}
          defaultValue={values.purpose_code}
          value={values.purpose_code}
          propagatedError={getError('purpose_code')}
        />
      </div>
    </StyledPurposeCodeWrapper>
  );
};

export default PurposeCode;
