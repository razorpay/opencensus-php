import styled from 'styled-components';

export const Title = styled.p`
  color: ${({ theme }) => `${theme.colors.surface.text.normal.lowContrast}`};
  font-size: ${({ theme }) => `${theme.typography.fonts.size[100]}`};
  font-weight: ${({ theme }) => `${theme.typography.fonts.weight.bold}`};
  padding-left: ${({ theme }) => `${theme.spacing[4]}px`};
`;
export const Time = styled.p`
  color: ${({ theme }) => `${theme.colors.surface.text.subtle.lowContrast}`};
  font-size: ${({ theme }) => `${theme.typography.fonts.size[80]}`};
`;

export const Description = styled.p`
  color: ${({ theme }) => `${theme.colors.surface.text.muted.lowContrast}`};
  font-size: ${({ theme }) => `${theme.typography.fonts.size[100]}px`};
  font-weight: ${({ theme }) => `${theme.typography.fonts.weight.regular}`};
  font-style: normal;
  padding: 6px 0px;
`;

export const Container = styled.div`
  padding: ${({ theme }) => `${theme.spacing[5]}px`};
  width: 330px;
  border: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
  background: ${({ theme }) => `${theme.colors.surface.background.level3.lowContrast}`};
  max-height: 600px;
  overflow-y: scroll;
`;

export const CustomIcon = styled.span`
  height: 20px;
  width: 20px;
  border-radius: 100px;
  display: flex;
  justify-content: center;
  align-items: center;
  line-height: 1;
  background: ${({ theme }) => `${theme.colors.feedback.background.negative.lowContrast}`};
`;

export const LineContainer = styled.div`
  border-left: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
  margin: ${({ theme }) => `${theme.spacing[1]}px`};
  margin-left: 10px;
  padding-left: ${({ theme }) => `${theme.spacing[6]}px`};
  padding-bottom: ${({ theme }) => `${theme.spacing[4]}px`};
  &:last-child {
    border: none;
  }
`;
