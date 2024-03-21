import styled from 'styled-components';

export const StyledSlider = styled.div`
  border-radius: 3px
  background-color: #FFF
  box-shadow: 0 0 0 1px rgba(63,63,68,0.05), 0 1px 3px 0 rgba(63,63,68,0.15)
  position: relative
  height: 70vh
  max-height: 580px
  min-height: 540px
`;

export const StyledOnboardingSlide = styled.div`
min-width: 90%
  margin: 0 auto
  height: 100%
  display: flex
  align-items: center

  .Landing--Image{
    overflow: hidden
    z-index: 2
    position: relative
    background-color: #F4FDFF}

    .img{
      position: relative
      right: 140px
      top: 144px
      transform: translate(0, -25%)
      width: 110%
      max-width: 700px
      min-width: 520px}

  .Product--Details{
    padding: 0 64px
    margin-top: 120px
    align-self: flex-start
    width: 50%}

    .Details-heading{
      font-size: 16px
      line-height: 21px
      margin: 8px 0 24px
      font-weight: bold
      color: #0D2366}

      .dash{
        border: 1.5px solid #2CCA74
        height: 0
        width: 43px
        display: inline-block
        margin-right: 8px
        vertical-align: middle}

    .Details-title{
      font-size: 40px
      line-height: 48px
      margin: 16px 0 8px
      font-weight: bold
      color: #0D2366}

    .Details-dash{
      font-size: 18px
      line-height: 24px
      margin-bottom: 42px}

    .Button-Container{
      position: absolute
      bottom: 64px
      right: 64px}

      .Button{
        background-color: #3B80EE
        color: #FFF
        border-radius: 4px
        font-size: 16px
        font-weight: bold
        height: 50px
        min-width: 126px
        line-height: 19px
        padding: 10px 20px
        border: 1px solid #E6E7E8
        outline: none}
`;
export const StyledSliderDots = styled.div`
  position: absolute
  left: 58%
  margin-top: 20px
  transform: translate(-50%, 0)
  text-align: center

  .SliderDots-Dot{
    border-radius: 50%
    border: 1px solid #528FF0
    cursor: pointer
    display: inline-block
    width: 9px
    height: 9px
    margin-right: 5px
  }

  .SliderDots-Dot--active{
    background-color: #528FF0
  }
`;

export const ReadMoreContainer = styled.div`
  min-height: 45px;
`;

export const StyledButtonContainer = styled.div`  
  display: flex
  flex-direction: column

  button{
    border-radius: 4px;
  }

  @media (max-width: 440px){
    display: flex;
    flex-direction: column;
    width: 80%;
    align-items: center;
    gap: 10px;
  }
`;

export const StyledFeaturesContainer = styled.div`
  gap: 10px;
`;

export const StyledOnboardingImg = styled.div`
  object-fit: cover !important;

  .onboarding-img {
    transform: none !important;
    height: 100% !important;
    width: 100% !important;
    top: 0 !important;
    right: 0 !important;
  }
`;
