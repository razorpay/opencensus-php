import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';

const BulletListContainer = styled.div`
  width: 100%;
  margin: 16px 0 20px;
`;

const BulletListInfo = styled.div`
  font-size: 14px;
  line-height: 20px;
  color: #5d6d86;
  margin-bottom: 12px;
`;

const BulletListItem = styled.div`
  display: flex;
  align-items: center;
  gap: 12px;
  &:not(:last-child) {
    margin-bottom: 6px;
  }
  img {
    width: 6.75px;
  }
`;

const BulletListItemLabel = styled.span`
  font-size: 14px;
  line-height: 20px;
  color: #5d6d86;
`;

export default function List({ label, items }) {
  return (
    <BulletListContainer>
      <BulletListInfo>{label}</BulletListInfo>
      {items.map((item, idx) => (
        <BulletListItem key={idx}>
          <img src="/dist/css/assets/settlements/diamond.svg" alt="diamond bullet" />
          <BulletListItemLabel>{item}</BulletListItemLabel>
        </BulletListItem>
      ))}
    </BulletListContainer>
  );
}

List.propTypes = {
  label: PropTypes.string.isRequired,
  items: PropTypes.arrayOf(PropTypes.string).isRequired,
};
