import React from 'react';
import styled from 'styled-components';

export const Container = styled.div`
  background: rgba(11, 112, 231, 0.02);
  border: 1px solid rgba(11, 112, 231, 0.05);
  border-radius: 2px;
  padding: 12px 28px 12px 24px;
  position: relative;
  display: flex;
  align-items: center;
`;

export const LeftSideBorder = styled.div`
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  width: 4px;
  height: 40px;
  background: ${({ color }) => color ?? `#2a86f3`};
  border-radius: 0px 6px 6px 0px;
`;

const Image = styled.img`
  width: 32px;
`;

export const Title = styled.h2`
  font-weight: 600;
  font-size: 14px;
  line-height: 18px;
  color: #324664;
  width: ${({ width }) => width ?? 160}px;
  margin: 0 28px 0 12px !important;
`;

const Separator = styled.div`
  width: 2px;
  height: 40px;
  background: rgba(21, 102, 241, 0.18);
  border-radius: 4px;
  margin-right: 12px;
`;

export const DiscountBlock = styled.div`
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 12px;
  width: 248px;
  border: 1px solid rgba(21, 102, 241, 0.18);
  box-sizing: border-box;
  border-radius: 4px;
  margin-right: 8px;
`;

export const Percentage = styled.span`
  font-size: 24px;
  font-weight: 600;
  line-height: 40px;
  color: ${({ color }) => color ?? `#2a86f3`};
`;

export const Label = styled.div`
  font-size: 13px;
  line-height: 18px;
  color: ${({ color }) => color ?? `#2a86f3`};
`;

export const AmountBlock = styled(DiscountBlock)`
  width: 183px;
`;

export const AmountContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 2px;
`;

export const Rupee = styled.span`
  font-weight: 500;
  font-size: 12px;
  line-height: 40px;
  color: rgba(21, 102, 241, 0.32);
`;

export const Amount = styled.span`
  font-weight: 600;
  font-size: 24px;
  line-height: 40px;
  color: ${({ color }) => color ?? `#2a86f3`};
`;

const CloseButton = styled.button`
  outline: none;
  border: none;
  background: transparent;
  color: rgba(22, 47, 86, 0.38);
  font-size: 12px;
  margin: 0 4px 0 auto;
`;

export default function Base({ sideBorder, image, title, children, onDismiss }) {
  return (
    <>
      {sideBorder}
      <Image src={image} />
      {title}
      <Separator />
      {children}
      {onDismiss && (
        <CloseButton onClick={onDismiss}>
          <i className="i i-close" />
        </CloseButton>
      )}
    </>
  );
}
