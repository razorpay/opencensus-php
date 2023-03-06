import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';

export const StyledErrorModalWrap = styled.div(
  ({ theme }) => `
  font-family: 'Lato';
  padding: 60px 40px 60px 40px;
  color: #213554;
  position: relative;
  width: 428px;
  height: 282px;

  .error-title {
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: 20px;
    line-height: 28px;
    margin-bottom: 20px;

    @media ${media.mobileTabMax} {
      font-size: 16px;
      line-height: 24px;
    }

  }

  .error-desc {
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: 20px;
    margin-bottom: 36px;

    @media ${media.mobileTabMax} {
      font-weight: 400;
      font-size: 14px;
    }
  }

  .error-btn-wrap {
    display: flex;
    justify-content: space-between;
    width: 80%;
    margin: 0 auto;

    @media ${media.mobileTabMax} {
      width: 100%;
    }

    .sec-btn-wrap {
      width: 48%;
      margin: auto;
      color: #2B83EA;
      font-weight: ${theme.typography.fonts.weight.bold};
      font-size: ${theme.typography.fonts.size[200]}px;
      cursor: pointer;

      @media ${media.mobileTabMax} {
        text-align: center;
      }
    }

    .primary-btn-wrap {
      width: 48%;
    }
  }

  @media ${media.mobileTabMax} {
    padding: 28px 0 0;
    width: 100%;
    height: 100%;
  }
`,
);
