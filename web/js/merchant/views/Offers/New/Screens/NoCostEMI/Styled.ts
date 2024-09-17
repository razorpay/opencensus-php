import styled from 'styled-components';

export const FootNote = styled.div`
  &.low-cost-footnote {
    margin-left: 66px;
  }
`;

export const StyledActionRow = styled.div`
  display: flex;
  justify-content: space-between !important;
  align-items: flex-start;
  padding: 12px 16px;
  width: 100%;

  .offer_header {
    width: 20%;
  }

  .offer-body-row {
    width: 20%;
    .Input-content {
      margin-left: 0 !important;
    }
    .Input {
      margin: 0px;
    }
  }

  .tenure,
  .interest {
    height: 40px;
    display: flex;
    align-items: center;
  }

  .interest {
    width: 10%;
  }
`;

export const StyledOfferForm = styled.div`
  &.low-cost-offer-container {
    .no-cost-offer-plans {
      display: flex;
      align-items: center;
      justify-content: space-between;
      .Input-content {
        width: 90%;
        margin-left: 0;
      }
    }
    .Input-label {
      width: 10% !important;
      text-align: left;
      top: 0px;
    }
    .emi-options {
      margin-left: 0;
      .heading {
        position: relative;
      }
    }
  }
`;

export const StyledOfferModal = styled.div`
  .Modal-container--NewSubscriptionLink {
    max-width: 980px;
  }
`;
