import React from 'react';
import { Block } from 'merchant_common/views/Reports/components/styled';
import { EmptyTablePropsType } from './types';
import { EmptyTableWrapper } from './styled';
import { Title, Text } from 'merchant_common/views/Reports/components';

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
      <Title contrast="low" size="small" type="normal">
        {title}
      </Title>
      <Text type="subdued" size="medium" contrast="low">
        {desc}
      </Text>
      <Block m={10}>{children}</Block>
    </EmptyTableWrapper>
  );
};
