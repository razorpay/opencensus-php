import styled from 'styled-components';

export const CtaContainer = styled.div`
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  background: #528ff01a;
  padding: 16px 24px;
  justify-content: space-between;
  align-items: center;

  button {
    font-weight: 600;
    font-size: 14px;
    line-height: 150%;
    padding: 12px 16px;
    cursor: pointer;
    border: none;
    width: 90px;
  }

  .primary-cta {
    background: #2a86f3;
    color: #fafbfc;
    border-radius: 2px;
  }

  .secondary-cta {
    border: 1px solid #2a86f3;
    background: #eaf2fd;
    color: #2b83ea;
    border-radius: 2px;
    margin-right: 16px;
  }

  button:disabled {
    background: #d9d9d9;
    color: rgba(65, 68, 73, 0.8);
  }
`;

export const AddItemContainer = styled.div`
  background: #fff;
  width: 100%;
  padding: 16px 24px;
  display: flex;
  flex-direction: column;

  .scroll {
    max-height: 256px;
    overflow: scroll;
  }

  .scroll::-webkit-scrollbar {
    -webkit-appearance: none;
    width: 4px;
  }

  .scroll::-webkit-scrollbar-thumb {
    background-color: #2a86f3;
  }

  .scroll::-webkit-scrollbar-track:vertical {
    background: #f6f6f7;
  }

  .page-spinner-container {
    height: 240px;
  }
`;

export const ProductList = styled.div`
  max-height: inherit;
  height: 256px;
  overflow: auto;
  flex-grow: 1;
  padding: 16px;
  border: 1px solid #f6f6f6;
  border-top: none;
`;

export const ModalHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;

  .title {
    font-size: 16px;
    font-weight: 600;
    line-height: 150%;
    color: #171a1e;
  }

  .exit-cta {
    font-size: 16px;
    color: #162f5661;
  }
`;

export const SearchBox = styled.div`
  display: flex;
  background: #f6f6f6;
  padding: 12px 18px;
  border: none;
  margin: 16px 0 0;
  align-items: center;
  gap: 8px;
  width: 100%;
`;

export const SearchInput = styled.input`
  background: #f6f6f6;
  border: none;
`;

export const ItemWrapper = styled.div`
  margin-right: 12px;
  .Input {
    margin: 0;
  }

  hr {
    opacity: 0.1;
    border-top: 1px dashed black;
    margin: 12px 0;
  }
`;

export const ItemContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
`;

export const VariantContainer = styled.div`
  margin: 12px 32px;
`;

export const Image = styled.img`
  height: 40px;
  width: 40px;
  object-fit: cover;
`;

export const ProductName = styled.div`
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
`;
