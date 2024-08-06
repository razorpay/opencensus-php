import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';
import { TimelineStepStatus } from 'apps/pos/src/app/types/AgreementSigning';

export const StyledCardContainer = styled.div(
  ({ theme, selected }: { theme: Theme; selected: boolean }) => {
    if (selected) {
      return `
          border: ${theme.border.width.thicker}px solid ${theme.colors.interactive.border.primary.disabled};
          padding: ${theme.spacing[3]}px;
          `;
    }
    return `
    border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
    padding: ${theme.spacing[3]}px;
    `;
  },
);

export const StyledCard = styled.div(({ theme, selected }: { theme: Theme; selected: boolean }) => {
  if (selected) {
    return `
        background-color:${theme.colors.surface.background.gray.moderate};
        padding:${theme.spacing[4]}px;
        border-radius: ${theme.border.radius.small}px;
        `;
  }
  return `padding:${theme.spacing[4]}px`;
});

export const StyledTimelineItemWrapper = styled.div(
  ({ theme, status }: { theme: Theme; status: TimelineStepStatus }) => {
    return `
      width:100%;
      margin-left:9px;
      border-left:${
        status === 'completed'
          ? `2px solid ${theme.colors.surface.border.gray.subtle}`
          : `2px solid ${theme.colors.surface.background.gray.moderate}`
      } ;
      padding-left: ${theme.spacing[8]}px;
      position:relative;
      `;
  },
);

export const StyledTimelineIcon = styled.div(({ theme }: { theme: Theme }) => {
  return `
      position:absolute;
      z-index:1;
      top:-1px;
      left:-12px;
      border:2px solid ${theme.colors.surface.background.gray.moderate};
      display:flex;
      justify-content:center;
      align-items:center;
  `;
});

export const StyledIcon = styled.img(
  () => `width:100% ; background-color:white ; border-radius:100%`,
);
