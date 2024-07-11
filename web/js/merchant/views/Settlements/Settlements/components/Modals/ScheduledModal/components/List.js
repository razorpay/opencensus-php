import React from 'react';
import { Text } from '@razorpay/blade/components';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import ImgDiamond from 'assets/settlements/diamond.svg';

const BulletListContainer = styled.div`
  width: 100%;
  margin: 16px 0 20px;
`;

const BulletListItem = styled.div`
  display: flex;
  align-items: flex-start;
  gap: 12px;
  &:not(:last-child) {
    margin-bottom: 12px;
  }
  img {
    width: 8px;
    margin-top: 6px;
  }
`;

export default function List({ label, items, labelPosition = 'top' }) {
  return (
    <BulletListContainer>
      {labelPosition === 'top' ? (
        <Text color="surface.text.gray.subtle" marginBottom="spacing.5">
          {label}
        </Text>
      ) : null}
      {items.map((item, idx) => (
        <BulletListItem key={idx}>
          <img src={ImgDiamond} alt="diamond bullet" />
          <Text color="surface.text.gray.subtle">{item}</Text>
        </BulletListItem>
      ))}
      {labelPosition === 'bottom' ? (
        <Text color="surface.text.gray.subtle" marginTop="spacing.5" marginBottom="spacing.8">
          {label}
        </Text>
      ) : null}
    </BulletListContainer>
  );
}

List.propTypes = {
  label: PropTypes.string.isRequired,
  items: PropTypes.arrayOf(PropTypes.string).isRequired,
};
