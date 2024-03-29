import styled from 'styled-components';

export const StyledFileUploadSection = styled.div`
  display: flex;
  flex-direction: row;
  gap: 40px;
  @media screen and (max-width: 768px) {
    flex-direction: column;
    gap: 32px;
  }
`;

export const Dot = styled.span`
  height: 5px;
  width: 5px;
  border-radius: 50%;
  background: hsla(216, 27%, 36%, 1);
`;

export const DetailList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 8px;
  @media screen and (max-width: 768px) {
    gap: 2px;
  }
`;

export const DetailListItem = styled.div`
  display: flex;
  align-items: center;
  gap: 9px;
  margin-left: 8px;
`;

export const FileDescription = styled.div`
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
`;

export const StyledWatchVideo = styled.a`
  padding: 8px 12px;
  background: #ffffff;
  border: 1px solid #dfe3e9;
  border-radius: 2px;
  display: flex;
  gap: 4px;
  width: fit-content;
  margin-top: 8px;
  cursor: pointer;
`;

export const StyledFileUpload = styled.div`
  flex: 1;
  max-width: 47%;
  background: rgba(82, 143, 240, 0.02);
  .Dropzone {
    height: 100%;
    .Dropzone-cavity {
      height: 100%;
      background: rgba(82, 143, 240, 0.02);
      border: 1px dashed #2a86f3;
      .Dropzone-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        margin: 0;
        align-items: center;
        justify-content: center;
        gap: 4px;
        font-size: 14px;
        img {
          width: 42px;
          height: 56px;
          max-width: 42px;
          margin-bottom: 12px;
        }
        .Dropzone-content-desc {
          padding: 0;
          align-items: center;
          display: flex;
          flex-direction: column;
          .staged-desc {
            width: 100%;
            text-align: center;
          }
        }
        .icon {
          font-size: 125%;
          right: 15px;
        }
      }
      .staged-content {
        flex-direction: row;
        padding: 0px 15px;
        gap: 8px;
        img {
          margin: 0;
        }
        .Dropzone-content-desc {
          align-items: flex-start;
          width: 70%;
          .staged-desc {
            font-weight: 600;
            text-align: start;
          }
        }
        .icon {
          position: initial;
          transform: none;
        }
      }
    }
  }
  @media screen and (max-width: 768px) {
    max-width: 100%;
    .Dropzone {
      .Dropzone-cavity {
        .Dropzone-content {
          flex-direction: row;
          padding: 26px 15px;
          justify-content: flex-start;
          gap: 6px;
          img {
            width: 27px;
            height: 36px;
            margin-bottom: 0;
          }
          .Dropzone-content-desc {
            align-items: flex-start;
            flex: 1;
            &:first-child {
              font-size: 14px;
            }
            &:nth-child(2),
            .staged-desc {
              font-size: 12px;
              width: 55vw;
              text-align: start;
            }
          }
        }
      }
    }
  }
`;
