import styled from 'styled-components';
import { media } from 'newAuth/breakpoints';

export const StyledHeading = styled.div(
  ({ theme }) => `
  color: #FFFFFF;
  margin-top: 110px;
  line-height: 120%;
  font-size: ${theme.typography.fonts.size[700]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
  .highlight {
    color: #FFCD4C;
  }
  @media ${media.mobileTabMax} {
    display: none;
  }
`,
);

export const StyledInfoWrapper = styled.div(
  ({ theme }) => `
    .yellow-seperator {
      border-top: 4px solid #EFAF41;
      border-radius: 4px;
      margin: 24px 0;
      width: 36px;
    }

    .sub-heading {
      color: #E7EBF1;
      font-size: 24px;
      font-weight: ${theme.typography.fonts.weight.bold};
      width: 500px;
      line-height: 36px;

      .rupee-icon {
        vertical-align: sub;
      }
    }

    .congrats-content-mweb {
      display: none;
    }
    @media ${media.mobileTabMax} {
      .yellow-seperator,
      .sub-heading,
      .info-card-wrapper {
        display: none;
      }

      .congrats-content-mweb {
        display: flex;
        padding: 80px 0 45px;
        flex-direction: column;
        align-items: center;

        label {
          font-weight: ${theme.typography.fonts.weight.bold};
          font-size: 20px;
          color: #FFFFFF;
          margin-top: 12px;
        }

        img {
          width: 20%;
        }
      }
    }

    .congrats-sub-heading {
      font-weight: ${theme.typography.fonts.weight.bold};
      font-size: 36px;
      color: #FFFFFF;

      span {
        color: #F9AE3E;
      }

      &.description{
        font-weight: ${theme.typography.fonts.weight.regular};
        font-size: 24px;
        width: 400px;
      }
    }

    .info-card-wrapper {
      margin: 72px 0 8px;
      width: 385px;
      min-height: 200px;
      background: #152379;
      padding: 30px 50px 12px 28px;
      border-radius: 0 32px 0 0;
      font-weight: ${theme.typography.fonts.weight.regular};
      font-size: ${theme.typography.fonts.size[200]}px;
      color: #FFFFFF;
      line-height: 24px;

      .info-card {
        .author-wrap {
          display: flex;

          .author-img {
            img {
              width: 50px;
              height: 50px;
            }
          }

          .author-info {
            margin-left: 16px;
            font-size: ${theme.typography.fonts.size[100]}px;
            font-weight: ${theme.typography.fonts.weight.regular};
            line-height: 170%;

            .author-des {
              color: #F9AE3E;
              margin-top: 4px;
            }
          }
        }
      }
    }
  `,
);

export const StyledCongratsContent = styled.div(
  ({ theme }) => `
  .congrats-img {
    margin-top: 60px;
    margin-left: -100px;
  }

  .congrats-heading {
    color: #F9AE3E;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: 36px;
    margin: 10px 0;
  }
  @media ${media.mobileTabMax} {
    display: none;
  }
`,
);

export const StyledTestimonials = styled.div`
  background: transparent;
  padding: 20px 50px 0 0;
  width: 636px;

  .carouselDiv{
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex-grow: 2;
  }
  
  .blue-seperator {
    border: 2px solid #121871;
    margin: 20px 0;
  }

  @media ${media.mobileTabMax} {
    display: none;
  }

  @media (max-width 767px){
    .theme-select{
      .btn.btn-primary{
        margin-top: 12px;
      }
    }
  }

  @media (max-width 1144px){
    .panel-theme{
      display: block;

      .panel-section--theme{
        width: 100%;
        display: block;
      }
      .preview-section--mobile{
        display: none;
      }
    }
  }
  .carousel-container{
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    overflow: hidden;

    button {
      position: absolute;
      height: 50px;
      z-index: 99;
      border: none;
      font-size: 30px;
      background: transparent;
    }
    .prev-btn{
      left 0;
    }
    .next-btn{
      right: 0;
    }
    .carousel-item{
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 100%;
      width: 100%;
      transition: 1s cubic-bezier(0.39, 0.575, 0.565, 1);
    }
    .carousel-item-left{
      justify-content: flex-start;
    }
  }
  .dot-container{
    display: flex;
    justify-content: center;
    margin: 10px;
  }

  .dot-container-left {
    justify-content: left;
  }

  .rounded-dot{
    height: 7px;
    width: 7px;
    border-radius: 50%;
    margin: 2px;
    background: #D6DBE2;
    cursor: pointer;
  }

  .active-dot{
    background: #4D9FEB;
  }

  .rounded-dot-yellow{
    background: rgba(249, 174, 62, 0.5);
  }
  .active-dot-yellow{
    background: #EBA642;
  }
`;
