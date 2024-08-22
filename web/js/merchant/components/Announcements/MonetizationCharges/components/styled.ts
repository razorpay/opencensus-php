import styled from 'styled-components';

export const MonetizationChargesWrapper = styled.div`
  .separator {
    background: #162F568A;
    display: inline-block;
    width: 6px;
    height: 6px;
    background-color: #162F568A;
    border-radius 50%;
    margin: 0 8px;
  }
`;

export const MonetizationChargesDetailsWrapper = styled.div`
  padding: 20px 12px;

  .contact-sales-button {
    color: #2950da;
    text-align: center;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
  }

  .view-product-benefits-button {
    color: #2950da;
    text-align: center;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin-top: 16px;
  }

  .margin-0 {
    margin: 0;
  }

  @media screen and (max-width: 768px) {
    padding: 0px 12px 20px;
  }
`;

export const CustomPricingWrapper = styled.div`
  padding: 20px 12px;

  .bg-size-cover {
    background-size: cover;
    height: 100%;
  }

  @media screen and (max-width: 768px) {
    padding: 0px 12px 20px;
  }
`;

export const ArrowLeftIconWrapper = styled.div`
  cursor: pointer;
`;

export const ProductWiseBenefitsWrapper = styled.div`
  padding: 20px 12px;

  .cursor-pointer {
    cursor: pointer;
  }

  .contact-sales-button {
    color: #2950da;
    text-align: center;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
  }

  @media screen and (max-width: 768px) {
    min-height: 436px;
    padding: 0px 12px 20px;
  }
`;

export const NoCodeAppButton = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding: 4px 10px;
  border-radius: 2px;
  border: 1px solid #d9efff;
`;

export const NoCodeAppButtonIcon = styled.img`
  width: 16px;
  height: 16px;
`;
