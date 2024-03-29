import styled from 'styled-components';

export const StyledCard = styled.div(
  ({ theme, customCardStyle }) => `
    background-color: ${theme.colors.surface.background.gray.intense};
    padding: ${theme.spacing[6]}px ${theme.spacing[7]}px;
    border-left: 1px solid ${theme.colors.surface.border.gray.subtle};
    ${customCardStyle}
`,
);

export const CardHeader = styled.div(
  ({ theme }) => `
    display: flex;
    font-size: ${theme.typography.fonts.size[100]}px;
    font-weight: ${theme.typography.fonts.weight.regular};
    line-height: ${theme.typography.lineHeights.l}px;
    color: ${theme.colors.surface.text.gray.subtle};
    padding-bottom: ${theme.spacing[4]}px;
  `,
);

export const CardHeaderIcon = styled.span(
  ({ theme }) => `
    padding-top: 1px;
    margin-left: ${theme.spacing[3]}px;
  `,
);

export const CardContent = styled.div(
  ({ theme }) => `
      font-size: ${theme.typography.fonts.size[500]}px;
      font-weight: ${theme.typography.fonts.weight.regular};
      line-height: ${theme.typography.lineHeights['3xl']}px;
      color: ${theme.colors.surface.text.gray.subtle};
      padding-bottom: ${theme.spacing[5]}px;
    `,
);

export const CardFooter = styled.div(
  ({ theme }) => `
        display: flex;
        align-items: flex-start;
        min-height: 36px;
        font-size: ${theme.typography.fonts.size[75]}px;
        font-weight: ${theme.typography.fonts.weight.regular};
        line-height: ${theme.typography.lineHeights.s}px;
        color: ${theme.colors.surface.text.gray.muted};
      `,
);
export const FlexBetween = styled.div(
  () => `
    display: flex;
    justify-content: space-between;
    align-items: center;
  `,
);

export const TextFooter = styled.div(
  ({ theme }) => `
    display: flex;
    padding-top: ${theme.spacing[3]}px;
  `,
);

export const NoWrap = styled.div(
  () => `
    white-space: nowrap;
    display: flex;
    align-items: center;
  `,
);

export const CardFooterIcon = styled.span(
  ({ theme }) => `
    font-size: ${theme.typography.fonts.size[25]}px;
    margin-right: ${theme.spacing[3]}px;
    padding-top: ${theme.spacing[1]}px;
  `,
);

export const CardWrapper = styled.div(
  ({ theme }) => `
    padding: ${theme.spacing[6]}px 0;
  `,
);

export const SummaryHeader = styled.div(
  ({ theme }) => `
    display: flex;
    justify-content: space-between;
    overflow-wrap: break-word;
    word-break: break-word;
    background-color: ${theme.colors.surface.background.gray.intense};
    padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
    border-bottom: 1px solid ${theme.colors.surface.border.gray.subtle};
    box-shadow: ${theme?.shadows?.offsetX?.level?.[1]}px ${theme?.shadows?.offsetY?.level?.[1]}px ${theme?.shadows?.blurRadius?.level?.[1]}px ${theme?.shadows?.color?.level?.[1]};
    @media(max-width: 900px){
      display: block;
    }
`,
);

export const SummaryHeaderSection = styled.div(
  ({ theme }) => `
    display: flex;
    align-items: center;
    @media(max-width: 900px){
      padding: ${theme.spacing[2]}px 0;
    }
    @media(max-width: 420px){
      display: block;
    }
`,
);

export const SettlementCycle = styled.div(
  ({ theme }) => `
    display: flex;
    align-items: center;
    padding-right: ${theme.spacing[6]}px;
`,
);

export const Documentation = styled.div(
  ({ theme }) => `
    display: flex;
    align-items: center;
    padding-left: ${theme.spacing[6]}px;
    border-left: 1px solid ${theme.colors.surface.border.gray.subtle};
    @media(max-width: 420px){
      border-left: 0;
      padding-left: 0;
    }
`,
);

export const MediumBold = styled.span(
  ({ theme }) => `
  font-size: ${theme.typography.fonts.size[200]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
  line-height: ${theme.typography.fonts.size[400]}px;
  color: ${theme.colors.surface.text.gray.subtle};
  padding-right: ${theme.spacing[4]}px;
`,
);

export const TimeSummary = styled.span(
  ({ theme }) => `
  display: flex;
  font-size: ${theme.typography.fonts.size[75]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  padding-right: ${theme.spacing[4]}px;
  color: ${theme.colors.interactive.text.primary.disabled};
  align-items: center;
`,
);

export const SummaryInfoIcon = styled.span(
  ({ theme }) => `
    font-size: ${theme.typography.fonts.size[25]}px;
    margin-right: ${theme.spacing[2]}px;
    margin-top: ${theme.spacing[2]}px;
  `,
);

export const SettlementSummary = styled.div(
  ({ theme }) => `
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    background-color: ${theme.colors.surface.background.gray.intense};
    box-shadow: ${theme?.shadows?.offsetX?.level?.[1]}px ${theme?.shadows?.offsetY?.level?.[1]}px ${theme?.shadows?.blurRadius?.level?.[1]}px ${theme?.shadows?.color?.level?.[1]};
    @media(min-width: 600px){
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    @media(min-width: 1200px){
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
`,
);

export const SpacerSpan = styled.span(
  ({ theme }) => `
  padding-right: ${theme.spacing[4]}px;
  @media(max-width: 420px){
      padding: 0;
    }
`,
);

export const CashAdvanceWrapper = styled.span(
  ({ theme }) => `
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.fonts.size[200]}px;
`,
);
