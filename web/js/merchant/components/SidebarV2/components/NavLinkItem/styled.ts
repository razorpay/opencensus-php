import { Theme } from '@razorpay/blade/components';
import { Link } from 'react-router-dom';
import styled, { css } from 'styled-components';

export const LinkItem = styled(Link)<{ isActive: boolean }>(
  ({ isActive }) => `
  height: 29px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  line-height: 21px;
  color: #b4b6bc;
  position: relative;
  ${
    isActive &&
    css`
      color: #ffffff;
      background: #000000;
      &:before {
        content: '';
        width: 3px;
        height: 100%;
        border-radius: 0px 2px 2px 0px;
        background: #ffffff;
        position: absolute;
      }
    `
  }
  &:focus {
    color: #ffffff;
  }
  &:hover {
    color: #ffffff;
    ${({ isActive }) =>
      !isActive &&
      css`
        background: rgba(255, 255, 255, 0.08);
      `}
  }
`,
);

export const LinkButtonItem = styled(Link)<any>`
  display: flex;
  color: #80a5ff;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 1rem;
  font-weight: 600;
  font-size: 0.75rem;

  &:hover {
    color: #ffffff;
  }

  &:focus {
    color: #80a5ff;
  }
`;

export const Typo = styled.span`
  font-weight: 400;
  font-size: 14px;
`;

const IconImageStyles = css`
  display: flex;
  margin: 0 12px 0 16px;
  max-width: 18px;
  min-width: 15px;
  justify-content: center;
`;

export const Icon = styled.i<{
  showNewHomePage?: boolean;
  isActive?: boolean;
}>`
  ${IconImageStyles}
  color: ${({ theme, showNewHomePage, isActive }) =>
    showNewHomePage
      ? isActive
        ? theme.colors.surface.icon.gray.normal
        : theme.colors.surface.icon.gray.subtle
      : 'reset'}
`;

export const ImageStyled = styled.img`
  ${IconImageStyles}
`;

export const NewTag = styled.span`
  width: 30px;
  height: 14px;
  background: #60e380;
  border-radius: 2px;
  font-weight: 600;
  font-size: 10px;
  line-height: 12px;
  text-transform: uppercase;
  color: #ffffff;
  text-align: center;
  position: absolute;
  right: 16px;
`;

export const BadgeContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: ${theme.colors.surface.text.staticWhite.normal};
    position: absolute;
    right: 16px;
  `,
);

export const LinkItemV2 = styled(Link)<{ isActive: boolean }>(
  ({ theme, isActive }) => `
  display: flex;
  padding: ${theme.spacing[3]}px ${theme.spacing[5]}px;
  align-items: center;
  background-color: ${isActive ? theme.colors.interactive.icon.staticWhite.normal : 'unset'};
  margin: 0 ${theme.spacing[3]}px;
  border-radius: ${theme.border.radius.medium}px;
  position: relative;

  & > i {
    margin: 0 ${theme.spacing[3]}px 0 0;
  }

  & > .rzp-image {
    position: absolute;
    left: 0;
  }
  & > .rzp-image, & > .rzp-image > img {
    border-top-left-radius: ${theme.border.radius.medium}px;
    border-bottom-left-radius: ${theme.border.radius.medium}px;
  }
`,
);
