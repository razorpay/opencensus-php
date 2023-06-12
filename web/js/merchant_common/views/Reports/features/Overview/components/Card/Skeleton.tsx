import React, { useMemo } from 'react';
import { CardWrapper, Header, Footer, TextWrapper } from './style';
import { Skeleton } from 'merchant_common/views/Reports/components/styled';
import { randomInt } from 'merchant_common/views/Reports/utils/commonUtils';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const CardSkeleton = (): JSX.Element => {
  const { theme } = useTheme();
  const width = useMemo(() => [randomInt(6, 10), randomInt(6, 10), randomInt(6, 10)], []);

  return (
    <CardWrapper theme={theme}>
      <Header theme={theme}>
        <Skeleton
          aria-label="Card Icon"
          style={{
            height: 32,
            width: 32,
            borderRadius: 40,
            marginRight: 10,
          }}
        />
        <Skeleton
          aria-label="Card Title"
          style={{
            height: '1rem',
            width: `${width[0]}0%`,
          }}
        />
      </Header>
      <TextWrapper aria-label="Card Desc" theme={theme}>
        <Skeleton
          style={{
            width: '100%',
            height: '0.6rem',
            marginBottom: 10,
          }}
        />
        <Skeleton
          style={{
            width: `${width[1]}0%`,
            height: '0.6rem',
            marginBottom: 10,
          }}
        />
        <Skeleton
          style={{
            width: `${width[2]}0%`,
            height: '0.6rem',
          }}
        />
      </TextWrapper>

      <Footer count={1} theme={theme}>
        <Skeleton
          aria-label="Card Link"
          style={{
            width: 50,
            height: '0.6rem',
          }}
        />
      </Footer>
    </CardWrapper>
  );
};
//s
