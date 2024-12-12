import styled from 'styled-components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const StyledTab = styled(FlexCentered)<{ active: boolean }>(({ theme, active }) => {
  const { FIELD_FOCUS_COLOR_L3 } = reportsTheme(theme);
  return `
            padding: ${theme.spacing[4]}px;
            border-bottom: 2px solid ${active ? FIELD_FOCUS_COLOR_L3 : 'transparent'};      
            color: ${active ? FIELD_FOCUS_COLOR_L3 : 'unset'} !important; 
          `;
});

export const TabsHeader = styled.header`
  display: flex;
  flex-wrap: wrap;
`;

export const TabsContainer = styled.header`
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  background: #ffffff;
  width: 100%;
  border-bottom: 1px solid #e2e8ea;
`;
