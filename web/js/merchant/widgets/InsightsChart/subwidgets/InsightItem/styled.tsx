import styled from 'styled-components';

export const ChartWrapper = styled.div(({ theme }) => ({
  backgroundColor: theme.colors.surface.background.gray.subtle,
  padding: theme.spacing[3],
  borderRadius: theme.border.radius.medium,
  height: 'fit-content',
}));
