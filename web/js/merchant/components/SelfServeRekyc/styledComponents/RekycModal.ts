import styled from 'styled-components';

export const ModalImageWrapper = styled.img`
  width: 100%;
  height: 204px;
  border-top-right-radius: 8px;
  border-top-left-radius: 8px;
  margin-bottom: 24px;
`

export const ModalWrapper = styled.div`
  div[data-blade-component='bottom-sheet'][data-testid="bottomsheet-surface"][role='dialog']{
    height: 474px;
  }

  div[data-blade-component='BottomSheetGrabHandle']{
    padding-top: 0px;
    margin-bottom: 0px;
  }

  div[data-blade-component='BottomSheetGrabHandle']::after{
    content: '';
    margin-top: 0;
    height: 0;
  };

  div[data-blade-component="bottom-sheet-header"]{
    > [data-blade-component='base-box']{
      height: 0;

      > [data-blade-component='base-box']{
       top: 12px;
      }
    };
  };

  div[data-blade-component="bottom-sheet-body"]{
    > [data-blade-component='base-box']{
      overflow: hidden;
    };
  };
`