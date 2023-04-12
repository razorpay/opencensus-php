import { reportsTheme } from 'merchant_common/views/Reports/configs';
import styled from 'styled-components';
import { CollapsibleFormChildWrapper } from 'merchant_common/views/Reports/components/CollapsibleForm/styled';

export const CustomDurationWrapper = styled.div(({ theme }) => {
  const { MARGIN_DIVIDER } = reportsTheme(theme);
  return `
    display: flex;
    gap: ${MARGIN_DIVIDER}px;
    flex-wrap: unset;
    width: 100%;
    margin-bottom: 20px;
    @media (max-width: ${theme.breakpoints.m}px) {
        flex-wrap: wrap;
        margin-bottom: 0;
    }
`;
});

export const FieldWrapper = styled(CollapsibleFormChildWrapper)`
  width: 100%;
`;

export const CloseModalButtonContainer = styled.div`
  margin-right: 10px;
`;
