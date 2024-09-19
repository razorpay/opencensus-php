import styled from 'styled-components';
import { Heading } from '@razorpay/blade/components';

export const Page = styled.div`
  background-color: white;
`;

export const Section = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${({ theme }) => theme.spacing[7]}px;
  margin-bottom: 40px;
`;

export const SectionTitle = styled(Heading)`
  font-size: ${({ theme }) => theme.typography.fonts.size[500]};
  font-weight: ${({ theme }) => theme.typography.fonts.weight.semibold};
  letter-spacing: line-height: ${({ theme }) => theme.typography.letterSpacings[500]};
  line-height: ${({ theme }) => theme.typography.lineHeights[500]}px;
`;

export const Card = styled.div`
  border: 1px solid ${({ theme }) => theme.colors.surface.border.gray.subtle};
  border-radius: ${({ theme }) => theme.spacing[3]}px;
  padding: ${({ theme }) => theme.spacing[7]}px;
  padding-bottom: ${({ theme }) => theme.spacing[8]}px;
  display: flex;
  flex-direction: column;
  gap: ${({ theme }) => theme.spacing[5]}px;
  max-width: 460px;
`;

export const CardHeader = styled.div`
  display: flex;
  gap: ${({ theme }) => theme.spacing[4]}px;
  items-align: center;
`;

CardHeader.Icon = styled.img`
  width: 24px;
  height: 24px;
`;

CardHeader.Title = styled(Heading)`
  margin-block: 0;
  flex-grow: 1;
  color: ${({ theme }) => theme.colors.surface.text.gray.normal};
  font-weight: ${({ theme }) => theme.typography.fonts.weight.semibold};
  line-height: ${({ theme }) => theme.typography.lineHeights[400]}px;
`;

export const ToggleWrapper = styled.div`
  display: flex;
  align-items: center;
  gap: ${({ theme }) => theme.spacing[3]}px;
`;

export const CreateCouponLink = styled.div`
  font-size: 14px;
  font-weight: 600;
  color: #5591ed;
  cursor: pointer;
`;

export const ShopifySyncModalWrapper = styled.div`
  background: #fff;

  .date {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .Input {
    margin: 0;
  }

  label {
    font-size: 14px;
    font-weight: 600;
    color: #162F56BD';
  }
`;
