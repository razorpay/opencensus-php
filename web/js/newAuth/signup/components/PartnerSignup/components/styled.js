import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';
import imageTickIcon from 'assets/partner-dashboard/tick-icon.svg';

export const StyledSignupHeader = styled.div`
  position: absolute;
  top: 20px;
  right: 60px;
  width: 520px;
  display: flex;
  justify-content: space-between;
  padding: 30px 25px 0;
  margin-bottom: 16px;

  @media ${media.mobileTabMax} {
    width: 88%;
    top: 0;
    position: relative;
    padding: 30px 0px 0px;
    right: 0;
    margin-left: 26px;
  }
`;

export const StyledHeaderCTA = styled.div`
  display: flex;

  .already-user-text {
    color: #ffffff;
    margin-right: 16px;
    margin-top: 8px;
    font-size: 12px;
  }
`;

export const StyledInfoIcon = styled.div(
  ({ visibility }) => `
  cursor: pointer;
  margin: 10px 6px 0px;
  visibility: ${visibility}
  `,
);

export const StyledCongratsFormWrapper = styled.div(
  ({ theme }) => `
    position: absolute;
    display: flex;
    flex-direction: column;
    row-gap: 32px;
    right: 65px;
    top: 48px;
    .mt-20{
      margin-top: 20px; 
    }

    @media ${media.mobileTabMax} {
      position: static;
      margin: auto;
      width: 90%;
    }

    .congrats-form {
      width: 530px;
      height: 305px;
      background: #FFFFFF;
      border: 1px solid #DBDBDB;
      border-radius: 16px;
      padding: 32px 100px;
      text-align: center;

      @media ${media.mobileTabMax} {
        width: auto;
        text-align: left;
        padding: 45px 15px 25px;
        height: auto;

        .star-logo {
          display: none;
        }

        .form2-heading {
          display: none;
        }
      }
    }

    .congrats-form-1 {
      ul {
        list-style: none;
        padding-left: 20px;
        padding-right: 20px;
      }

      ul li {
        text-align: left;
        background: url('${imageTickIcon}') no-repeat left center;
        padding: 25px 10px 5px 40px;
        list-style: none;
        margin: 0;
        vertical-align: middle;
        font-size: ${theme.typography.fonts.size[75]}px;
        font-weight: ${theme.typography.fonts.weight.bold};
        line-height: 20px;

        &:last-child {
          padding: 5px 10px 5px 40px;
        }
      }
    }

    .congrats-form-2 {
      .form2-heading {
        font-weight: ${theme.typography.fonts.weight.bold};
        margin-bottom: 16px;
      }

      .form2-sub-heading {
        font-weight: ${theme.typography.fonts.weight.regular};
        font-size: ${theme.typography.fonts.size[100]}px;
        color: #213554;
        margin-bottom: 32px;
        text-align: left;
        line-height: 20px;
      }

      .email-btn-wrap {
        display: flex;
        justify-content: space-between;
        width: 80%;
        margin: 0 auto;

        .sec-btn-wrap {
          width: 48%;
          margin: auto;
          color: #2B83EA;
          font-weight: ${theme.typography.fonts.weight.bold};
          font-size: ${theme.typography.fonts.size[100]}px;
          cursor: pointer;
        }

        .primary-btn-wrap {
          width: 48%;
        }
      }
    }

    .congrats-note {
      padding: 15px 60px 15px 40px;
      background: #152379;
      font-weight: ${theme.typography.fonts.weight.regular};
      font-size: ${theme.typography.fonts.size[200]}px;
      color: #E7E9EC;
      width: 530px;
      border-radius: 4px;
      margin-bottom: 24px;

      @media ${media.mobileTabMax} {
        width: auto;
      }
    }
  `,
);

export const StyledCongratsInputWrapper = styled.div(
  ({ theme }) => `
    font-weight: ${theme.typography.fonts.weight.regular};
    font-size: ${theme.typography.fonts.size[200]}px;

    width: 80%;
    margin: 12px auto;
  `,
);
