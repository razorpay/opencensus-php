import { Flexbox } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import styled, { css } from 'styled-components';

export const StyledNeedsClarification = styled(Flexbox.Column)`
  gap: 24px;
  width: 468px;
  @media screen and (max-width: 768px) {
    width: 100%;
  }
`;

export const UploadContainer = styled(Flexbox.Column)`
  gap: 9px;
`;

export const StyledUploadContainer = styled.div<any>`
  background: rgba(82, 143, 240, 0.02);
  .Dropzone {
    ${({ isMulti }) =>
      !isMulti &&
      css`
        height: 122px;
      `}
    .Dropzone-cavity {
      height: 100%;
      background: rgba(82, 143, 240, 0.02);
      border: 1px dashed #2a86f3;
      display: flex;
      flex-direction: column;
      gap: 12px;
      ${({ isMulti }) =>
        isMulti &&
        css`
          padding: 12px 0px;
        `}
      .Dropzone-content {
        height: 100%;
        margin: 0;
        display: flex;
        gap: 15px;
        align-items: center;
        justify-content: center;
        position: relative;
        img {
          width: 42px;
          height: 56px;
          max-width: 42px;
        }
        .Dropzone-content-desc {
          padding: 0;
          width: 50%;
          text-align: center;
          font-size: 14px;
          .staged-desc {
            font-weight: 600;
          }
        }
        .icon {
          font-size: 125%;
          right: 50px;
        }
      }
    }
  }
  @media screen and (max-width: 768px) {
    .Dropzone {
      ${({ isMulti }) =>
        !isMulti &&
        css`
          height: 88px;
        `}
      .Dropzone-cavity {
        height: 100%;
        padding: 0;
        gap: 0px;
        .Dropzone-content {
          flex-direction: row;
          padding: 20px;
          justify-content: flex-start;
          height: 100%;
          gap: 12px;
          position: relative;
          img {
            width: 27px;
            height: 36px;
            margin-bottom: 0;
          }
          .Dropzone-content-desc {
            align-items: flex-start;
            flex: 1;
            &:nth-child(2),
            .staged-desc {
              font-size: 12px;
              width: 55vw;
              text-align: start;
            }
          }
          .icon {
            font-size: 125%;
            right: 15px;
          }
        }
      }
    }
  }
`;
