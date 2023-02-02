import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';
import { Checkbox } from '@razorpay/blade/components';

export const StyledFormWrap = styled.div`
  width: 533px;
  height: 662px;
  background: #ffffff;
  right: 50px;
  top: 110px;
  position: absolute;
  border: 1px solid #dbdbdb;
  border-radius: 16px;
  line-height: 24px;
  letter-spacing: 0.25px;

  @media ${media.mobileTabMax} {
    padding: 0px;
    position: static;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    margin: 26px auto;
    width: 90%;
    border-radius: 5px;
    height: auto;
  }
`;

export const StyledFormContentWrap = styled.div`
  position: relative;
  height: 100%;
  padding: 0px 74px;
  @media ${media.mobileTabMax} {
    padding: 0;
  }
`;

export const StyledFormContent = styled.div`
  @media ${media.mobileTabMax} {
    padding: 0 24px;
  }
`;

export const StyledProgressBarContainer = styled.div`
  background-color: rgb(192, 192, 192);
  width: 100%;
  border-radius: 15px;
  margin: 74px auto 24px;
  @media ${media.mobileTabMax} {
    width: 100%;
    margin: 0 0 45px;
    border-radius: 5px;
  }
`;

export const StyledProgressBarSkill = styled.div(
  ({ $progress }) => `
    background: linear-gradient(90deg, #FFBF1C 0%, #FFAB2D 106.82%), #FFFFFF;
    color: white;
    padding: 0.7%;
    text-align: right;
    font-size: 20px;
    border-radius: 5px;
    width: ${$progress}%;
    @media ${media.mobileTabMax} {
      border-radius: 5px;
    }
  `,
);

export const StyledStepWrapper = styled.div(
  ({ theme }) => `
    text-align: center;
    padding: 0px 30px;

    .resend-otp {
      text-align: left;
      margin: 16px 0px 4px;
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.bold};
      color: #435775;

      &.text-success {
        color: #008659;
      }

      &.help-text {
        cursor: default;
        font-weight: ${theme.typography.fonts.weight.regular};
        font-size: 11px;
        line-height: 16px;
        text-align: left;
        color: #8895A8;
      }

      .resend-otp-btn {
        padding-left: 6px;
        font-weight: ${theme.typography.fonts.weight.bold};
        color: #1566F1;
        cursor: pointer;
        &.resend-otp-disabled {
          cursor: default;
          color: #8895A8;
        }
      }
    }
    @media ${media.mobileTabMax} {
      text-align: left;
      padding: 0 16px 0 0;
      margin-bottom: 64%;
      min-height: 225px;

      .resend-otp {
        margin: 10px 0;
        font-size: 12px;
      }
    }
  `,
);

export const StyledTitle = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[600]}px;
    line-height: 36px;
    color: #162F56;
    margin-bottom: 16px;
    text-align: center;
    @media ${media.mobileTabMax} {
      text-align: left;
    }
  `,
);

export const StyledSubtitle = styled.div(
  ({ theme, $textAlign = 'left' }) => `
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[200]}px;
    color: #435775;
    margin-bottom: 24px;
    margin-left: auto;
    text-align: ${$textAlign};

    .change-text {
      color: #2A86F3;
      font-size: ${theme.typography.fonts.size[100]}px;
      cursor: pointer;

      &:hover {
        text-decoration: underline;
      }
    }

    .mobile-num {
      font-weight: ${theme.typography.fonts.weight.bold};
      margin-right: 6px;
    }
    @media ${media.mobileTabMax} {
      font-weight: ${theme.typography.fonts.weight.regular};
      font-size: ${theme.typography.fonts.size[200]}px;
      color: #435775;
      margin-bottom: 44px;
      width: 100%;
      text-align: left;
      margin-left: 0;
      margin-right: 0;
    }
  `,
);

export const StyledTileWrap = styled.div(
  ({ theme }) => `
    width: 100%;
    text-align: left;
    margin: 0 auto;
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: 20px;

    @media ${media.mobileTabMax} {
      margin: 0;
      width: 80%;
    }
  `,
);

export const StyledTileHeading = styled.div(
  ({ theme }) => `
    display: flex;
    color: rgba(33, 53, 84, 0.67);
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[100]}px;
    margin-top: 20px;
  `,
);

export const StyledTilesAll = styled.div`
  display: flex;
  flex-wrap: wrap;
  gap: 10px 10px;
  margin-top: 8px;
`;

export const StyledInputWrapper = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[200]}px;

    width: 100%;
    margin: 0 auto;
  `,
);

export const StyledFooterWrap = styled.div(
  ({ theme }) => `
  position: absolute;
  bottom: 100px;
  left: 100px;

  @media ${media.mobileTabMax} {
    left: inherit;
    position: inherit;
  }

  .btn-wrap {
    width: 90%;
    margin: 0 auto;

    @media ${media.mobileTabMax} {
      width: 100%;
    }
  }

  .signup-footer {
    text-align: center;
    margin-top: 18px;
    font-size: ${theme.typography.fonts.size[75]}px;
    color: rgba(22, 47, 86, 0.54);

    @media ${media.mobileTabMax} {
      margin: 16px 0px;
    }
  }
  .blue-link {
    color: #2A86F3;
    text-decoration: none;
  }
`,
);

export const StyledCheckboxWrapper = styled.div`
  display: flex;
  margin: 16px auto;
  @media ${media.mobileTabMax} {
    margin: 16px auto;
  }
`;
export const StyledOptInCheckbox = styled(Checkbox)``;

export const StyledBTypeInfoWrap = styled.div`
  padding: 60px 40px 60px 40px;
  color: #213554;
  position: relative;
  width: 428px;
  height: 418px;
`;

export const StyledBTypeInfoHeading = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: 24px;
  `,
);

export const StyledBTypeHeading = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[200]}px;
    margin-bottom: 8px;
    margin-top: 28px;
    line-height: 24px;
  `,
);

export const StyledBTypeDescription = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: 20px;
    ul {
      font-size: ${theme.typography.fonts.size[100]}px;
      font-weight: ${theme.typography.fonts.weight.regular};
      margin-left: 26px;
      margin-top: 20px;
    }
  `,
);

export const StyledBtypeLabel = styled.div(
  ({ $isActive }) => `
    padding: 7px 10px;
    border: 1px solid #DBDBDB;
    border-radius: 7px;
    cursor: pointer;

    :hover {
      background: #3B8CEB;
      color: #FFFFFF;
    }
    ${
      $isActive
        ? `
      background: #0B70E7;
      color: #FFFFFF;
      `
        : ''
    }
  `,
);

export const StyledIconWrap = styled.img`
  width: 16px;
  float: right;
  margin: 3px 4px;
`;

export const StyledInfoIcon = styled.span`
  cursor: pointer;
  margin: 0px 6px;
`;

export const StyledErrorContent = styled.div(
  ({ theme }) => `
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 50px;

    .error-image {
      width: 214px;
      height: 214px;
    }

    .error-screen-title {
      margin: 24px 0px;
      font-weight: ${theme.typography.fonts.weight.bold};
      font-size: 24px;
      color: #162F56;
    }

    .error-screen-desc {
      width: 90%;
      font-weight: ${theme.typography.fonts.weight.regular};
      font-size: ${theme.typography.fonts.size[200]}px;
      line-height: 28px;
      color: #435775;
    }
  `,
);

export const StyledPartnerTypeTiles = styled.div(
  ({ theme }) => `
  text-align: left;
  margin-left: -19%;
  width: 138%;

  @media ${media.mobileTabMax} {
    .desktop-only{
      display: none;
    }
  }
  .error-message {
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: 13px;
    line-height: 22px;
    color: #D13821;
  }

  .pts-tile-wrap {
    border: 1.5px solid #D4E0FC;
    border-radius: 2px;
    cursor: pointer;
    background: linear-gradient(to right, #D4E0FC 4.6%, #FFFFFF 4.6%);
    padding-left: 4.6%;
    margin-bottom: 4%;
    transition: background 1s linear;

    &.active,
    &:hover {
      background: linear-gradient(to right, #D4E0FC 4.6%, #F1F5FE 4.6%);
    }

    .pts-tile-content {
      margin-top: 8px;
      margin-bottom: 11px;
      margin-left: 11px;
      margin-right: 11px;
      position: relative;

      .pts-image {
        width: 12%;

        img {
          position: absolute;
          top: 0;
        }

        display: inline-block;
      }

      .pts-description {
        font-size: ${theme.typography.fonts.size[75]}px;
        line-height: 22px;
        width: 88%;
        display: inline-block;

        .pts-heading {
          display: block;
          line-height: 24px;
          font-size: ${theme.typography.fonts.size[200]}px;
        }

        .pts-sub-heading {
          color: #162F56;
        }

        .pts-sub-description {
          font-size: ${theme.typography.fonts.size[25]}px;
          line-height: 18px;
          color: #75849Bd9;
        }

        .row {
          width: 100%;
          display: flex;
          flex-direction: row;
        }

        .column {
          width: 50%;
          padding-left: 3%;

          ul li {
            list-style: none;
          }
          ul li::before {
            content: '\\2022';
            color: rgba(22, 47, 86, 0.87);
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
          }
        }
      }
    }
  }
`,
);

export const StyledTopRightClose = styled.div`
  position: absolute;
  top: 25px;
  right: 25px;
  font-size: 27px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6a788c;
  border-radius: 16px;
`;
