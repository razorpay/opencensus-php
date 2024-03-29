import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import { makePxValue } from '@razorpay/blade-old/src/_helpers/theme';
import { calculateLoaderSize } from 'merchant/views/PaymentHandle/utils';

const GREY_BACKGROUND = '#F2F4F8';
const MOBILE_SCREEN_MAX = '767px';

export const GreenCheck = styled.i`
  color: green;
`;

export const EditPHBtnWrapper = styled.div`
  display: flex;
  gap: 12px;
  justify-content: space-between;
`;

export const EqualWidth = styled.div`
  width: 50%;
`;

export const RedCheck = styled.i`
  color: #fff;
  width: 12px;
  height: 12px;
  display: flex;
  margin-top: 4px;
  font-size: 10px;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
  background: rgb(209, 45, 45);
`;

export const SlugImgWrapper = styled.div`
  cursor: pointer;
`;

export const CTAWrapper = styled.div`
  margin: 8px 0 16px 0;
`;

export const EditOutlineWrapper = styled.div`
  color: rgba(43, 131, 234, 1);
  margin: 0 6px 0 0;
`;

export const InfoWrapper = styled.div`
  display: flex;
  align-items: flex-start;
`;

export const BlurFilterContainer = styled(View)`
  filter: ${(props) => (props.isTestMode ? 'blur(2.5px)' : 'blur(0)')};
`;

export const NewContainer = styled.div`
  color: #fff;
  display: flex;
  font-size: 10px;
  padding: 1px 4px;
  line-height: 14px;
  border-radius: 2px;
  align-items: center;
  background: #009c5c;
  justify-content: center;
`;

export const Wrapper = styled(View)`
  border: 1px solid #dfe5e7;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    width: 95%;
  }
`;

export const Line = styled(View)`
  height: 30px;
  margin: 0 12px;
  border-left: 1px solid #a8afc4;
`;

export const EditContainer = styled(View)`
  width: 95%;
`;

export const EditSlugContainer = styled.div`
  width: 312px;
  padding: 18px;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    width: 100%;
  }
`;

export const InlineMessage = styled.span`
  font-weight: 400;
  font-size: 12px;
  line-height: 16px;
  color: rgba(22, 47, 86, 0.54);
`;

export const TextSuggestion = styled.div`
  gap: 3px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  flex-direction: row;
  margin-top: 4px;
  justify-content: flex-start;
`;

export const TransactionHeader = styled.div`
  padding: 20px;
  display: flex;
  align-items: center;
  background: #f3f4f7;
`;

export const ButtonWrapper = styled.div`
  display: flex;
  gap: 18px;
  margin-top: 24px;
`;

export const ShareBtnWrapper = styled.div`
  width: 102px;
`;

export const ExportWrapper = styled.i`
  color: #132644 !important;
  font-size: 11px;
`;

export const LegendWrapper = styled.legend`
  width: 30px;
  margin-bottom: 0;
`;

export const SlugEditInput = styled.input`
  padding: 0;
  border: transparent;
  background: transparent;
`;

export const NoticeWrapper = styled.div`
  padding: 12px;
  border-radius: 4px;
  background: rgba(223, 135, 0, 0.09);
  border: 1px solid rgba(223, 135, 0, 0.32);
`;

export const HideMobile = styled.div`
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: none;
  }
`;

export const ShowMobile = styled.div`
  display: none;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: block;
  }
`;

export const MobileSlugImgContainer = styled.div`
  width: 100%;
  margin-top: 16px;
`;

export const LoadingCTA = styled(View)`
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    width: 240px;
  }
`;

export const FlexV2 = styled(View)`
  display: flex;
  align-items: ${(props) => (props.alignItems ? props.alignItems : '')};
  justify-content: ${(props) => (props.justifyContent ? props.justifyContent : '')};
  flex-direction: ${(props) => (props.flexDirection ? props.flexDirection : '')};
  gap: ${(props) => (props.gap ? props.gap : '')};
`;

export const LinkCTA = styled(View)`
  margin: 128px 0 24px 0;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    margin: 52px 0 8px 0;
  }
`;

export const GifContainer = styled.div`
  width: 340px;
  bottom: 40px;
  position: relative;
`;

export const BottomImgWrapper = styled.div`
  bottom: 44%;
  position: relative;
  transform: rotate(180deg);
`;

export const SlugContainer = styled.div`
  width: 100%;
  height: 48px;
  border-radius: 4px;
  border: 1px solid rgba(21, 102, 241, 0.18);
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    border: 1px solid #528ff0;
  }
`;

export const BannerSlugContainer = styled.div`
  width: 100%;
  height: 48px;
  border-radius: 4px;
  border: 1px solid rgba(21, 102, 241, 0.18);
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    overflow: hidden;
    border: 1px solid #528ff0;
  }
`;

export const LeftContainer = styled(View)`
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: none;
  }
`;

export const Circle = styled.div<any>`
  border-radius: 50%;
  width: ${(props) => (props.width ? props.width : '32px')};
  height: ${(props) => (props.height ? props.height : '32px')};
  margin: ${(props) => (props.margin ? props.margin : '0 8px 0 0')};
  background: ${(props) => (props.background ? props.background : GREY_BACKGROUND)};
`;

export const LeftPanel = styled(View)`
  height: 600px;
  display: flex;
  max-height: 600px;
  align-items: center;
  flex-direction: column;
  justify-content: center;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: none;
  }
`;

export const RightPanel = styled(View)`
  height: 600px;
  display: flex;
  padding: 0 80px;
  max-height: 600px;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    width: 100%;
    padding: 0 16px;
  }
`;

export const StyledText = styled.div.attrs(({ className }) => ({
  className: `custom-class ${className}`,
}))<any>`
  color: ${(props) => (props.color ? props.color : '')};
  font-size: ${(props) => (props.fontSize ? props.fontSize : '14px')};
  font-weight: ${(props) => (props.fontWeight ? props.fontWeight : '')};
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    line-height: 24px;
    color: ${(props) => (props.color ? props.color : '')};
    font-weight: ${(props) => props.mobileFontWeight || props.fontWeight || ''};
    font-size: ${(props) => props.mobileFontSize || props.fontSize || '14px'};
  }
`;

export const StyledLoader = styled(View)`
  border-radius: 50%;
  border: ${(props) => (props.border ? props.border : '1.5')}px solid
    ${(props) => props.theme.bladeOld.colors.primary[700]};
  border-top: 3px solid ${(props) => props.theme.bladeOld.colors.background[600]};
  width: ${(props) => (props.makePxValue ? makePxValue(props.width) : props.width)};
  height: ${(props) => (props.makePxValue ? makePxValue(props.height) : props.height)};
  animation: spin 0.6s linear infinite;
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: none;
  }
`;

export const ShimmerContainer = styled(View)`
  opacity: 0.3;
  margin-top: 24px;
  border-radius: 4px;
  border-radius: 2px;
  background: ${GREY_BACKGROUND};
  background-size: 800px 100px;
  animation: shimmer 1.2s forwards infinite linear;
  width: ${(props) => calculateLoaderSize('desktop', props.size).width};
  height: ${(props) => calculateLoaderSize('desktop', props.size).height};
  background-image: linear-gradient(
    90deg,
    rgba(204, 204, 204, 0.1) 0%,
    #cccccc 80%,
    rgba(204, 204, 204, 0.1) 100%
  );
  @keyframes shimmer {
    0% {
      background-position: -400px 0;
    }
    100% {
      background-position: 400px 0;
    }
  }
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    width: ${(props) => calculateLoaderSize('mobile', props.size).width};
    height: ${(props) => calculateLoaderSize('mobile', props.size).height};
  }
`;

export const BottomWrapper = styled(View)`
  transform: rotate(180deg);
`;

export const InfoContainer = styled(View)`
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    min-height: 240px;
  }
`;

export const IconBackgroundWrapper = styled(View)`
  color: #bd7a03;
`;

export const AlertContainer = styled(View)`
  gap: 8px;
  width: 429px;
  height: 40px;
  display: flex;
  padding: 12px;
  border-radius: 4px;
  align-items: center;
  justify-content: flex-start;
  background: rgba(223, 135, 0, 0.09);
  border: 1px solid rgba(223, 135, 0, 0.32);
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    display: none;
  }
`;

export const AlertInfoListContainer = styled(View)`
  width: 100%;
  height: 33px;
  display: flex;
  padding-left: 12px;
  background: #fbf8e5;
  align-items: center;
  border: 1px solid #e0e5e7;
  border-radius: 2px 2px 0px 0px;
  @media screen and (max-width: ${MOBILE_SCREEN_MAX}) {
    padding: 16px;
    height: 54px;
  }
`;

export const ShareContainer = styled(View)`
  width: 20px;
  height: 20px;
  display: flex;
  margin-left: 4px;
  margin-right: 24px;
  border-radius: 2px;
  background: #ffffff;
  align-items: center;
  justify-content: center;
  border: 1px solid #dfe3e9;
`;

export const DropDownSlugContainer = styled.fieldset`
  height: 44px;
  bottom: 12px;
  display: flex;
  cursor: pointer;
  margin: 16px 0 0 0;
  padding: 12px 8px;
  border-radius: 1px;
  position: relative;
  flex-direction: row;
  justify-content: space-between;
  background: rgba(42, 134, 243, 0.03);
  border: 1px solid rgba(42, 134, 243, 0.16);
`;

export const CenterAlignContainer = styled.div`
  bottom: 12px;
  display: flex;
  width: 100%;
  position: relative;
  justify-content: space-between;
`;

export const AlignTogether = styled(View)`
  display: flex;
`;

export const HighlightBold = styled.span`
  font-weight: 600;
  font-size: 13px;
  color: rgba(22, 47, 86, 0.87);
  text-decoration: none;
  text-align: left;
  letter-spacing: 0px;
  line-height: 18px;
  overflow: initial;
  text-overflow: initial;
  max-height: initial;
  position: relative;
`;
