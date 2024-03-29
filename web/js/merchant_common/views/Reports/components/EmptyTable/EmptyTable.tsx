import React from 'react';
import { Block } from 'merchant_common/views/Reports/components/styled';
import { EmptyTablePropsType } from './types';
import { EmptyTableWrapper } from './styled';
import { Text, Heading } from '@razorpay/blade/components';

export const EmptyTable = ({ src, title, desc, children }: EmptyTablePropsType): JSX.Element => {
  return (
    <EmptyTableWrapper aria-label="Table is empty">
      <img
        src={src}
        alt="Empty Table Illustration"
        style={{
          height: 264,
          width: 264,
          marginBottom: 15,
          objectFit: 'contain',
        }}
      />
      <Heading size="large" color="surface.text.gray.normal">
        {title}
      </Heading>
      <Text size="medium" color="surface.text.gray.muted">
        {desc}
      </Text>
      <Block m={10}>{children}</Block>
    </EmptyTableWrapper>
  );
};
