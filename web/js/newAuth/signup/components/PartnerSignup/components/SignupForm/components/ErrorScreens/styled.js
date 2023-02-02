import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';

export const StyledErrorModalWrap = styled.div(
  ({ theme }) => `
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
  }

  .error-desc {
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: 20px;
    margin-bottom: 36px;
  }

  .error-btn-wrap {
    display: flex;
    justify-content: space-between;
    width: 80%;
    margin: 0 auto;

    .sec-btn-wrap {
      width: 48%;
      margin: auto;
      color: #2B83EA;
      font-weight: ${theme.typography.fonts.weight.bold};
      font-size: ${theme.typography.fonts.size[200]}px;
      cursor: pointer;
    }

    .primary-btn-wrap {
      width: 48%;
    }
  }

  @media ${media.mobileTabMax} {
    display: none;
  }
`,
);
