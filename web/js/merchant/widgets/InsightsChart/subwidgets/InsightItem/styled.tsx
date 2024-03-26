import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import styled from 'styled-components';

export const ChartWrapper = styled.div(({ theme }) => ({
  backgroundColor: COLORS.backgroundPrimarySubtle,
  padding: theme.spacing[3],
  borderRadius: theme.border.radius.medium,
  height: 'fit-content',
}));
