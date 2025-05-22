/* eslint-disable consistent-return */
import React, { forwardRef } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { get } from 'lodash';
import { displayPriceDenominations } from '../../constants';
import { FILE_UPLOAD_OPTIONS } from 'merchant/views/GCMS/Programs/CreateProgram/constants';
import { DENOMINATION_TYPE_ENUM } from '../../../shared/constants';

const PROGRAM_DETAILS = [
  {
    key: 'Program Name',
    path: 'name',
  },
  {
    key: 'Program Description',
    path: 'description',
  },
  {
    key: 'Discount',
    render: (values) => {
      return <Text>{values.discount}%</Text>;
    },
  },
];

const GIFT_CARD_DETAILS = [
  {
    key: 'Denomination Type',
    render: (values) => {
      return (
        <Text>
          {values.denomination_type === DENOMINATION_TYPE_ENUM.FIXED
            ? 'Fixed Denomination'
            : 'Customizable Denomination'}
        </Text>
      );
    },
  },
  {
    key: 'Denomination Values',
    render: (values) => {
      return (
        <Text>
          {displayPriceDenominations(
            values.denomination_type,
            values.denomination_values,
            values.denomination_values.from,
            values.denomination_values.to,
          )}
        </Text>
      );
    },
  },
  {
    key: 'Card Expiry',
    render: (values) => {
      return <Text>{`${values.validity_quantity} ${values.validity_span}s`}</Text>;
    },
  },
  {
    key: 'Steps to redeem',
    path: 'steps_to_redeem',
  },
  {
    key: 'Terms and Conditions',
    path: 'terms_and_conditions',
  },
];

const SECTIONS = [
  {
    title: 'Program Details',
    values: PROGRAM_DETAILS,
  },
  {
    title: 'Gift Card Details',
    values: GIFT_CARD_DETAILS,
  },
];

const Review = forwardRef(({ values }, ref) => {
  function renderKeyValuePair(pair) {
    return (
      <Box display="flex" flexDirection="row">
        <Box width="175px" minWidth="175px">
          <Text color="interactive.text.gray.subtle">{pair.key + ':'}</Text>
        </Box>
        <Box>
          {pair.path ? (
            <Text color="surface.text.gray.subtle">{get(values, pair.path)}</Text>
          ) : (
            pair.render(values)
          )}
        </Box>
      </Box>
    );
  }

  function renderSection(section) {
    return (
      <Box>
        <Box marginBottom="spacing.5">
          <Text size="medium" weight="semibold" color="surface.text.gray.normal">
            {section.title}
          </Text>
        </Box>
        <Box gap="spacing.3" display="flex" flexDirection="column">
          {section.values.map((detail) => {
            return <Box>{renderKeyValuePair(detail)}</Box>;
          })}
        </Box>
      </Box>
    );
  }

  return (
    <React.Fragment>
      <Box>
        <Text weight="semibold" size="medium">
          Preview
        </Text>
        <Box
          width="395px"
          height="250px"
          borderRadius="large"
          display="flex"
          flexDirection="row"
          justifyContent="center"
          overflow="hidden"
          marginTop="24px"
          borderColor="surface.border.gray.muted"
        >
          {values.url && (
            <img src={values.url} height="100%" width="100%" style={{ 'object-fit': 'contain' }} />
          )}
          {!values.url && values.upload_type === FILE_UPLOAD_OPTIONS.CUSTOM.value && (
            <img
              src={URL.createObjectURL(values.image)}
              height="100%"
              width="100%"
              style={{ 'object-fit': 'contain' }}
            />
          )}
          {!values.url &&
            values.image &&
            values.upload_type === FILE_UPLOAD_OPTIONS.BRAND_DESIGN.value && (
              <Box
                backgroundColor={color}
                display="flex"
                width="100%"
                height="100%"
                justifyContent="center"
                alignItems="center"
                ref={ref}
              >
                <img
                  src={URL.createObjectURL(values.image)}
                  height="100%"
                  width="100%"
                  style={{ 'object-fit': 'contain' }}
                />
              </Box>
            )}
        </Box>
      </Box>
      <Box display="flex" flexDirection="column" gap="24px" marginTop="24px">
        {SECTIONS.map((_val, idx) => renderSection(SECTIONS[idx]))}
      </Box>
    </React.Fragment>
  );
});

export default Review;
