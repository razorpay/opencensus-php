import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';
import imageDesktopSignupBg from 'assets/partner-dashboard/signup-bkg-desktop.svg';
import imageMobileSignupBg from 'assets/partner-dashboard/signup-bkg-mobile.svg';

export const StyledSignupWrapper = styled.div(
  ({ theme }) => `
    background: url('${imageDesktopSignupBg}');
    font-family: 'Lato';
    height: 100%;
    padding-left: 105px;
    position: relative;
    overflow: scroll;
    
    .logo-wrap {
      display: flex;
      padding-top: 60px;
      max-width: 154px;

      .logo {
        width: 85%;
      }
      .partner-logo {
        margin-left: 10px;
        margin-top: 10px;
        height: 15px;
      }
    }
    .signup-footer-mweb{
      display: none;
    }
  
  @media ${media.mobileTabMax} {
    background: url('${imageMobileSignupBg}');
    background-size: cover;
    position: relative;
    height: 100%;
    padding: 0 0 30px;
    font-size: ${theme.typography.fonts.size[100]}px;

    .logo-wrap, .logo, .partner-logo  {
      display: none;
    }

    .signup-footer-mweb {
      display: flex;
      margin: 20px auto 0;
      justify-content: center;
      align-items: end;

      .logo {
        width: 25%;
        margin: -3px 8px;
      }
    }

      .signup-form-content-wrap {
        padding: 0;

        .container {
          width: 100%;
          margin: 0 0 45px;
          border-radius: 5px;

          .skill {
            border-radius: 5px;
          }
        }

        .signup-form-content {
          padding: 0 24px;

          .step-wrap {


            .signup-form-input {
              width: 100%;
            }
          }
        }

        .footer-wrap {
          bottom: 28px;
          left: 0;
          right: 0;
          width: 100%;
          margin: auto;
          padding: 0 24px 0 12px;
        }
      }
    }

    .congrats-form-wrap {
      position: static;
      margin: auto;
      width: 90%;

      .congrats-form {
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
      
      .congrats-note {
        width: auto;
      }
    }

    .logo-footer {
      display: block;
    }

    .step-wrap {


      .tile-wrap {
        margin: 0;
        width: 80%;
      }
    }
  }
 `,
);
