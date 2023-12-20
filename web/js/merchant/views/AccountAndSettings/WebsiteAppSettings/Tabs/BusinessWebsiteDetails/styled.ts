import styled from 'styled-components';

export const StyledBusinessWebsiteContainer = styled.div`
  position: relative;

  [data-testid='website-card-container'] {
    > div {
      border: none;
    }
  }

  [data-blade-component='card-body'] {
    border: none;
    padding: 0 5px;
  }

  [data-blade-component='card-header'] {
    margin-bottom: 0;

    [data-blade-component='divider'] {
      border: none;
    }
  }
`;

export const StyledLinksContainer = styled.div`
  @media (min-width: 801px) {
    display: flex;
    justify-content: space-evenly;
    gap: 12px;
  }
`;

export const StyledAdditionalWebsiteContainer = styled.div`
  margin: 0 12px;
  @media (max-width: 480px) {
    margin-bottom: 24px;
  }
`;
