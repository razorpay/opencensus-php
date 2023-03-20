import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import styled from 'styled-components';

export const Root = styled.ul`
  width: 100%;
  list-style-type: none;
  margin-top: 0;
  margin-bottom: 0;
  padding-left: 0;
  padding-right: 0;
`;

export const PanelContainer = styled.li`
  width: 100%;
  display: list-item;
  list-style-type: none;
  box-sizing: border-box;
  border-bottom-width: 1px;
  border-bottom-style: solid;
  border-bottom-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

export const Header = styled.div<any>`
  justify-content: space-between;
  cursor: ${(props) => (props.$disabled ? 'not-allowed' : 'pointer')};
  display: flex;
  align-items: center;
  font-size: ${({ theme }) => theme.bladeOld.fonts.size.medium};
  line-height: ${({ theme }) => theme.bladeOld.fonts.lineHeight.medium};
  color: ${({ theme }) => getColor(theme, 'shade.960')};
  padding-top: ${(props) => props.theme.bladeOld.spacings.medium};
  padding-bottom: ${(props) => props.theme.bladeOld.spacings.medium};
`;

export const Content = styled.div<any>`
  max-height: ${(props) => (props.$expanded ? '100%' : 0)};
  height: ${(props) => (props.$expanded ? 'auto' : 0)};
  color: ${({ theme }) => getColor(theme, 'shade.970')};
  font-size: ${({ theme }) => theme.bladeOld.fonts.size.medium};
  line-height: ${({ theme }) => theme.bladeOld.fonts.lineHeight.medium};
  padding-bottom: ${(props) => (props.$expanded ? props.theme.bladeOld.spacings.medium : 0)};
  overflow: 'hidden';
  transition-duration: all;
  transition-duration: 400ms;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
`;
